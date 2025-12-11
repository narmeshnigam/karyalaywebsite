<?php
/**
 * API: Search Plans
 * Returns active plans matching search query
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
        // Return all active plans when no query
        $sql = "SELECT id, name, mrp, discounted_price, currency, billing_period_months 
                FROM plans 
                WHERE status = 'ACTIVE'
                ORDER BY name ASC
                LIMIT 20";
        $stmt = $db->query($sql);
    } else {
        // Search by name
        $sql = "SELECT id, name, mrp, discounted_price, currency, billing_period_months 
                FROM plans 
                WHERE status = 'ACTIVE'
                AND name LIKE :query
                ORDER BY name ASC
                LIMIT 10";
        $stmt = $db->prepare($sql);
        $stmt->execute([':query' => '%' . $query . '%']);
    }
    
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($plans);
} catch (PDOException $e) {
    error_log("Plan search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Search failed']);
}
