<?php
require_once 'config.php';

// Förstör sessionen
$_SESSION = [];

// Ta bort session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}


session_destroy();

header("Location: index.php");
exit;
?>
