<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'quiz_platform');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_NAME', 'CampusGateway Quiz Platform');
define('SITE_URL', 'http://localhost/quiz_platform');

// Payment configuration
define('PAYMENT_AMOUNT', 100.00); // ETB
define('PAYMENT_CURRENCY', 'ETB');

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>