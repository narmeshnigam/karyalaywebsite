<?php
/**
 * Environment Detection Test Script
 * 
 * This script tests the environment detection functionality
 * to ensure it works correctly on both localhost and production.
 */

// Bootstrap the application
require_once __DIR__ . '/../config/bootstrap.php';

use Karyalay\Services\InstallationService;

$installationService = new InstallationService();

// Get environment information
$envInfo = $installationService->getEnvironmentInfo();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Environment Detection Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 15px;
            margin: 20px 0;
        }
        .info-label {
            font-weight: 600;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
        }
        .badge-localhost {
            background: #dbeafe;
            color: #1e40af;
        }
        .badge-production {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-yes {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-no {
            background: #fee2e2;
            color: #991b1b;
        }
        .warning-box {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning-box.high {
            background: #fef2f2;
            border-color: #ef4444;
        }
        .warning-box.low {
            background: #eff6ff;
            border-color: #3b82f6;
        }
        .warning-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .test-section {
            margin: 30px 0;
            padding: 20px;
            background: #f9fafb;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Environment Detection Test</h1>
        
        <div class="test-section">
            <h2>Environment Information</h2>
            <div class="info-grid">
                <div class="info-label">Environment:</div>
                <div class="info-value">
                    <span class="badge badge-<?php echo $envInfo['is_localhost'] ? 'localhost' : 'production'; ?>">
                        <?php echo $envInfo['environment']; ?>
                    </span>
                </div>
                
                <div class="info-label">Is Localhost:</div>
                <div class="info-value">
                    <span class="badge badge-<?php echo $envInfo['is_localhost'] ? 'yes' : 'no'; ?>">
                        <?php echo $envInfo['is_localhost'] ? 'Yes' : 'No'; ?>
                    </span>
                </div>
                
                <div class="info-label">Is Production:</div>
                <div class="info-value">
                    <span class="badge badge-<?php echo $envInfo['is_production'] ? 'yes' : 'no'; ?>">
                        <?php echo $envInfo['is_production'] ? 'Yes' : 'No'; ?>
                    </span>
                </div>
                
                <div class="info-label">Server Software:</div>
                <div class="info-value"><?php echo htmlspecialchars($envInfo['server_software']); ?></div>
                
                <div class="info-label">PHP Version:</div>
                <div class="info-value"><?php echo htmlspecialchars($envInfo['php_version']); ?></div>
                
                <div class="info-label">Operating System:</div>
                <div class="info-value"><?php echo htmlspecialchars($envInfo['php_os']); ?></div>
                
                <div class="info-label">Is Windows:</div>
                <div class="info-value">
                    <span class="badge badge-<?php echo $envInfo['is_windows'] ? 'yes' : 'no'; ?>">
                        <?php echo $envInfo['is_windows'] ? 'Yes' : 'No'; ?>
                    </span>
                </div>
                
                <div class="info-label">Is HTTPS:</div>
                <div class="info-value">
                    <span class="badge badge-<?php echo $envInfo['is_https'] ? 'yes' : 'no'; ?>">
                        <?php echo $envInfo['is_https'] ? 'Yes' : 'No'; ?>
                    </span>
                </div>
                
                <div class="info-label">Server Name:</div>
                <div class="info-value"><?php echo htmlspecialchars($envInfo['server_name']); ?></div>
                
                <div class="info-label">Document Root:</div>
                <div class="info-value"><?php echo htmlspecialchars($envInfo['document_root']); ?></div>
            </div>
        </div>
        
        <?php if (!empty($envInfo['warnings'])): ?>
        <div class="test-section">
            <h2>Environment Warnings</h2>
            <?php foreach ($envInfo['warnings'] as $warning): ?>
                <div class="warning-box <?php echo $warning['severity']; ?>">
                    <div class="warning-title">
                        <?php echo ucfirst($warning['severity']); ?> - <?php echo ucfirst($warning['type']); ?>
                    </div>
                    <div><?php echo htmlspecialchars($warning['message']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="test-section">
            <h2>Environment Warnings</h2>
            <p style="color: #10b981;">✓ No warnings detected</p>
        </div>
        <?php endif; ?>
        
        <div class="test-section">
            <h2>File Permissions Test</h2>
            <div class="info-grid">
                <div class="info-label">Config Files:</div>
                <div class="info-value">
                    <?php 
                    $configPerms = $installationService->getEnvironmentPermissions('config');
                    echo '0' . decoct($configPerms);
                    ?>
                </div>
                
                <div class="info-label">Upload Directories:</div>
                <div class="info-value">
                    <?php 
                    $uploadPerms = $installationService->getEnvironmentPermissions('upload');
                    echo '0' . decoct($uploadPerms);
                    ?>
                </div>
                
                <div class="info-label">General Files:</div>
                <div class="info-value">
                    <?php 
                    $generalPerms = $installationService->getEnvironmentPermissions('general');
                    echo '0' . decoct($generalPerms);
                    ?>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h2>Path Resolution Test</h2>
            <div class="info-grid">
                <div class="info-label">Config Path:</div>
                <div class="info-value">
                    <?php echo htmlspecialchars($installationService->getEnvironmentPath('config')); ?>
                </div>
                
                <div class="info-label">Uploads Path:</div>
                <div class="info-value">
                    <?php echo htmlspecialchars($installationService->getEnvironmentPath('uploads')); ?>
                </div>
                
                <div class="info-label">Install Path:</div>
                <div class="info-value">
                    <?php echo htmlspecialchars($installationService->getEnvironmentPath('install')); ?>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h2>Server Variables</h2>
            <div class="info-grid">
                <div class="info-label">SERVER_NAME:</div>
                <div class="info-value"><?php echo htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'Not set'); ?></div>
                
                <div class="info-label">SERVER_ADDR:</div>
                <div class="info-value"><?php echo htmlspecialchars($_SERVER['SERVER_ADDR'] ?? 'Not set'); ?></div>
                
                <div class="info-label">REMOTE_ADDR:</div>
                <div class="info-value"><?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'Not set'); ?></div>
                
                <div class="info-label">HTTP_HOST:</div>
                <div class="info-value"><?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Not set'); ?></div>
                
                <div class="info-label">SERVER_PORT:</div>
                <div class="info-value"><?php echo htmlspecialchars($_SERVER['SERVER_PORT'] ?? 'Not set'); ?></div>
                
                <div class="info-label">HTTPS:</div>
                <div class="info-value"><?php echo htmlspecialchars($_SERVER['HTTPS'] ?? 'Not set'); ?></div>
            </div>
        </div>
    </div>
</body>
</html>
