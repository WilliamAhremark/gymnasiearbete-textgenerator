<?php

session_start();

$host = getenv('MYSQLHOST');
$dbname = getenv('MYSQLDATABASE');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');
$port = getenv('MYSQLPORT') ?: 3306;

if (!$host) {
    die("MYSQLHOST missing");
}

try {

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

    $pdo = new PDO(
        $dsn,
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

} catch (PDOException $e) {

    die("Database connection failed: " . $e->getMessage());

}

/**
 * Generates a CSRF token and stores it in the session.
 * Returns the token so it can be embedded in forms.
 */
function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies that the provided CSRF token matches the one stored in the session.
 */
function verifyCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitizes user input to prevent XSS by stripping tags and encoding special characters.
 */
function sanitizeInput(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validates that the given string is a properly formatted email address.
 */
function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Checks password strength and returns an array of unmet requirements.
 * An empty array means the password satisfies all requirements.
 */
function checkPasswordStrength(string $password): array {
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'at least 8 characters';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'at least one uppercase letter';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'at least one lowercase letter';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'at least one number';
    }
    if (!preg_match('/[!@#$%^&*()\-_=+\[\]{};:\'",.<>?\/\\\\|`~]/', $password)) {
        $errors[] = 'at least one special character';
    }

    return $errors;
}

/**
 * Returns true if the current visitor has an active authenticated session.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Redirects to the login page if the visitor is not authenticated.
 * Stores the originally requested URL so the user can be sent there after login.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'] ?? 'dashboard.php';
        header('Location: login.php');
        exit;
    }
}

// Define BASE_URL for asset paths
define('BASE_URL', '/');

/**
 * Returns true if the current user is an admin.
 */
function isAdmin(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

?>
