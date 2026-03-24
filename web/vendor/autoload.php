<?php
// Enkel autoloader för PHPMailer (fallback när Composer inte fungerar)
spl_autoload_register(function ($class) {
    $prefix = 'PHPMailer\\PHPMailer\\';
    if (strpos($class, $prefix) === 0) {
        $relative = substr($class, strlen($prefix));
        $file = __DIR__ . '/../vendor/phpmailer/phpmailer/src/' . $relative . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});
