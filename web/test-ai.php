<?php

header('Content-Type: application/json');

$token = trim(getenv('HF_TOKEN') ?: getenv('HF_API_TOKEN') ?: '');
$model = trim(getenv('HF_MODEL') ?: 'gpt2');
$prompt = trim($_GET['prompt'] ?? 'Hello my name is');
$length = (int)($_GET['length'] ?? 64);
$length = max(1, min($length, 256));

if ($token === '') {
    http_response_code(500);
    echo json_encode([
        'error' => 'Missing HF token. Set HF_TOKEN (or HF_API_TOKEN) in environment.',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$url = 'https://router.huggingface.co/hf-inference/models/' . rawurlencode($model);
$payload = json_encode([
    'inputs' => $prompt,
    'options' => [
        'wait_for_model' => true,
    ],
    'parameters' => [
        'max_new_tokens' => $length,
        'return_full_text' => false,
    ],
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$token}",
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 60,
]);

$response = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_errno($ch) ? curl_error($ch) : '';
curl_close($ch);

$decoded = null;
$jsonError = null;
if ($response !== false) {
    $decoded = json_decode((string)$response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $jsonError = json_last_error_msg();
    }
}

echo json_encode([
    'model' => $model,
    'url' => $url,
    'http_code' => $http,
    'curl_error' => $error,
    'json_error' => $jsonError,
    'raw_response' => $response,
    'decoded_json' => $decoded,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
