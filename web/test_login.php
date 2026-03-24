<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$dbname = "ai_project_db";
$username = "root";
$password = "";

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

echo "<h2>🧪 Test Inloggning</h2>";

$testEmail = 'admin@ai-project.com';
$testPassword = 'Admin123!';

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$testEmail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Användare från databas:</h3>";
echo "<pre>" . print_r($user, true) . "</pre>";

echo "<h3>Password Verify Test:</h3>";
echo "Input password: <code>$testPassword</code><br>";
echo "Hash från DB: <code>{$user['password']}</code><br>";

$verified = password_verify($testPassword, $user['password']);
echo "<br>Result: " . ($verified ? "✅ MATCH!" : "❌ NO MATCH") . "<br>";

if ($verified) {
    echo "<p style='color: green; font-size: 20px;'>✅ Lösenordet fungerar! Problemet är något annat i login.php</p>";
} else {
    echo "<p style='color: red; font-size: 20px;'>❌ Lösenordet matchar inte hashen!</p>";
}
?>
