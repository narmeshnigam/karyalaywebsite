<?php
/**
 * Admin Media Library Page
 * Displays grid of uploaded media assets with upload functionality
 * Supports images, videos, PDFs, and other file types
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\MediaAsset;

// Start secure session
startSecureSession();

// Require admin authentication and media.view permission
require_admin();
require_permission('media.view');

// Initialize MediaAsset model
$mediaAssetModel = new MediaAsset();

// Handle file deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!validateCsrfToken()) {
        $delete_error = 'Invalid CSRF token';
    } else {
        $delete_id = $_POST['media_id'] ?? '';
        if (!empty($delete_id)) {
            // Get the media asset to find the file path
            $asset = $mediaAssetModel->findById($delete_id);
            if ($asset) {
                // Extract relative path from URL or use stored path
                $file_path = '';
                if (!empty($asset['file_path'])) {
                    $file_path = __DIR__ . '/../' . ltrim($asset['file_path'], '/');
                } else {
                    // Legacy: extract from URL
                    $url = $asset['url'];
                    if (preg_match('/\/uploads\/media\/([^?]+)/', $url, $matches)) {
                        $file_path = __DIR__ . '/../uploads/media/' . $matches[1];
                    }
                }
                
                // Delete from database first
                if ($mediaAssetModel->delete($delete_id)) {
                    // Then delete the physical file if it exists
                    if (!empty($file_path) && file_exists($file_path)) {
                        @unlink($file_path);
                    }
                    header('Location: ' . get_app_base_url() . '/admin/media-library.php?deleted=1');
                    exit;
                } else {
                    $delete_error = 'Failed to delete media asset from database';
                }
            } else {
                $delete_error = 'Media asset not found';
            }
        }
    }
}

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
                // Validate file type - expanded list
                $allowed_types = [
                    'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
                    'application/pdf',
                    'video/mp4', 'video/webm', 'video/ogg',
                    'audio/mpeg', 'audio/wav', 'audio/ogg',
                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'text/plain', 'text/csv',
                    'application/zip', 'application/x-rar-compressed'
                ];
                
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                if (!in_array($mime_type, $allowed_types)) {
                    $upload_error = 'Invalid file type. Allowed: images, videos, audio, documents, PDFs, archives';
                } else {
                    // Generate unique filename
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
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
                        $relative_path = 'uploads/media/' . $unique_filename;
                        
                        // Move uploaded file
                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                            // Generate URL dynamically
                            $url = get_app_base_url() . '/' . $relative_path;
                            
                            // Save to database with relative path
                            $media_data = [
                                'filename' => $file['name'],
                                'url' => $url,
                                'file_path' => $relative_path,
                                'mime_type' => $mime_type,
                                'size' => $file['size'],
                                'uploaded_by' => $_SESSION['user_id']
                            ];
                            
                            $result = $mediaAssetModel->create($media_data);
                            
                            if ($result) {
                                $upload_success = true;
                                header('Location: ' . get_app_base_url() . '/admin/media-library.php?uploaded=1');
                                exit;
                            } else {
                                $upload_error = 'Failed to save media asset to database';
                                unlink($upload_path);
                            }
                        } else {
                            $upload_error = 'Failed to move uploaded file. Check directory permissions.';
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

/**
 * Get the correct URL for a media asset
 * Handles both legacy absolute URLs and new relative paths
 */
function get_media_url($asset) {
    // If we have a file_path, construct URL dynamically
    if (!empty($asset['file_path'])) {
        return get_app_base_url() . '/' . ltrim($asset['file_path'], '/');
    }
    
    // Legacy: check if URL is relative or needs base URL
    $url = $asset['url'] ?? '';
    
    // If URL starts with http, return as-is
    if (strpos($url, 'http') === 0) {
        return $url;
    }
    
    // If URL starts with /, it's already absolute path
    if (strpos($url, '/') === 0) {
        // Check if it already has the app base URL
        $base = get_app_base_url();
        if (strpos($url, $base) === 0) {
            return $url;
        }
        return $base . $url;
    }
    
    // Otherwise, prepend base URL
    return get_app_base_url() . '/' . $url;
}

/**
 * Get file type category for icon display
 */
function get_file_type_category($mime_type) {
    if (strpos($mime_type, 'image/') === 0) return 'image';
    if (strpos($mime_type, 'video/') === 0) return 'video';
    if (strpos($mime_type, 'audio/') === 0) return 'audio';
    if ($mime_type === 'application/pdf') return 'pdf';
    if (strpos($mime_type, 'word') !== false || $mime_type === 'application/msword') return 'word';
    if (strpos($mime_type, 'excel') !== false || strpos($mime_type, 'spreadsheet') !== false) return 'excel';
    if (strpos($mime_type, 'powerpoint') !== false || strpos($mime_type, 'presentation') !== false) return 'powerpoint';
    if (strpos($mime_type, 'zip') !== false || strpos($mime_type, 'rar') !== false) return 'archive';
    if (strpos($mime_type, 'text/') === 0) return 'text';
    return 'file';
}

/**
 * Get SVG icon for file type
 */
function get_file_type_icon($type) {
    $icons = [
        'pdf' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M10 11h4"></path><path d="M10 15h4"></path><path d="M10 19h4"></path></svg>',
        'word' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
        'excel' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><rect x="8" y="12" width="8" height="6"></rect><line x1="12" y1="12" x2="12" y2="18"></line><line x1="8" y1="15" x2="16" y2="15"></line></svg>',
        'powerpoint' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><rect x="8" y="12" width="8" height="6" rx="1"></rect></svg>',
        'audio' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>',
        'archive' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ca8a04" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>',
        'text' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>',
        'file' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>'
    ];
    return $icons[$type] ?? $icons['file'];
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
            Upload Media
        </button>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($_GET['uploaded']) && $_GET['uploaded'] == '1'): ?>
    <div class="alert alert-success">
        <strong>Success!</strong> Media file uploaded successfully.
    </div>
<?php endif; ?>

<?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
    <div class="alert alert-success">
        <strong>Success!</strong> Media file deleted successfully.
    </div>
<?php endif; ?>

<?php if (!empty($upload_error)): ?>
    <div class="alert alert-error">
        <strong>Error!</strong> <?php echo htmlspecialchars($upload_error); ?>
    </div>
<?php endif; ?>

<?php if (!empty($delete_error)): ?>
    <div class="alert alert-error">
        <strong>Error!</strong> <?php echo htmlspecialchars($delete_error); ?>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="admin-filters-section">
    <form method="GET" action="<?php echo get_app_base_url(); ?>/admin/media-library.php" class="admin-filters-form">
        <div class="admin-filter-group">
            <label for="type" class="admin-filter-label">File Type</label>
            <select id="type" name="type" class="admin-filter-select">
                <option value="">All Types</option>
                <option value="image/" <?php echo strpos($mime_type_filter, 'image/') === 0 ? 'selected' : ''; ?>>Images</option>
                <option value="video/" <?php echo strpos($mime_type_filter, 'video/') === 0 ? 'selected' : ''; ?>>Videos</option>
                <option value="audio/" <?php echo strpos($mime_type_filter, 'audio/') === 0 ? 'selected' : ''; ?>>Audio</option>
                <option value="application/pdf" <?php echo $mime_type_filter === 'application/pdf' ? 'selected' : ''; ?>>PDFs</option>
                <option value="application/" <?php echo $mime_type_filter === 'application/' ? 'selected' : ''; ?>>Documents</option>
            </select>
        </div>
        
        <div class="admin-filter-actions">
            <button type="submit" class="btn btn-secondary">Apply Filters</button>
            <a href="<?php echo get_app_base_url(); ?>/admin/media-library.php" class="btn btn-text">Clear</a>
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
            <?php foreach ($media_assets as $asset): 
                $media_url = get_media_url($asset);
                $file_type = get_file_type_category($asset['mime_type']);
                $extension = strtoupper(pathinfo($asset['filename'], PATHINFO_EXTENSION));
                $file_path = $asset['file_path'] ?? '';
                if (empty($file_path) && preg_match('/\/uploads\/(.+)$/', $asset['url'], $m)) {
                    $file_path = 'uploads/' . $m[1];
                }
            ?>
                <div class="media-item" data-id="<?php echo htmlspecialchars($asset['id']); ?>">
                    <div class="media-preview">
                        <?php if ($file_type === 'image'): ?>
                            <img src="<?php echo htmlspecialchars($media_url); ?>" 
                                 alt="<?php echo htmlspecialchars($asset['filename']); ?>"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'media-error\'><span>Image not found</span></div>';">
                        <?php elseif ($file_type === 'video'): ?>
                            <video controls preload="metadata">
                                <source src="<?php echo htmlspecialchars($media_url); ?>" 
                                        type="<?php echo htmlspecialchars($asset['mime_type']); ?>">
                                Your browser does not support video playback.
                            </video>
                        <?php else: ?>
                            <div class="media-placeholder">
                                <div class="media-icon">
                                    <?php echo get_file_type_icon($file_type); ?>
                                </div>
                                <span class="media-extension"><?php echo $extension; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="media-info">
                        <div class="media-filename" title="<?php echo htmlspecialchars($asset['filename']); ?>">
                            <?php echo htmlspecialchars($asset['filename']); ?>
                        </div>
                        <div class="media-path" title="<?php echo htmlspecialchars($file_path); ?>">
                            <span class="path-label">Path:</span> <?php echo htmlspecialchars($file_path ?: 'N/A'); ?>
                        </div>
                        <div class="media-meta">
                            <span><?php echo format_file_size($asset['size']); ?></span>
                            <span><?php echo get_relative_time($asset['created_at']); ?></span>
                        </div>
                        <div class="media-actions">
                            <button type="button" 
                                    class="btn btn-sm btn-secondary" 
                                    onclick="copyToClipboard('<?php echo htmlspecialchars($media_url, ENT_QUOTES); ?>')">
                                Copy URL
                            </button>
                            <a href="<?php echo htmlspecialchars($media_url); ?>" 
                               target="_blank" 
                               class="btn btn-sm btn-text"
                               title="Open in new tab">
                                View
                            </a>
                            <button type="button" 
                                    class="btn btn-sm btn-danger" 
                                    onclick="confirmDelete('<?php echo htmlspecialchars($asset['id']); ?>', '<?php echo htmlspecialchars($asset['filename'], ENT_QUOTES); ?>')">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="admin-card-footer">
                <?php 
                $base_url = get_app_base_url() . '/admin/media-library.php';
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
                       accept="image/*,video/*,audio/*,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar"
                       required>
                <p class="form-help">
                    Allowed: Images, Videos, Audio, PDFs, Documents (Word, Excel, PowerPoint), Text files, Archives. Max size: 10MB
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

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="modal">
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h2 class="modal-title">Delete Media</h2>
            <button type="button" class="modal-close" onclick="document.getElementById('delete-modal').style.display='none'">
                ✕
            </button>
        </div>
        <form method="POST" class="modal-body">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="media_id" id="delete-media-id" value="">
            
            <p class="delete-confirm-text">
                Are you sure you want to delete <strong id="delete-filename"></strong>?
            </p>
            <p class="delete-warning">This action cannot be undone.</p>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-text" onclick="document.getElementById('delete-modal').style.display='none'">
                    Cancel
                </button>
                <button type="submit" class="btn btn-danger">
                    Delete
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
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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
    height: 180px;
    background: var(--color-gray-100);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}

.media-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.media-preview video {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: #000;
}

.media-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-2);
    padding: var(--spacing-4);
    width: 100%;
    height: 100%;
}

.media-icon {
    display: flex;
    align-items: center;
    justify-content: center;
}

.media-icon svg {
    width: 48px;
    height: 48px;
}

.media-extension {
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-500);
    text-transform: uppercase;
    background: var(--color-gray-200);
    padding: 2px 8px;
    border-radius: var(--radius-sm);
}

.media-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    color: var(--color-gray-500);
    font-size: var(--font-size-sm);
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
    margin-bottom: var(--spacing-1);
}

.media-path {
    font-size: var(--font-size-xs);
    color: var(--color-gray-500);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: var(--spacing-2);
    font-family: monospace;
}

.media-path .path-label {
    color: var(--color-gray-400);
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
    flex-wrap: wrap;
}

.btn-danger {
    background-color: #dc2626;
    color: white;
    border: none;
}

.btn-danger:hover {
    background-color: #b91c1c;
}

.btn-sm.btn-danger {
    padding: 4px 8px;
    font-size: var(--font-size-xs);
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

.modal-content.modal-sm {
    max-width: 400px;
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

.delete-confirm-text {
    margin: 0 0 var(--spacing-2) 0;
    color: var(--color-gray-700);
}

.delete-warning {
    margin: 0;
    font-size: var(--font-size-sm);
    color: #dc2626;
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
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
    
    .media-actions {
        flex-direction: column;
    }
    
    .media-actions .btn {
        width: 100%;
        text-align: center;
    }
}
</style>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showToast('URL copied to clipboard!', 'success');
    }, function(err) {
        console.error('Could not copy text: ', err);
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            showToast('URL copied to clipboard!', 'success');
        } catch (e) {
            showToast('Failed to copy URL', 'error');
        }
        document.body.removeChild(textarea);
    });
}

function confirmDelete(mediaId, filename) {
    document.getElementById('delete-media-id').value = mediaId;
    document.getElementById('delete-filename').textContent = filename;
    document.getElementById('delete-modal').style.display = 'flex';
}

function showToast(message, type) {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 12px 24px;
        border-radius: 8px;
        color: white;
        font-size: 14px;
        z-index: 9999;
        animation: slideIn 0.3s ease;
        background-color: ${type === 'success' ? '#10b981' : '#ef4444'};
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Close modals when clicking outside
document.getElementById('upload-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});

document.getElementById('delete-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('upload-modal').style.display = 'none';
        document.getElementById('delete-modal').style.display = 'none';
    }
});

// Add animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>

<?php include_admin_footer(); ?>
