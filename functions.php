<?php
require_once 'config.php';

// Enhanced access control functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user_type'] === 'admin';
}

function isRegularUser() {
    return isLoggedIn() && $_SESSION['user_type'] === 'user';
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        redirect('../login.php');
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error'] = 'Access denied. Administrator privileges required.';
        redirect('../index.php');
    }
}

function requireUser() {
    requireLogin();
    if (!isRegularUser()) {
        $_SESSION['error'] = 'Access denied. User account required.';
        redirect('../index.php');
    }
}

function requirePaymentVerified() {
    requireUser();
    if (!isPaymentVerified()) {
        $_SESSION['error'] = 'You need to have your payment verified to access this feature.';
        redirect('payment.php');
    }
}

// Check if user payment is verified
function isPaymentVerified() {
    global $pdo;
    if (!isLoggedIn()) return false;
    
    $stmt = $pdo->prepare("SELECT payment_status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    return $user && $user['payment_status'] === 'verified';
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Get user data
function getUserData($user_id = null) {
    global $pdo;
    $user_id = $user_id ?: $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Format date
function formatDate($date) {
    return date('M j, Y g:i A', strtotime($date));
}

// Calculate percentage
function calculatePercentage($part, $total) {
    return $total > 0 ? round(($part / $total) * 100) : 0;
}
?>