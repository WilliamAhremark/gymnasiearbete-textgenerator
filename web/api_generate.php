<?php

require_once 'config.php';

header('Content-Type: application/json');

$token = getenv('HF_API_TOKEN');
$primaryModel = trim(getenv('HF_MODEL') ?: 'gpt2');
$fallbackModelsEnv = trim(getenv('HF_FALLBACK_MODELS') ?: 'distilgpt2,bigscience/bloom-560m');

if (!$token) {
    http_response_code(500);
    echo json_encode(['error' => 'HF token missing']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$prompt = trim($data['prompt'] ?? '');
$length = (int)($data['length'] ?? 120);

if ($prompt === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Prompt is required']);
    exit;
}

if ($length < 1 || $length > 1000) {
    http_response_code(400);
    echo json_encode(['error' => 'Length must be between 1 and 1000']);
    exit;
}

$payload = json_encode([
    "inputs" => $prompt,
    "options" => [
        "wait_for_model" => true
    ],
    "parameters" => [
        "max_new_tokens" => $length,
        "return_full_text" => false
    ]
]);

// Keep model list unique and ordered: primary first, then fallbacks.
$models = array_values(array_unique(array_filter(array_map('trim', array_merge([$primaryModel], explode(',', $fallbackModelsEnv))))));

function requestHuggingFace(string $url, string $payload, string $token): array {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_errno($ch) ? curl_error($ch) : '';
    curl_close($ch);

    return [$httpCode, $response, $curlError];
}

$result = null;
$lastHttpCode = 502;
$attemptErrors = [];

foreach ($models as $modelName) {
    $encodedModel = rawurlencode($modelName);
    $endpoints = [
        "https://router.huggingface.co/hf-inference/models/{$encodedModel}",
        "https://api-inference.huggingface.co/models/{$encodedModel}"
    ];

    foreach ($endpoints as $url) {
        [$httpCode, $response, $curlError] = requestHuggingFace($url, $payload, $token);
        $lastHttpCode = $httpCode > 0 ? $httpCode : 502;

        if ($curlError !== '') {
            $attemptErrors[] = "{$modelName} via {$url}: {$curlError}";
            continue;
        }

        $parsed = json_decode((string)$response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $attemptErrors[] = "{$modelName} via {$url}: invalid JSON";
            continue;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $result = $parsed;
            break 2;
        }

        $errorText = is_array($parsed) ? ($parsed['error'] ?? ('HTTP ' . $httpCode)) : ('HTTP ' . $httpCode);
        $attemptErrors[] = "{$modelName} via {$url}: {$errorText}";

        // Do not continue to other endpoints/models for auth and permission issues.
        if (in_array($httpCode, [400, 401, 403], true)) {
            http_response_code($httpCode);
            echo json_encode(['error' => $errorText, 'details' => $parsed]);
            exit;
        }

        // For 404/410/429/5xx we continue trying fallback endpoints/models.
    }
}

if ($result === null) {
    http_response_code($lastHttpCode >= 400 ? $lastHttpCode : 502);
    echo json_encode([
        'error' => 'All configured Hugging Face models/endpoints failed',
        'attempts' => $attemptErrors
    ]);
    exit;
}

$text = '';
if (is_array($result) && isset($result[0]['generated_text'])) {
    $text = (string)$result[0]['generated_text'];
} elseif (is_array($result) && isset($result['generated_text'])) {
    $text = (string)$result['generated_text'];
}

if (trim($text) === '') {
    http_response_code(502);
    echo json_encode(['error' => 'Model returned empty text', 'details' => $result]);
    exit;
}

echo json_encode([
    "text" => $text
]);