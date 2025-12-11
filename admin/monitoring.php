<?php
/**
 * Admin Monitoring Dashboard
 * Displays system health, logs, and performance metrics
 */

require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../classes/Services/LoggerService.php';
require_once __DIR__ . '/../classes/Services/PerformanceMonitoringService.php';

use Karyalay\Services\LoggerService;
use Karyalay\Services\PerformanceMonitoringService;

// Require admin authentication
requireAdmin();

$pageTitle = 'System Monitoring';

// Get log files
$logDir = __DIR__ . '/../storage/logs';
$logFiles = [];
if (is_dir($logDir)) {
    $files = scandir($logDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && $file !== '.gitignore') {
            $logFiles[] = [
                'name' => $file,
                'size' => filesize($logDir . '/' . $file),
                'modified' => filemtime($logDir . '/' . $file),
            ];
        }
    }
    // Sort by modified time, newest first
    usort($logFiles, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
}

// Get recent errors from today's error log
$recentErrors = [];
$errorLogFile = $logDir . '/errors-' . date('Y-m-d') . '.log';
if (file_exists($errorLogFile)) {
    $lines = file($errorLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $recentErrors = array_slice(array_reverse($lines), 0, 10);
    $recentErrors = array_map(function($line) {
        return json_decode($line, true);
    }, $recentErrors);
}

// Get performance metrics from today
$perfMetrics = [];
$perfLogFile = $logDir . '/performance-' . date('Y-m-d') . '.log';
if (file_exists($perfLogFile)) {
    $lines = file($perfLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $recentMetrics = array_slice(array_reverse($lines), 0, 5);
    foreach ($recentMetrics as $line) {
        $data = json_decode($line, true);
        if ($data) {
            $perfMetrics[] = $data;
        }
    }
}

// System information
$systemInfo = [
    'php_version' => PHP_VERSION,
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'environment' => getenv('APP_ENV') ?: 'development',
];

include __DIR__ . '/../templates/admin-header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <div class="page-actions">
            <a href="<?php echo get_app_base_url(); ?>/admin/monitoring.php?action=clear_logs" class="btn btn-secondary" 
               onclick="return confirm('Are you sure you want to clear old logs?')">
                Clear Old Logs
            </a>
        </div>
    </div>

    <!-- System Health Overview -->
    <div class="monitoring-grid">
        <div class="monitoring-card">
            <h3>System Health</h3>
            <div class="health-status">
                <?php
                $healthFile = __DIR__ . '/../public/health.php';
                $healthUrl = getenv('APP_URL') . '/health.php';
                ?>
                <div class="status-indicator status-healthy">
                    <span class="status-dot"></span>
                    <span>System Operational</span>
                </div>
                <a href="<?php echo htmlspecialchars($healthUrl); ?>" target="_blank" class="btn btn-sm">
                    View Health Check
                </a>
            </div>
        </div>

        <div class="monitoring-card">
            <h3>System Information</h3>
            <table class="info-table">
                <tr>
                    <td>Environment:</td>
                    <td><strong><?php echo htmlspecialchars($systemInfo['environment']); ?></strong></td>
                </tr>
                <tr>
                    <td>PHP Version:</td>
                    <td><?php echo htmlspecialchars($systemInfo['php_version']); ?></td>
                </tr>
                <tr>
                    <td>Memory Limit:</td>
                    <td><?php echo htmlspecialchars($systemInfo['memory_limit']); ?></td>
                </tr>
                <tr>
                    <td>Max Execution Time:</td>
                    <td><?php echo htmlspecialchars($systemInfo['max_execution_time']); ?>s</td>
                </tr>
            </table>
        </div>

        <div class="monitoring-card">
            <h3>Error Tracking</h3>
            <div class="metric-value">
                <?php echo count($recentErrors); ?>
            </div>
            <div class="metric-label">Errors Today</div>
            <?php if (getenv('ERROR_TRACKING_ENABLED') === 'true'): ?>
                <div class="status-indicator status-healthy">
                    <span class="status-dot"></span>
                    <span>Sentry Enabled</span>
                </div>
            <?php else: ?>
                <div class="status-indicator status-warning">
                    <span class="status-dot"></span>
                    <span>Sentry Disabled</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="monitoring-card">
            <h3>Performance Monitoring</h3>
            <?php if (getenv('PERFORMANCE_MONITORING_ENABLED') !== 'false'): ?>
                <div class="status-indicator status-healthy">
                    <span class="status-dot"></span>
                    <span>Enabled</span>
                </div>
                <div class="metric-value">
                    <?php echo count($perfMetrics); ?>
                </div>
                <div class="metric-label">Metrics Collected Today</div>
            <?php else: ?>
                <div class="status-indicator status-warning">
                    <span class="status-dot"></span>
                    <span>Disabled</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Errors -->
    <?php if (!empty($recentErrors)): ?>
    <div class="monitoring-section">
        <h2>Recent Errors</h2>
        <div class="log-entries">
            <?php foreach ($recentErrors as $error): ?>
                <?php if ($error): ?>
                <div class="log-entry log-entry-<?php echo htmlspecialchars($error['level'] ?? 'error'); ?>">
                    <div class="log-header">
                        <span class="log-level"><?php echo htmlspecialchars(strtoupper($error['level'] ?? 'ERROR')); ?></span>
                        <span class="log-time"><?php echo htmlspecialchars($error['timestamp'] ?? ''); ?></span>
                    </div>
                    <div class="log-message">
                        <?php echo htmlspecialchars($error['message'] ?? 'No message'); ?>
                    </div>
                    <?php if (!empty($error['context'])): ?>
                    <details class="log-context">
                        <summary>View Context</summary>
                        <pre><?php echo htmlspecialchars(json_encode($error['context'], JSON_PRETTY_PRINT)); ?></pre>
                    </details>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Performance Metrics -->
    <?php if (!empty($perfMetrics)): ?>
    <div class="monitoring-section">
        <h2>Recent Performance Metrics</h2>
        <div class="metrics-table">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Request Duration</th>
                        <th>Memory Peak</th>
                        <th>Metrics Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($perfMetrics as $metric): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($metric['timestamp'] ?? ''); ?></td>
                        <td><?php echo number_format($metric['summary']['request_duration_ms'] ?? 0, 2); ?> ms</td>
                        <td><?php echo htmlspecialchars($metric['summary']['memory_peak_mb'] ?? 0); ?> MB</td>
                        <td><?php echo htmlspecialchars($metric['summary']['metrics_count'] ?? 0); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Log Files -->
    <div class="monitoring-section">
        <h2>Log Files</h2>
        <div class="log-files-table">
            <table>
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Size</th>
                        <th>Last Modified</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logFiles as $file): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($file['name']); ?></td>
                        <td><?php echo number_format($file['size'] / 1024, 2); ?> KB</td>
                        <td><?php echo date('Y-m-d H:i:s', $file['modified']); ?></td>
                        <td>
                            <a href="<?php echo get_app_base_url(); ?>/admin/monitoring.php?action=download&file=<?php echo urlencode($file['name']); ?>" 
                               class="btn btn-sm">Download</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logFiles)): ?>
                    <tr>
                        <td colspan="4" class="text-center">No log files found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.monitoring-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.monitoring-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
}

.monitoring-card h3 {
    margin: 0 0 15px 0;
    font-size: 16px;
    color: #4a5568;
}

.metric-value {
    font-size: 36px;
    font-weight: 700;
    color: #2d3748;
    margin: 10px 0;
}

.metric-label {
    font-size: 14px;
    color: #718096;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
    font-size: 14px;
}

.status-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}

.status-healthy .status-dot {
    background: #48bb78;
}

.status-warning .status-dot {
    background: #ed8936;
}

.status-error .status-dot {
    background: #f56565;
}

.info-table {
    width: 100%;
    font-size: 14px;
}

.info-table td {
    padding: 8px 0;
}

.info-table td:first-child {
    color: #718096;
}

.monitoring-section {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.monitoring-section h2 {
    margin: 0 0 20px 0;
    font-size: 20px;
    color: #2d3748;
}

.log-entries {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.log-entry {
    border-left: 4px solid #cbd5e0;
    padding: 15px;
    background: #f7fafc;
    border-radius: 4px;
}

.log-entry-error {
    border-left-color: #f56565;
    background: #fff5f5;
}

.log-entry-warning {
    border-left-color: #ed8936;
    background: #fffaf0;
}

.log-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 12px;
}

.log-level {
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 4px;
    background: #e2e8f0;
}

.log-entry-error .log-level {
    background: #feb2b2;
    color: #742a2a;
}

.log-entry-warning .log-level {
    background: #fbd38d;
    color: #7c2d12;
}

.log-time {
    color: #718096;
}

.log-message {
    font-size: 14px;
    color: #2d3748;
    margin-bottom: 10px;
}

.log-context {
    font-size: 12px;
}

.log-context pre {
    background: white;
    padding: 10px;
    border-radius: 4px;
    overflow-x: auto;
    margin-top: 10px;
}

.metrics-table table,
.log-files-table table {
    width: 100%;
    border-collapse: collapse;
}

.metrics-table th,
.metrics-table td,
.log-files-table th,
.log-files-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.metrics-table th,
.log-files-table th {
    background: #f7fafc;
    font-weight: 600;
    color: #4a5568;
}

.text-center {
    text-align: center;
}
</style>

<?php
// Handle actions
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'download' && isset($_GET['file'])) {
        $filename = basename($_GET['file']);
        $filepath = $logDir . '/' . $filename;
        
        if (file_exists($filepath)) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }
    } elseif ($_GET['action'] === 'clear_logs') {
        // Clear logs older than 30 days
        $cutoffTime = time() - (30 * 24 * 60 * 60);
        foreach ($logFiles as $file) {
            if ($file['modified'] < $cutoffTime) {
                unlink($logDir . '/' . $file['name']);
            }
        }
        header('Location: /admin/monitoring.php');
        exit;
    }
}

include __DIR__ . '/../templates/admin-footer.php';
?>

