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

$prompt = $data['prompt'] ?? '';
$length = $data['length'] ?? 120;

$payload = json_encode([
    "inputs" => $prompt,
    "parameters" => [
        "max_new_tokens" => (int)$length
    ]
]);

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://api-inference.huggingface.co/models/distilgpt2");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)]);
    exit;
}

curl_close($ch);

$result = json_decode($response, true);

$text = $result[0]['generated_text'] ?? 'No response';

echo json_encode([
    "text" => $text
]);