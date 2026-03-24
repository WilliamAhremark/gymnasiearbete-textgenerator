<?php
require_once 'config.php';

echo "Resetting users table...\n\n";

try {
    // Spara admin och user först
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username IN ('admin', 'user')");
    $stmt->execute();
    $preserve = $stmt->fetchAll();
    
    echo "Found " . count($preserve) . " users to preserve (admin, user)\n\n";
    
    // Radera alla andra
    $stmt = $pdo->prepare("DELETE FROM users WHERE username NOT IN ('admin', 'user')");
    $stmt->execute();
    $deleted = $stmt->rowCount();
    
    echo "✓ Deleted $deleted user(s)\n";
    echo "✓ Kept: admin, user\n\n";
    
    echo "Users remaining:\n";
    foreach ($preserve as $user) {
        echo "  - " . $user['username'] . " (" . $user['email'] . ")\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
