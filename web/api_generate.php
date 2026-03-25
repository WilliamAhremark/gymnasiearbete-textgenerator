<?php

require_once 'config.php';

header('Content-Type: application/json');

$token = getenv('HF_API_TOKEN');
$aiApiUrl = trim(getenv('AI_API_URL') ?: '');
$primaryModel = trim(getenv('HF_MODEL') ?: 'gpt2');
$fallbackModelsEnv = trim(getenv('HF_FALLBACK_MODELS') ?: 'distilgpt2,bigscience/bloom-560m');

if (!$token && $aiApiUrl === '') {
    http_response_code(500);
    echo json_encode(['error' => 'Missing AI configuration: set HF_API_TOKEN and/or AI_API_URL']);
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

$internalPayload = json_encode([
    'prompt' => $prompt,
    'length' => $length
]);

// Keep model list unique and ordered: primary first, then fallbacks.
$models = array_values(array_unique(array_filter(array_map('trim', array_merge([$primaryModel], explode(',', $fallbackModelsEnv))))));

function requestJson(string $url, string $payload, array $headers, int $timeout = 90): array {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_errno($ch) ? curl_error($ch) : '';
    curl_close($ch);

    return [$httpCode, $response, $curlError];
}

function maybeExtractText($parsed): string {
    if (!is_array($parsed)) {
        return '';
    }

    if (isset($parsed[0]['generated_text'])) {
        return (string)$parsed[0]['generated_text'];
    }

    if (isset($parsed['generated_text'])) {
        return (string)$parsed['generated_text'];
    }

    if (isset($parsed['text'])) {
        return (string)$parsed['text'];
    }

    return '';
}

$result = null;
$lastHttpCode = 502;
$attemptErrors = [];

if ($token) {
    foreach ($models as $modelName) {
        $encodedModel = rawurlencode($modelName);
        $endpoints = [
            "https://router.huggingface.co/hf-inference/models/{$encodedModel}",
            "https://api-inference.huggingface.co/models/{$encodedModel}"
        ];

        foreach ($endpoints as $url) {
            // Retry transient provider errors once before moving to next fallback.
            for ($retry = 0; $retry < 2; $retry++) {
                [$httpCode, $response, $curlError] = requestJson(
                    $url,
                    $payload,
                    [
                        "Authorization: Bearer {$token}",
                        "Content-Type: application/json"
                    ]
                );
                $lastHttpCode = $httpCode > 0 ? $httpCode : 502;

                if ($curlError !== '') {
                    $attemptErrors[] = "{$modelName} via {$url}: {$curlError}";
                    break;
                }

                $parsed = json_decode((string)$response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $attemptErrors[] = "{$modelName} via {$url}: invalid JSON";
                    break;
                }

                if ($httpCode >= 200 && $httpCode < 300) {
                    $result = $parsed;
                    break 3;
                }

                $errorText = is_array($parsed) ? ($parsed['error'] ?? ('HTTP ' . $httpCode)) : ('HTTP ' . $httpCode);
                $attemptErrors[] = "{$modelName} via {$url}: {$errorText}";

                // Do not continue for auth/permission/configuration issues.
                if (in_array($httpCode, [400, 401, 403], true)) {
                    http_response_code($httpCode);
                    echo json_encode(['error' => $errorText, 'details' => $parsed]);
                    exit;
                }

                // Retry on transient rate-limit/server loading errors.
                if ($retry === 0 && in_array($httpCode, [408, 409, 425, 429, 500, 502, 503, 504], true)) {
                    usleep(250000);
                    continue;
                }

                break;
            }
        }
    }
}

// Secondary fallback: your own deployed API service (AI_API_URL).
if ($result === null && $aiApiUrl !== '') {
    [$httpCode, $response, $curlError] = requestJson(
        $aiApiUrl,
        $internalPayload,
        ["Content-Type: application/json"],
        120
    );
    $lastHttpCode = $httpCode > 0 ? $httpCode : $lastHttpCode;

    if ($curlError !== '') {
        $attemptErrors[] = "AI_API_URL {$aiApiUrl}: {$curlError}";
    } else {
        $parsed = json_decode((string)$response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $attemptErrors[] = "AI_API_URL {$aiApiUrl}: invalid JSON";
        } elseif ($httpCode >= 200 && $httpCode < 300) {
            $result = $parsed;
        } else {
            $apiError = is_array($parsed)
                ? ($parsed['detail'] ?? $parsed['error'] ?? ('HTTP ' . $httpCode))
                : ('HTTP ' . $httpCode);
            $attemptErrors[] = "AI_API_URL {$aiApiUrl}: {$apiError}";
        }
    }
}

if ($result === null) {
    http_response_code($lastHttpCode >= 400 ? $lastHttpCode : 502);
    echo json_encode([
        'error' => 'All configured AI providers failed',
        'attempts' => $attemptErrors
    ]);
    exit;
}

$text = maybeExtractText($result);

if (trim($text) === '') {
    http_response_code(502);
    echo json_encode(['error' => 'Model returned empty text', 'details' => $result]);
    exit;
}

echo json_encode([
    "text" => $text
]);