<?php
/**
 * API: Search subscriptions for port assignment
 * Returns active subscriptions without a port assigned
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

try {
    $db = \Karyalay\Database\Connection::getInstance();
    $query = $_GET['q'] ?? '';
    $currentPortId = $_GET['current_port_id'] ?? '';

    // Search for active subscriptions without a port (or with the current port)
    $sql = "SELECT s.id, s.status, s.start_date, s.end_date,
                   u.name as customer_name, u.email as customer_email,
                   p.name as plan_name
            FROM subscriptions s
            JOIN users u ON s.customer_id = u.id
            JOIN plans p ON s.plan_id = p.id
            WHERE s.status IN ('ACTIVE', 'PENDING_ALLOCATION')
            AND (s.assigned_port_id IS NULL";
    
    $params = [];
    
    // Also include the subscription that currently has this port
    if (!empty($currentPortId)) {
        $sql .= " OR s.assigned_port_id = :current_port_id";
        $params[':current_port_id'] = $currentPortId;
    }
    
    $sql .= ")";
    
    // Add search filter
    if (!empty($query)) {
        $sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR p.name LIKE :search)";
        $params[':search'] = '%' . $query . '%';
    }
    
    $sql .= " ORDER BY u.name ASC LIMIT 20";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($subscriptions);
    
} catch (PDOException $e) {
    error_log("Subscription search API error: " . $e->getMessage());
    error_log("SQL: " . ($sql ?? 'N/A'));
    error_log("Params: " . json_encode($params ?? []));
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Subscription search API general error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
