<?php

require_once 'config.php';

header('Content-Type: application/json');

$token = getenv('HF_API_TOKEN');

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

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://api-inference.huggingface.co/models/distilgpt2");
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

if (curl_errno($ch)) {
    http_response_code(502);
    echo json_encode(['error' => curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

$result = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid JSON from Hugging Face', 'raw' => $response]);
    exit;
}

if ($httpCode >= 400) {
    $hfError = $result['error'] ?? ('Hugging Face API error (HTTP ' . $httpCode . ')');
    http_response_code($httpCode);
    echo json_encode(['error' => $hfError, 'details' => $result]);
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