<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    // Log activity before logout
    $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    $logStmt->execute([getCurrentUserId(), 'logout', 'User logged out', $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    
    // Destroy session
    session_unset();
    session_destroy();
}

header('Location: index.php');
exit;
