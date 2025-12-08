<?php
/**
 * Admin Media Library Page
 * Displays grid of uploaded media assets with upload functionality
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\MediaAsset;

// Start secure session
startSecureSession();

// Require admin authentication
require_admin();

// Initialize MediaAsset model
$mediaAssetModel = new MediaAsset();

// Handle file upload
$upload_success = false;
$upload_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_file'])) {
    // Verify CSRF token
    if (!validateCsrfToken()) {
        $upload_error = 'Invalid CSRF token';
    } else {
        $file = $_FILES['media_file'];
        
        // Log upload error if any
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
            ];
            $upload_error = $error_messages[$file['error']] ?? 'Unknown upload error: ' . $file['error'];
            error_log("File upload error: " . $upload_error);
        }
        
        // Validate file upload
        if ($file['error'] === UPLOAD_ERR_OK) {
            // Validate file size (max 10MB)
            $max_size = 10 * 1024 * 1024; // 10MB
            if ($file['size'] > $max_size) {
                $upload_error = 'File size exceeds 10MB limit';
            } else {
                // Validate file type
                $allowed_types = [
                    'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
                    'application/pdf', 'video/mp4', 'video/webm'
                ];
                
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                if (!in_array($mime_type, $allowed_types)) {
                    $upload_error = 'Invalid file type. Allowed: images, PDFs, videos';
                } else {
                    // Generate unique filename
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $unique_filename = uniqid('media_', true) . '.' . $extension;
                    
                    // Create uploads directory if it doesn't exist
                    $upload_dir = __DIR__ . '/../uploads/media/';
                    if (!is_dir($upload_dir)) {
                        if (!mkdir($upload_dir, 0755, true)) {
                            $upload_error = 'Failed to create upload directory';
                        }
                    }
                    
                    if (empty($upload_error)) {
                        $upload_path = $upload_dir . $unique_filename;
                        
                        // Debug logging
                        error_log("Attempting to move file from: " . $file['tmp_name']);
                        error_log("To: " . $upload_path);
                        error_log("Temp file exists: " . (file_exists($file['tmp_name']) ? 'yes' : 'no'));
                        error_log("Upload dir writable: " . (is_writable($upload_dir) ? 'yes' : 'no'));
                        
                        // Move uploaded file
                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                            // Generate URL
                            $url = get_base_url() . '/uploads/media/' . $unique_filename;
                            
                            // Save to database
                            $media_data = [
                                'filename' => $file['name'],
                                'url' => $url,
                                'mime_type' => $mime_type,
                                'size' => $file['size'],
                                'uploaded_by' => $_SESSION['user_id']
                            ];
                            
                            $result = $mediaAssetModel->create($media_data);
                            
                            if ($result) {
                                $upload_success = true;
                                // Redirect to avoid form resubmission
                                header('Location: ' . get_base_url() . '/admin/media-library.php?uploaded=1');
                                exit;
                            } else {
                                $upload_error = 'Failed to save media asset to database';
                                // Clean up uploaded file
                                unlink($upload_path);
                            }
                        } else {
                            $upload_error = 'Failed to move uploaded file. Check directory permissions.';
                            error_log("Failed to move uploaded file from {$file['tmp_name']} to {$upload_path}");
                        }
                    }
                }
            }
        } else {
            $upload_error = 'File upload error: ' . $file['error'];
        }
    }
}

// Get filters from query parameters
$mime_type_filter = $_GET['type'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 24;
$offset = ($page - 1) * $per_page;

// Build filters array
$filters = [];
if (!empty($mime_type_filter)) {
    $filters['mime_type'] = $mime_type_filter;
}

// Fetch media assets
try {
    $media_assets = $mediaAssetModel->findAll($filters, $per_page, $offset);
    
    // Get total count for pagination
    $db = \Karyalay\Database\Connection::getInstance();
    $count_sql = "SELECT COUNT(*) FROM media_assets WHERE 1=1";
    $count_params = [];
    
    if (!empty($mime_type_filter)) {
        $count_sql .= " AND mime_type LIKE :mime_type";
        $count_params[':mime_type'] = $mime_type_filter . '%';
    }
    
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_assets = $count_stmt->fetchColumn();
    $total_pages = ceil($total_assets / $per_page);
    
} catch (Exception $e) {
    error_log("Media library error: " . $e->getMessage());
    $media_assets = [];
    $total_assets = 0;
    $total_pages = 0;
}

// Generate CSRF token
$csrf_token = getCsrfToken();

// Include admin header
include_admin_header('Media Library');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Media Library</h1>
        <p class="admin-page-description">Upload and manage media assets for use in content</p>
    </div>
    <div class="admin-page-header-actions">
        <button type="button" class="btn btn-primary" onclick="document.getElementById('upload-modal').style.display='flex'">
            <span class="btn-icon">⬆️</span>
            Upload Media
        </button>
    </div>
</div>

<!-- Upload Success Message -->
<?php if (isset($_GET['uploaded']) && $_GET['uploaded'] == '1'): ?>
    <div class="alert alert-success">
        <strong>Success!</strong> Media file uploaded successfully.
    </div>
<?php endif; ?>

<!-- Upload Error Message -->
<?php if (!empty($upload_error)): ?>
    <div class="alert alert-error">
        <strong>Error!</strong> <?php echo htmlspecialchars($upload_error); ?>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="admin-filters-section">
    <form method="GET" action="<?php echo get_base_url(); ?>/admin/media-library.php" class="admin-filters-form">
        <div class="admin-filter-group">
            <label for="type" class="admin-filter-label">File Type</label>
            <select id="type" name="type" class="admin-filter-select">
                <option value="">All Types</option>
                <option value="image/" <?php echo strpos($mime_type_filter, 'image/') === 0 ? 'selected' : ''; ?>>Images</option>
                <option value="video/" <?php echo strpos($mime_type_filter, 'video/') === 0 ? 'selected' : ''; ?>>Videos</option>
                <option value="application/pdf" <?php echo $mime_type_filter === 'application/pdf' ? 'selected' : ''; ?>>PDFs</option>
            </select>
        </div>
        
        <div class="admin-filter-actions">
            <button type="submit" class="btn btn-secondary">Apply Filters</button>
            <a href="<?php echo get_base_url(); ?>/admin/media-library.php" class="btn btn-text">Clear</a>
        </div>
    </form>
</div>

<!-- Media Grid -->
<div class="admin-card">
    <?php if (empty($media_assets)): ?>
        <?php 
        render_empty_state(
            'No media files found',
            'Upload your first media file to get started',
            'javascript:document.getElementById(\'upload-modal\').style.display=\'flex\'',
            'Upload Media'
        );
        ?>
    <?php else: ?>
        <div class="media-grid">
            <?php foreach ($media_assets as $asset): ?>
                <div class="media-item" data-id="<?php echo htmlspecialchars($asset['id']); ?>">
                    <div class="media-preview">
                        <?php if (strpos($asset['mime_type'], 'image/') === 0): ?>
                            <img src="<?php echo htmlspecialchars($asset['url']); ?>" 
                                 alt="<?php echo htmlspecialchars($asset['filename']); ?>"
                                 loading="lazy">
                        <?php elseif (strpos($asset['mime_type'], 'video/') === 0): ?>
                            <video controls>
                                <source src="<?php echo htmlspecialchars($asset['url']); ?>" 
                                        type="<?php echo htmlspecialchars($asset['mime_type']); ?>">
                            </video>
                        <?php else: ?>
                            <div class="media-placeholder">
                                <span class="media-icon">📄</span>
                                <span class="media-extension"><?php echo strtoupper(pathinfo($asset['filename'], PATHINFO_EXTENSION)); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="media-info">
                        <div class="media-filename" title="<?php echo htmlspecialchars($asset['filename']); ?>">
                            <?php echo htmlspecialchars($asset['filename']); ?>
                        </div>
                        <div class="media-meta">
                            <span><?php echo format_file_size($asset['size']); ?></span>
                            <span><?php echo get_relative_time($asset['created_at']); ?></span>
                        </div>
                        <div class="media-actions">
                            <button type="button" 
                                    class="btn btn-sm btn-secondary" 
                                    onclick="copyToClipboard('<?php echo htmlspecialchars($asset['url'], ENT_QUOTES); ?>')">
                                Copy URL
                            </button>
                            <a href="<?php echo htmlspecialchars($asset['url']); ?>" 
                               target="_blank" 
                               class="btn btn-sm btn-text">
                                View
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="admin-card-footer">
                <?php 
                $base_url = get_base_url() . '/admin/media-library.php';
                $query_params = [];
                if (!empty($mime_type_filter)) {
                    $query_params[] = 'type=' . urlencode($mime_type_filter);
                }
                if (!empty($query_params)) {
                    $base_url .= '?' . implode('&', $query_params);
                }
                render_pagination($page, $total_pages, $base_url);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-card-footer">
            <p class="admin-card-footer-text">
                Showing <?php echo count($media_assets); ?> of <?php echo $total_assets; ?> media file<?php echo $total_assets !== 1 ? 's' : ''; ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<!-- Upload Modal -->
<div id="upload-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Upload Media</h2>
            <button type="button" class="modal-close" onclick="document.getElementById('upload-modal').style.display='none'">
                ✕
            </button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="modal-body">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="form-group">
                <label for="media_file" class="form-label">Select File</label>
                <input type="file" 
                       id="media_file" 
                       name="media_file" 
                       class="form-input" 
                       accept="image/*,video/*,application/pdf"
                       required>
                <p class="form-help">
                    Allowed: Images (JPEG, PNG, GIF, WebP), Videos (MP4, WebM), PDFs. Max size: 10MB
                </p>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-text" onclick="document.getElementById('upload-modal').style.display='none'">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    Upload
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.admin-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-6);
    gap: var(--spacing-4);
}

.admin-page-header-content {
    flex: 1;
}

.admin-page-title {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-2) 0;
}

.admin-page-description {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    margin: 0;
}

.admin-page-header-actions {
    display: flex;
    gap: var(--spacing-3);
}

.alert {
    padding: var(--spacing-4);
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-6);
}

.alert-success {
    background-color: #d1fae5;
    border: 1px solid #6ee7b7;
    color: #065f46;
}

.alert-error {
    background-color: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}

.admin-filters-section {
    background: white;
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    padding: var(--spacing-4);
    margin-bottom: var(--spacing-6);
}

.admin-filters-form {
    display: flex;
    gap: var(--spacing-4);
    align-items: flex-end;
    flex-wrap: wrap;
}

.admin-filter-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
    flex: 1;
    min-width: 200px;
}

.admin-filter-label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
}

.admin-filter-select {
    padding: var(--spacing-2) var(--spacing-3);
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-md);
    font-size: var(--font-size-base);
    color: var(--color-gray-900);
}

.admin-filter-select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.admin-filter-actions {
    display: flex;
    gap: var(--spacing-2);
}

.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: var(--spacing-4);
    padding: var(--spacing-4);
}

.media-item {
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    overflow: hidden;
    background: white;
    transition: box-shadow 0.2s;
}

.media-item:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.media-preview {
    width: 100%;
    height: 200px;
    background: var(--color-gray-100);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.media-preview img,
.media-preview video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.media-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--spacing-2);
}

.media-icon {
    font-size: 48px;
}

.media-extension {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-600);
}

.media-info {
    padding: var(--spacing-3);
}

.media-filename {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: var(--spacing-2);
}

.media-meta {
    display: flex;
    gap: var(--spacing-2);
    font-size: var(--font-size-xs);
    color: var(--color-gray-600);
    margin-bottom: var(--spacing-3);
}

.media-meta span:not(:last-child)::after {
    content: '•';
    margin-left: var(--spacing-2);
}

.media-actions {
    display: flex;
    gap: var(--spacing-2);
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: var(--radius-lg);
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-4);
    border-bottom: 1px solid var(--color-gray-200);
}

.modal-title {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: var(--font-size-xl);
    color: var(--color-gray-500);
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-md);
}

.modal-close:hover {
    background: var(--color-gray-100);
}

.modal-body {
    padding: var(--spacing-4);
}

.form-group {
    margin-bottom: var(--spacing-4);
}

.form-label {
    display: block;
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    margin-bottom: var(--spacing-2);
}

.form-input {
    width: 100%;
    padding: var(--spacing-2) var(--spacing-3);
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-md);
    font-size: var(--font-size-base);
}

.form-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-help {
    font-size: var(--font-size-xs);
    color: var(--color-gray-600);
    margin-top: var(--spacing-2);
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: var(--spacing-2);
    padding: var(--spacing-4);
    border-top: 1px solid var(--color-gray-200);
}

.btn-icon {
    margin-right: var(--spacing-1);
}

.admin-card-footer-text {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: 0;
}

@media (max-width: 768px) {
    .admin-page-header {
        flex-direction: column;
    }
    
    .media-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
}
</style>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('URL copied to clipboard!');
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}

// Close modal when clicking outside
document.getElementById('upload-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});
</script>

<?php include_admin_footer(); ?>
