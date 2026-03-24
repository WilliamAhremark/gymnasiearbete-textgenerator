<?php
/**
 * KONFIGURATION OCH SÄKERHETSFUNKTIONER
 * 
 * Denna fil hanterar:
 * - Session-konfiguration med moderna säkerhetsinställningar
 * - Databasanslutning via PDO (prepared statements mot SQL-injektion)
 * - Autentiserings- och valideringsfunktioner
 * - CSRF-skydd för formulär
 * 
 * SÄKERHETSPRINCIPER:
 * - Alla SQL-frågor använder PDO prepared statements (förhindrar SQL-injektion)
 * - Sessions konfigurerade med httponly flag (förhindrar XSS-attacker frånJS)
 * - use_strict_mode aktiverad (förhindrar session fixation)
 * - Password-hashing med bcrypt (aldrig plaintext)
 * - CSRF-tokens för hålla alla POST-operationer
 */

/**
 * SESSION-KONFIGURATION
 * 
 * VARFÖR DESSA INSTÄLLNINGAR:
 * - cookie_lifetime=86400: Sessions varar 24 timmar (säker timeout för gymnasiearbete)
 * - cookie_httponly=true: KRITISK säkerhet - cookie kan INTE nås från JavaScript.
 *   Detta förhindrar XSS-attackers från att stjäla sessionscookies
 * - use_strict_mode=true: Accepterar ENDAST befintliga session IDs, förhindrar
 *   session fixation-attacker där motståndare ställer in sessionID
 */
session_start([
    'cookie_lifetime' => 86400,     // 24 timmar session-livslängd
    'cookie_httponly' => true,      // Förhindrar XSS-attacker från att stjäla cookies
    'use_strict_mode' => true       // Förhindrar session fixation-attacker
]);

// Aktivera all error-rapportering under utveckling (bör disableas i produktion)
$appEnv = getenv('APP_ENV') ?: 'development';
if ($appEnv === 'production') {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

/**
 * BASURL FÖR LINKAR
 * 
 * Definierar root-sökvägen för webbservern. Använd denna konstant
 * för att skapa absoluta links så att de är korrekta oavsett undersida
 */
$baseUrl = getenv('BASE_URL') ?: '/GYMNASIEARBETE1/web/';
define('BASE_URL', $baseUrl);

/**
 * DATABASKONFIGURATION
 * 
 * VARFÖR PDO + PREPARED STATEMENTS:
 * PDO (PHP Data Objects) tillsammans med prepared statements förhindrar SQL-injektion.
 * Istället för att bygga SQL-strängar direkt, lösgör vi data från SQL-kod:
 * 
 * UNSAFE (SÅRBART):    $sql = "SELECT * FROM users WHERE email = '" . $_GET['email'] . "'";
 * SÄKERT (FÖORDE):     $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
 *                      $stmt->execute([$_GET['email']]);
 * 
 * Prepared statements gör att databasen ALDRIG tolkar användarinput som SQL-kod
 */

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'ai_project_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$dbPort = getenv('DB_PORT') ?: null;

// Detektera om vi använder Supabase PostgreSQL eller traditionell MySQL
$isSupabase = strpos($host, 'supabase.co') !== false;
$isPostgres = $isSupabase || getenv('DB_TYPE') === 'postgres';

try {
    if ($isPostgres) {
        // PostgreSQL-anslutning (för Supabase eller andra PostgreSQL-servrar)
        $port = $dbPort ?: 5432;
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
        $pdo = new PDO(
            $dsn,
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Kasta errors som exceptions
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC    // Returnera associativa arrayer
            ]
        );
    } else {
        // MySQL-anslutning (traditionell databaskonfiguration)
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        if ($dbPort) {
            $dsn .= ";port=$dbPort";
        }
        $pdo = new PDO(
            $dsn,
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Kasta errors som exceptions
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC    // Returnera associativa arrayer
            ]
        );
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


function isLoggedIn() {
    
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit;
    }
}

function sanitizeInput($input) {
    
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// csrf-skydd, för att stoppa hacks
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    // kollar att tokenen är rätt
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}


function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}


function checkPasswordStrength($password) {
    $errors = [];
    
   
    if (strlen($password) < 8) {
        $errors[] = "minst 8 tecken";
    }
    
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "minst en stor bokstav";
    }
    
   
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "minst en liten bokstav";
    }
    
  
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "minst en siffra";
    }
    
    
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = "minst ett specialtecken (!@#$%^&* etc.)";
    }
    
    return $errors;
}

// messages som visas en gång (t.ex efter redirect)
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}
?>