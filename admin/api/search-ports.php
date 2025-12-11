<?php
/**
 * API: Search Available Ports
 * Returns available ports matching search query
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
        // Return all available ports when no query
        $sql = "SELECT id, instance_url, db_name, db_host, server_region 
                FROM ports 
                WHERE status = 'AVAILABLE'
                ORDER BY instance_url ASC
                LIMIT 20";
        $stmt = $db->query($sql);
        $ports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Search by various fields
        $sql = "SELECT id, instance_url, db_name, db_host, server_region 
                FROM ports 
                WHERE status = 'AVAILABLE'
                AND (instance_url LIKE :query OR db_name LIKE :query OR db_host LIKE :query OR server_region LIKE :query)
                ORDER BY instance_url ASC
                LIMIT 10";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':query' => '%' . $query . '%']);
        $ports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($ports);
} catch (PDOException $e) {
    error_log("Port search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Search failed']);
}
