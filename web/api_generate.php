<?php

require_once 'config.php';

header('Content-Type: application/json');

$token = trim(getenv('HF_TOKEN') ?: getenv('HF_API_TOKEN') ?: '');
$aiApiUrl = trim(getenv('AI_API_URL') ?: '');
// Default model list expanded with more reliable free-tier options.
$primaryModel = trim(getenv('HF_MODEL') ?: 'gpt2');
$fallbackModelsEnv = trim(getenv('HF_FALLBACK_MODELS') ?: 'distilgpt2,EleutherAI/gpt-neo-125M');

// Validate that the token looks like a real HuggingFace token (starts with "hf_").
$tokenValid = $token !== '' && str_starts_with($token, 'hf_');

if (!$tokenValid && $aiApiUrl === '') {
    http_response_code(500);
    $tokenHint = $token === ''
        ? 'HF_TOKEN is not set'
        : 'HF_TOKEN does not look valid (expected it to start with "hf_")';
    echo json_encode([
        'error' => 'Missing or invalid AI configuration',
        'hint'  => "{$tokenHint}. Set HF_TOKEN to a valid Hugging Face token, or set AI_API_URL to a custom inference endpoint.",
    ]);
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
$authBlocked = false;

if ($tokenValid) {
    foreach ($models as $modelName) {
        $encodedModel = rawurlencode($modelName);
        // Try the HuggingFace Inference API (router endpoint).
        $endpoints = [
            "https://router.huggingface.co/hf-inference/models/{$encodedModel}"
        ];

        foreach ($endpoints as $url) {
            // Up to 3 attempts per endpoint: handles model-loading (503) and transient errors.
            for ($retry = 0; $retry < 3; $retry++) {
                [$httpCode, $response, $curlError] = requestJson(
                    $url,
                    $payload,
                    [
                        "Authorization: Bearer {$token}",
                        "Content-Type: application/json",
                        "Accept: application/json",
                        "X-Wait-For-Model: true"
                    ]
                );
                $lastHttpCode = $httpCode > 0 ? $httpCode : 502;

                if ($curlError !== '') {
                    $attemptErrors[] = "{$modelName} @ {$url}: curl error — {$curlError}";
                    break;
                }

                $parsed = json_decode((string)$response, true);
                $jsonError = json_last_error();
                $isJson = $jsonError === JSON_ERROR_NONE;

                if ($httpCode >= 200 && $httpCode < 300) {
                    if ($isJson) {
                        $result = $parsed;
                    } else {
                        $result = ["text" => trim((string)$response)];
                    }
                    break 3;
                }

                if (!$isJson) {
                    $snippet = trim((string)$response);
                    if (strlen($snippet) > 220) {
                        $snippet = substr($snippet, 0, 220) . '...';
                    }
                    $attemptErrors[] = "{$modelName} @ {$url}: non-JSON response (HTTP {$httpCode}) — {$snippet}";
                    break;
                }

                $errorText = is_array($parsed) ? ($parsed['error'] ?? ('HTTP ' . $httpCode)) : ('HTTP ' . $httpCode);

                // Build a human-readable hint for common failure codes.
                $hint = '';
                if ($httpCode === 401) {
                    $hint = 'Token rejected — check that HF_TOKEN is a valid, non-expired Hugging Face token.';
                } elseif ($httpCode === 403) {
                    $hint = 'Access denied — the token may lack permission for this model, or the model is gated.';
                } elseif ($httpCode === 404) {
                    $hint = "Model \"{$modelName}\" not found on the HuggingFace router. It may have been removed or renamed.";
                } elseif ($httpCode === 503) {
                    // Model is still loading; estimated_time may be present.
                    $estimatedTime = is_array($parsed) ? (int)($parsed['estimated_time'] ?? 20) : 20;
                    $waitMs = min($estimatedTime * 1000000, 5000000); // cap at 5 s
                    $hint = "Model is loading (estimated {$estimatedTime}s). Retrying after short wait…";
                    $attemptErrors[] = "{$modelName} @ {$url}: {$errorText} — {$hint}";
                    if ($retry < 2) {
                        usleep($waitMs);
                        continue;
                    }
                    break;
                }

                $logEntry = "{$modelName} @ {$url}: HTTP {$httpCode} — {$errorText}";
                if ($hint !== '') {
                    $logEntry .= " | {$hint}";
                }
                $attemptErrors[] = $logEntry;

                // Token auth failures should stop HF attempts, but still allow downstream fallbacks.
                if (in_array($httpCode, [401, 403], true)) {
                    $authBlocked = true;
                    break 3;
                }

                // 404 means this model is unavailable on the router; skip to next model.
                if ($httpCode === 404) {
                    break;
                }

                // Retry on transient rate-limit / server errors.
                if ($retry < 2 && in_array($httpCode, [408, 409, 425, 429, 500, 502, 504], true)) {
                    usleep(500000); // 0.5 s
                    continue;
                }

                break;
            }
        }
    }
}

// Secondary fallback: a custom deployed inference service (AI_API_URL).
if ($result === null && $aiApiUrl !== '') {
    [$httpCode, $response, $curlError] = requestJson(
        $aiApiUrl,
        $internalPayload,
        ["Content-Type: application/json"],
        120
    );
    $lastHttpCode = $httpCode > 0 ? $httpCode : $lastHttpCode;

    if ($curlError !== '') {
        $attemptErrors[] = "AI_API_URL {$aiApiUrl}: curl error — {$curlError}";
    } else {
        $parsed = json_decode((string)$response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $attemptErrors[] = "AI_API_URL {$aiApiUrl}: invalid JSON in response";
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

if ($result === null && $authBlocked) {
    $attemptErrors[] = 'HF token auth failed; remote HF generation was skipped after authentication rejection.';
}

// Tertiary fallback: local pattern-based text generation (ai-fallback.php).
if ($result === null) {
    $fallbackFile = __DIR__ . '/ai-fallback.php';
    if (file_exists($fallbackFile)) {
        $fallbackResult = null;
        try {
            // Call the fallback script in-process by including it with isolated scope.
            $fallbackResult = (function () use ($prompt, $length, $fallbackFile): ?array {
                return include $fallbackFile;
            })();
        } catch (Throwable $e) {
            $attemptErrors[] = "local fallback: exception — " . $e->getMessage();
        }

        if (is_array($fallbackResult) && isset($fallbackResult['text']) && trim($fallbackResult['text']) !== '') {
            $result = $fallbackResult;
            $result['_source'] = 'local-fallback';
        } else {
            $attemptErrors[] = 'local fallback: returned no usable text';
        }
    }
}

if ($result === null) {
    http_response_code($lastHttpCode >= 400 ? $lastHttpCode : 502);
    echo json_encode([
        'error'    => 'All configured AI providers failed',
        'attempts' => $attemptErrors,
        'hint'     => 'Check that HF_TOKEN is set to a valid Hugging Face token (starts with "hf_") and that the chosen models are accessible with a free-tier account.',
    ]);
    exit;
}

$text = maybeExtractText($result);

if (trim($text) === '') {
    http_response_code(502);
    echo json_encode(['error' => 'Model returned empty text', 'details' => $result]);
    exit;
}

$responsePayload = ["text" => $text];
// Surface the fallback source so callers can show a notice if needed.
if (isset($result['_source'])) {
    $responsePayload['_source'] = $result['_source'];
}

echo json_encode($responsePayload);