<?php
/**
 * Database Configuration - Petrol Station Management System
 * Security: All credentials should be configured for production deployment
 */

// Database configuration - Update these for your server
define('DB_HOST', 'localhost');
define('DB_NAME', 'petrol_station');
define('DB_USER', 'daniel');
define('DB_PASS', '2050a');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please contact administrator.");
}

// Session configuration
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => false, // Set to true for HTTPS
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
    'use_cookies' => true,
]);

// Security constants
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LENGTH', 32);

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// Validate CSRF token
function validateCSRFToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Sanitize input
function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
        return $data;
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Get current user role
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit;
    }
}

// Check role permission
function hasRole($role) {
    return getCurrentUserRole() === $role;
}

// Require specific role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: ../index.php?error=unauthorized');
        exit;
    }
}

// Require any of the allowed roles
function requireRoles($roles) {
    requireLogin();
    if (!in_array(getCurrentUserRole(), $roles)) {
        header('Location: ../index.php?error=unauthorized');
        exit;
    }
}
