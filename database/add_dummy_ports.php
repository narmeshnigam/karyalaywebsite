<?php
/**
 * Add 20 Dummy Ports for Testing
 * 
 * This script creates 20 test ports with complete details
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Karyalay\Database\Connection;

try {
    $db = Connection::getInstance();
    
    echo "=== Adding 20 Dummy Ports for Testing ===\n\n";
    
    echo "Creating 20 dummy ports...\n\n";
    
    $regions = ['US-East', 'US-West', 'EU-Central', 'Asia-Pacific', 'UK-London'];
    $baseUrls = [
        'https://demo-instance-',
        'https://test-portal-',
        'https://staging-app-',
        'https://dev-system-',
        'https://sandbox-'
    ];
    
    $setupInstructions = [
        "1. Access your instance at the URL provided above\n2. Login with your credentials sent via email\n3. Complete the initial setup wizard\n4. Configure your business settings\n5. Start using the platform",
        "1. Click on the instance URL to access your portal\n2. Use the temporary password sent to your email\n3. Change your password on first login\n4. Set up your company profile\n5. Invite team members",
        "1. Navigate to your instance URL\n2. Enter your login credentials\n3. Follow the onboarding guide\n4. Customize your dashboard\n5. Begin managing your operations",
        "1. Open the instance URL in your browser\n2. Sign in with your account details\n3. Complete the security setup\n4. Configure system preferences\n5. Start exploring features",
        "1. Access your dedicated instance\n2. Login using your registered email\n3. Set up two-factor authentication\n4. Configure your workspace\n5. Import your data"
    ];
    
    $notes = [
        "High-performance instance with SSD storage",
        "Standard configuration with daily backups",
        "Premium tier with enhanced security features",
        "Development environment for testing",
        "Production-ready instance with monitoring",
        "Optimized for high-traffic applications",
        "Includes advanced analytics features",
        "Configured with custom domain support",
        "Enhanced with CDN integration",
        "Includes automated backup and restore"
    ];
    
    $portsAdded = 0;
    
    for ($i = 1; $i <= 20; $i++) {
        // Generate UUID
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        // Generate instance details
        $baseUrl = $baseUrls[array_rand($baseUrls)];
        $instanceNumber = str_pad($i, 3, '0', STR_PAD_LEFT);
        $instanceUrl = $baseUrl . $instanceNumber . '.sellerportal.com';
        
        $region = $regions[array_rand($regions)];
        $note = $notes[array_rand($notes)];
        $setupInstruction = $setupInstructions[array_rand($setupInstructions)];
        
        // Generate database credentials
        $dbHost = "db-server-" . strtolower(str_replace('-', '', $region)) . ".internal";
        $dbName = "portal_db_" . $instanceNumber;
        $dbUsername = "portal_user_" . $instanceNumber;
        $dbPassword = bin2hex(random_bytes(16));
        
        // Insert port
        $sql = "INSERT INTO ports (
            id, 
            instance_url, 
            status, 
            server_region, 
            notes,
            db_host,
            db_name,
            db_username,
            db_password,
            setup_instructions,
            created_at,
            updated_at
        ) VALUES (
            :id,
            :instance_url,
            'AVAILABLE',
            :server_region,
            :notes,
            :db_host,
            :db_name,
            :db_username,
            :db_password,
            :setup_instructions,
            NOW(),
            NOW()
        )";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            ':id' => $uuid,
            ':instance_url' => $instanceUrl,
            ':server_region' => $region,
            ':notes' => $note,
            ':db_host' => $dbHost,
            ':db_name' => $dbName,
            ':db_username' => $dbUsername,
            ':db_password' => $dbPassword,
            ':setup_instructions' => $setupInstruction
        ]);
        
        if ($result) {
            $portsAdded++;
            echo sprintf(
                "✓ Port %2d: %s (Region: %s)\n",
                $i,
                $instanceUrl,
                $region
            );
            echo sprintf(
                "   DB: %s/%s (User: %s)\n",
                $dbHost,
                $dbName,
                $dbUsername
            );
        } else {
            echo "✗ Failed to add port $i\n";
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "Successfully added $portsAdded out of 20 ports\n";
    
    // Show statistics
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM ports GROUP BY status");
    echo "\nPort Status Distribution:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['status']}: {$row['count']}\n";
    }
    
    $totalPorts = $db->query("SELECT COUNT(*) FROM ports")->fetchColumn();
    echo "\nTotal ports in database: $totalPorts\n";
    
    echo "\n✓ Dummy ports added successfully!\n";
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
