<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$databaseUrl = getenv("DATABASE_URL");

if (!$databaseUrl) {
    die("DATABASE_URL missing");
}

$db = parse_url($databaseUrl);

$host = $db["host"];
$port = $db["port"];
$user = $db["user"];
$pass = $db["pass"];
$name = ltrim($db["path"], "/");

$dsn = "pgsql:host=$host;port=$port;dbname=$name;sslmode=require";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}