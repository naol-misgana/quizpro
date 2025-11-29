<?php
require_once 'config.php';
require_once 'functions.php';

// User registration
function registerUser($userData) {
    global $pdo;
    
    $username = sanitize($userData['username']);
    $email = sanitize($userData['email']);
    $password = password_hash($userData['password'], PASSWORD_DEFAULT);
    $full_name = sanitize($userData['full_name']);
    $phone = sanitize($userData['phone']);
    $institution = sanitize($userData['institution']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, institution) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $full_name, $phone, $institution]);
        return $pdo->lastInsertId();
    } catch(PDOException $e) {
        return false;
    }
}

// User login
function loginUser($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = TRUE");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['payment_status'] = $user['payment_status'];
        $_SESSION['full_name'] = $user['full_name'];
        
        return true;
    }
    
    return false;
}

// Admin login
function loginAdmin($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND user_type = 'admin' AND is_active = TRUE");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['full_name'] = $user['full_name'];
        
        return true;
    }
    
    return false;
}

// Logout
function logout() {
    session_destroy();
    redirect('index.php');
}

// Enhanced login with brute force protection
function loginUserWithProtection($username, $password) {
    global $pdo;
    
    // Check for failed attempts in the last 30 minutes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE username = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND success = 0");
    $stmt->execute([$username]);
    $failed_attempts = $stmt->fetchColumn();
    
    if ($failed_attempts >= 5) {
        return false; // Too many failed attempts
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = TRUE");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Record successful attempt
        $stmt = $pdo->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, 1)");
        $stmt->execute([$username, $_SERVER['REMOTE_ADDR']]);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['payment_status'] = $user['payment_status'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['last_login'] = time();
        
        return true;
    } else {
        // Record failed attempt
        $stmt = $pdo->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, 0)");
        $stmt->execute([$username, $_SERVER['REMOTE_ADDR']]);
        
        return false;
    }
}

// CSRF protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Input validation
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    return preg_match('/^[0-9+\-\s()]{10,20}$/', $phone);
}

function sanitizeFileName($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9\.\-\_]/', '', $filename);
    return time() . '_' . $filename;
}
?>