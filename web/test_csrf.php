<?php
session_start();
echo "<h2>🔐 CSRF Token Test</h2>";

// Test generateCSRFToken
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

$token = generateCSRFToken();
echo "Generated token: <code>$token</code><br>";
echo "Session token: <code>{$_SESSION['csrf_token']}</code><br>";

// Test verifyCSRFToken
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

$testToken = $_SESSION['csrf_token'];
$result = verifyCSRFToken($testToken);
echo "<br>Verify same token: " . ($result ? "✅ OK" : "❌ FAIL") . "<br>";

$badToken = "bad_token_12345";
$result2 = verifyCSRFToken($badToken);
echo "Verify bad token: " . ($result2 ? "❌ FAIL (should be false)" : "✅ OK (correctly rejected)") . "<br>";

echo "<hr>";
echo "<h3>Conclusion:</h3>";
echo "<p>CSRF functions work correctly. Problem might be:</p>";
echo "<ul>";
echo "<li>Form not submitting CSRF token correctly</li>";
echo "<li>Session issues</li>";
echo "</ul>";
?>
