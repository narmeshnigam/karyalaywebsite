<?php
/**
 * Simple test for subscription search API
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';

header('Content-Type: application/json');

startSecureSession();

// Check if we have admin session
if (!isAdmin()) {
    echo json_encode([
        'error' => 'Unauthorized',
        'authenticated' => isAuthenticated(),
        'current_user' => getCurrentUser()
    ]);
    exit;
}

try {
    $db = \Karyalay\Database\Connection::getInstance();
    
    // Simple test query
    $stmt = $db->query("SELECT COUNT(*) as count FROM subscriptions");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'subscription_count' => $result['count'],
        'user_id' => $_SESSION['user_id'],
        'role' => $_SESSION['role']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}