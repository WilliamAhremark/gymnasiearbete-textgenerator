<?php
// Script för att fixa användarlösenord
require_once 'config.php';

// Generera rätt hash för Admin123!
$adminPassword = 'Admin123!';
$userPassword = 'User123!';

$adminHash = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);
$userHash = password_hash($userPassword, PASSWORD_BCRYPT, ['cost' => 12]);

echo "<h2>Uppdaterar lösenord...</h2>";

try {
    // Uppdatera admin
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@ai-project.com'");
    $stmt->execute([$adminHash]);
    echo "<p>✅ Admin-lösenord uppdaterat (Admin123!)</p>";
    
    // Uppdatera user
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'user@example.com'");
    $stmt->execute([$userHash]);
    echo "<p>✅ User-lösenord uppdaterat (User123!)</p>";
    
    echo "<hr>";
    echo "<p><strong>Inloggningsuppgifter:</strong></p>";
    echo "<p>Admin: admin@ai-project.com / Admin123!</p>";
    echo "<p>User: user@example.com / User123!</p>";
    echo "<hr>";
    echo "<p><a href='login.php'>Gå till inloggning →</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Fel: " . $e->getMessage() . "</p>";
}
?>
