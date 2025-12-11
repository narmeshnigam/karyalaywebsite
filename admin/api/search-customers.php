<?php
/**
 * API: Search Customers
 * Returns customers matching search query (by email or name)
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';

header('Content-Type: application/json');

startSecureSession();

// Check admin authentication
if (!isAdmin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$query = $_GET['q'] ?? '';

$db = \Karyalay\Database\Connection::getInstance();

try {
    if (strlen($query) === 0) {
        // Return recent customers when no query
        $sql = "SELECT id, name, email, phone 
                FROM users 
                WHERE (UPPER(role) = 'CUSTOMER' OR role IS NULL OR UPPER(role) NOT IN ('ADMIN', 'SUPPORT', 'SALES', 'CONTENT_EDITOR'))
                ORDER BY created_at DESC
                LIMIT 20";
        $stmt = $db->query($sql);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Search by email or name
        $sql = "SELECT id, name, email, phone 
                FROM users 
                WHERE (UPPER(role) = 'CUSTOMER' OR role IS NULL OR UPPER(role) NOT IN ('ADMIN', 'SUPPORT', 'SALES', 'CONTENT_EDITOR'))
                AND (email LIKE :query OR name LIKE :query)
                ORDER BY name ASC
                LIMIT 10";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':query' => '%' . $query . '%']);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($customers);
} catch (PDOException $e) {
    error_log("Customer search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Search failed']);
}
