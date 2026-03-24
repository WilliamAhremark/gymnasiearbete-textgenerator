<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug - Gymnasiearbete Database</h2>";

// Test database connection
$host = "localhost";
$dbname = "ai_project_db";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo "<p>✅ Databasanslutning OK</p>";
    
    // Kolla om databas finns
    $stmt = $pdo->query("SHOW DATABASES LIKE 'ai_project_db'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Databasen 'ai_project_db' finns</p>";
    } else {
        echo "<p>❌ Databasen 'ai_project_db' finns INTE!</p>";
        echo "<p>Skapa den med: CREATE DATABASE ai_project_db;</p>";
        exit;
    }
    
    // Kolla om users-tabellen finns
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Tabellen 'users' finns</p>";
    } else {
        echo "<p>❌ Tabellen 'users' finns INTE! Kör schema.sql igen.</p>";
        exit;
    }
    
    // Lista alla användare
    $stmt = $pdo->query("SELECT id, email, username, role FROM users");
    $users = $stmt->fetchAll();
    
    echo "<h3>📋 Användare i databasen:</h3>";
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Email</th><th>Username</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ Inga användare finns! Kör INSERT-kommandot igen.</p>";
    }
    
    // Uppdatera lösenord
    echo "<hr><h3>🔧 Fixar lösenord...</h3>";
    
    $adminPassword = 'Admin123!';
    $userPassword = 'User123!';
    
    $adminHash = password_hash($adminPassword, PASSWORD_BCRYPT);
    $userHash = password_hash($userPassword, PASSWORD_BCRYPT);
    
    // Uppdatera eller skapa admin
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'admin@ai-project.com'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@ai-project.com'");
        $stmt->execute([$adminHash]);
        echo "<p>✅ Admin-lösenord uppdaterat</p>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (email, password, username, role, is_verified) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin@ai-project.com', $adminHash, 'admin', 'admin', 1]);
        echo "<p>✅ Admin-användare skapad</p>";
    }
    
    // Uppdatera eller skapa user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'user@example.com'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'user@example.com'");
        $stmt->execute([$userHash]);
        echo "<p>✅ User-lösenord uppdaterat</p>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (email, password, username, role, is_verified) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['user@example.com', $userHash, 'testuser', 'user', 1]);
        echo "<p>✅ User-användare skapad</p>";
    }
    
    echo "<hr>";
    echo "<h3>✅ KLART!</h3>";
    echo "<p><strong>Inloggningsuppgifter:</strong></p>";
    echo "<ul>";
    echo "<li>Admin: <code>admin@ai-project.com</code> / <code>Admin123!</code></li>";
    echo "<li>User: <code>user@example.com</code> / <code>User123!</code></li>";
    echo "</ul>";
    echo "<p><a href='login.php' style='background: #4361ee; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Gå till inloggning</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ FEL: " . $e->getMessage() . "</p>";
}
?>
