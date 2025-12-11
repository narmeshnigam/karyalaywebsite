<?php
/**
 * Debug Plans - Check what's in the database
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/bootstrap.php';

use Karyalay\Database\Connection;

$db = Connection::getInstance();

echo "<h1>Plans Debug</h1>";
echo "<style>
    body { font-family: monospace; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f0f0f0; }
    .highlight { background: #ffffcc; }
</style>";

try {
    $stmt = $db->query("SELECT * FROM plans ORDER BY created_at DESC");
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Total Plans: " . count($plans) . "</h2>";
    
    if (empty($plans)) {
        echo "<p>No plans found in database.</p>";
    } else {
        echo "<table>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Name</th>";
        echo "<th>Slug</th>";
        echo "<th>MRP</th>";
        echo "<th>Discounted</th>";
        echo "<th>Currency</th>";
        echo "<th>Billing Period</th>";
        echo "<th>Users</th>";
        echo "<th>Storage</th>";
        echo "<th>Status</th>";
        echo "<th>Created</th>";
        echo "</tr>";
        
        foreach ($plans as $plan) {
            echo "<tr>";
            echo "<td class='highlight'>" . htmlspecialchars(substr($plan['id'], 0, 8)) . "...</td>";
            echo "<td class='highlight'>" . htmlspecialchars($plan['name']) . "</td>";
            echo "<td class='highlight'>" . htmlspecialchars($plan['slug']) . "</td>";
            echo "<td>" . htmlspecialchars($plan['mrp'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($plan['discounted_price'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($plan['currency']) . "</td>";
            echo "<td>" . htmlspecialchars($plan['billing_period_months']) . "</td>";
            echo "<td>" . htmlspecialchars($plan['number_of_users'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($plan['allowed_storage_gb'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($plan['status']) . "</td>";
            echo "<td>" . htmlspecialchars($plan['created_at']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Show full details
        echo "<h2>Full Plan Details</h2>";
        foreach ($plans as $i => $plan) {
            echo "<h3>Plan " . ($i + 1) . ": " . htmlspecialchars($plan['name']) . "</h3>";
            echo "<pre>";
            print_r($plan);
            echo "</pre>";
            echo "<hr>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
