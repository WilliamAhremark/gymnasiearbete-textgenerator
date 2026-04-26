<?php

function loadEnvFile(string $path): void {
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || strpos($trimmed, '#') === 0 || strpos($trimmed, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            continue;
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}

loadEnvFile(__DIR__ . '/.env.local');
loadEnvFile(__DIR__ . '/.env');

function envValue(string $key, ?string $default = null): ?string {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string)$value;
}

function isCurrentRequestHttps(): bool {
    return (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || ((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    );
}

// Configure secure session cookies before starting the session.
// In HTTPS mode, this prevents cookies from being sent over plaintext HTTP.
$isHttps = isCurrentRequestHttps();

if (PHP_SAPI !== 'cli') {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

session_start();

$host = envValue('MYSQLHOST', '127.0.0.1');
$dbname = envValue('MYSQLDATABASE', 'ai_project_db');
$user = envValue('MYSQLUSER', 'root');
$pass = envValue('MYSQLPASSWORD', '');
$port = envValue('MYSQLPORT', '3306');

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

function requireAdmin(): void {
    if (!isLoggedIn() || !isAdmin()) {
        http_response_code(403);
        die('Access denied');
    }
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
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
if ($scriptDir === '.' || $scriptDir === '') {
    $scriptDir = '/';
}
if (substr($scriptDir, -1) !== '/') {
    $scriptDir .= '/';
}
define('BASE_URL', $scriptDir);

/**
 * Returns true if the current user is an admin.
 */
function isAdmin(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getSiteUrl(): string {
    $configured = envValue('APP_URL', '');
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $scheme = isCurrentRequestHttps() ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = defined('BASE_URL') ? BASE_URL : '/';

    return rtrim($scheme . '://' . $host . $scriptDir, '/');
}

function getMailerConfig(): array {
    return [
        'host' => envValue('MAIL_HOST', ''),
        'port' => (int)envValue('MAIL_PORT', '587'),
        'secure' => envValue('MAIL_SECURE', 'tls'),
        'username' => envValue('MAIL_USERNAME', ''),
        'password' => envValue('MAIL_PASSWORD', ''),
        'from' => envValue('MAIL_FROM', envValue('MAIL_USERNAME', '')),
        'from_name' => envValue('MAIL_FROM_NAME', 'NeuralText AI'),
    ];
}

function isMailerConfigured(): bool {
    $config = getMailerConfig();
    return $config['host'] !== '' && $config['username'] !== '' && $config['password'] !== '';
}

function createVerificationToken(int $userId): array {
    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);
    $expiresAt = (new DateTimeImmutable('+24 hours'))->format('Y-m-d H:i:s');

    return [
        'raw' => $rawToken,
        'hash' => $tokenHash,
        'expires_at' => $expiresAt,
        'user_id' => $userId,
    ];
}

function sendVerificationEmail(string $email, string $username, string $rawToken, int $userId): array {
    if (!isMailerConfigured()) {
        return [false, 'E-postverifiering är inte konfigurerad ännu.'];
    }

    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        return [false, 'PHPMailer kunde inte laddas.'];
    }

    require_once $autoload;

    try {
        $config = getMailerConfig();
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->Port = $config['port'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['secure'];
        $mail->setFrom($config['from'], $config['from_name']);
        $mail->addAddress($email, $username);
        $mail->isHTML(true);
        $mail->Subject = 'Bekräfta din e-postadress';

        $verifyUrl = getSiteUrl() . '/verify_email.php?uid=' . urlencode((string)$userId) . '&token=' . urlencode($rawToken);
        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8');

        $mail->Body = '<h2>Välkommen, ' . $safeUsername . '!</h2>'
            . '<p>Klicka på länken nedan för att bekräfta din e-postadress och aktivera kontot.</p>'
            . '<p><a href="' . $safeUrl . '">Bekräfta e-postadress</a></p>'
            . '<p>Länken är giltig i 24 timmar.</p>';
        $mail->AltBody = "Hej $username\n\nBekräfta din e-postadress: $verifyUrl\n\nLänken gäller i 24 timmar.";

        $mail->send();
        return [true, 'Verifieringsmejlet har skickats.'];
    } catch (Throwable $e) {
        error_log('Verification email error: ' . $e->getMessage());
        return [false, 'Kunde inte skicka verifieringsmejl.'];
    }
}

?>
