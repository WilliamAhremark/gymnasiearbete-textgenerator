<?php
require_once 'config.php';
requireLogin();

$token = getenv('HF_API_TOKEN');

if (!$token) {
    http_response_code(500);
    echo json_encode(["error" => "HF_API_TOKEN missing"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$prompt = $data['prompt'] ?? '';
$length = $data['length'] ?? 100;

$payload = [
    "inputs" => $prompt,
    "parameters" => [
        "max_new_tokens" => (int)$length
    ]
];

$ch = curl_init("https://api-inference.huggingface.co/models/distilgpt2");

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
curl_close($ch);

echo $response;