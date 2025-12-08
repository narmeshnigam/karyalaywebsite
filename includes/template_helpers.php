<?php
/**
 * Template Helper Functions
 * Provides utility functions for template rendering and includes
 */

/**
 * Include header template
 * 
 * @param string $page_title Optional page title
 * @param string $page_description Optional page description
 * @param array $additional_css Optional array of additional CSS files
 */
function include_header($page_title = '', $page_description = '', $additional_css = []) {
    // Make variables available to the header template
    if (!empty($page_title)) {
        $GLOBALS['page_title'] = $page_title;
    }
    if (!empty($page_description)) {
        $GLOBALS['page_description'] = $page_description;
    }
    if (!empty($additional_css)) {
        $GLOBALS['additional_css'] = $additional_css;
    }
    
    require_once __DIR__ . '/../templates/header.php';
}

/**
 * Include footer template
 * 
 * @param array $additional_js Optional array of additional JavaScript files
 */
function include_footer($additional_js = []) {
    if (!empty($additional_js)) {
        $GLOBALS['additional_js'] = $additional_js;
    }
    
    require_once __DIR__ . '/../templates/footer.php';
}

/**
 * Render a template partial
 * 
 * @param string $template_name Name of the template file (without .php extension)
 * @param array $data Associative array of data to pass to the template
 * @return void
 */
function render_template($template_name, $data = []) {
    $template_path = __DIR__ . '/../templates/' . $template_name . '.php';
    
    if (!file_exists($template_path)) {
        error_log("Template not found: {$template_path}");
        return;
    }
    
    // Extract data array to variables
    extract($data);
    
    require $template_path;
}

/**
 * Get the current page name for navigation highlighting
 * 
 * @return string Current page name without extension
 */
function get_current_page() {
    return basename($_SERVER['PHP_SELF'], '.php');
}

/**
 * Check if a navigation link should be active
 * 
 * @param string $page_name Page name to check
 * @return bool True if the page is active
 */
function is_active_page($page_name) {
    return get_current_page() === $page_name;
}

/**
 * Generate active class for navigation links
 * 
 * @param string $page_name Page name to check
 * @return string 'active' if page is active, empty string otherwise
 */
function active_class($page_name) {
    return is_active_page($page_name) ? 'active' : '';
}

/**
 * Sanitize output for HTML display
 * 
 * @param string $text Text to sanitize
 * @return string Sanitized text
 */
function esc_html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize output for HTML attribute
 * 
 * @param string $text Text to sanitize
 * @return string Sanitized text
 */
function esc_attr($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize URL
 * 
 * @param string $url URL to sanitize
 * @return string Sanitized URL
 */
function esc_url($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Date format (default: 'F j, Y')
 * @return string Formatted date
 */
function format_date($date, $format = 'F j, Y') {
    if (empty($date)) {
        return '';
    }
    
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    
    return date($format, $timestamp);
}

/**
 * Truncate text to specified length
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to append (default: '...')
 * @return string Truncated text
 */
function truncate_text($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Generate a slug from text
 * 
 * @param string $text Text to convert to slug
 * @return string Slug
 */
function generate_slug($text) {
    // Convert to lowercase
    $slug = strtolower($text);
    
    // Replace non-alphanumeric characters with hyphens
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    
    // Remove leading and trailing hyphens
    $slug = trim($slug, '-');
    
    return $slug;
}

/**
 * Display flash message if exists
 * 
 * @return void
 */
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        
        echo '<div class="alert alert-' . esc_attr($type) . '" role="alert">';
        echo esc_html($message);
        echo '</div>';
        
        // Clear flash message
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}

/**
 * Set flash message
 * 
 * @param string $message Message to display
 * @param string $type Message type (success, warning, danger, info)
 * @return void
 */
function set_flash_message($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get asset URL with versioning for cache busting and CDN support
 * 
 * @param string $path Path to asset relative to assets directory
 * @param bool $versioned Whether to add version query parameter (default: true)
 * @return string Full asset URL with version parameter
 */
function asset_url($path, $versioned = true) {
    static $cdnConfig = null;
    static $baseUrl = null;
    
    // Determine base URL once
    if ($baseUrl === null) {
        // Use get_base_url() for consistency
        $appBaseUrl = get_base_url();
        $baseUrl = $appBaseUrl . '/assets/';
    }
    
    // Load CDN configuration once
    if ($cdnConfig === null) {
        $configPath = __DIR__ . '/../config/cdn.php';
        $cdnConfig = file_exists($configPath) ? require $configPath : ['enabled' => false];
    }
    
    // Use CDN if enabled
    $finalBaseUrl = $baseUrl;
    if ($cdnConfig['enabled'] && !empty($cdnConfig['base_url'])) {
        // Check if this asset should be excluded from CDN
        $excluded = false;
        if (!empty($cdnConfig['exclude_patterns'])) {
            foreach ($cdnConfig['exclude_patterns'] as $pattern) {
                if (fnmatch($pattern, $path)) {
                    $excluded = true;
                    break;
                }
            }
        }
        
        if (!$excluded) {
            $finalBaseUrl = rtrim($cdnConfig['base_url'], '/') . '/assets/';
        }
    }
    
    $url = $finalBaseUrl . ltrim($path, '/');
    
    if ($versioned) {
        $fullPath = __DIR__ . '/../assets/' . ltrim($path, '/');
        
        // Use file modification time as version for cache busting
        if (file_exists($fullPath)) {
            $version = filemtime($fullPath);
            $url .= '?v=' . $version;
        }
    }
    
    return $url;
}

/**
 * Get versioned CSS URL
 * 
 * @param string $filename CSS filename (without path)
 * @return string Full CSS URL with version
 */
function css_url($filename) {
    return asset_url('css/' . $filename);
}

/**
 * Get versioned JS URL
 * 
 * @param string $filename JS filename (without path)
 * @return string Full JS URL with version
 */
function js_url($filename) {
    return asset_url('js/' . $filename);
}

/**
 * Get versioned image URL
 * 
 * @param string $filename Image filename (without path)
 * @return string Full image URL with version
 */
function image_url($filename) {
    return asset_url('images/' . $filename);
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in
 */
function is_logged_in() {
    // Use the same check as isAuthenticated() for consistency
    if (!function_exists('isAuthenticated')) {
        require_once __DIR__ . '/auth_helpers.php';
    }
    return isAuthenticated();
}

/**
 * Alias for is_logged_in() - Check if user is logged in
 * 
 * @return bool True if user is logged in
 */
function isLoggedIn() {
    return is_logged_in();
}

/**
 * Get the application base URL
 * Automatically detects if running in a subdirectory
 * 
 * @return string Base URL path (e.g., '/karyalayportal' or '')
 */
function get_base_url() {
    static $baseUrl = null;
    
    if ($baseUrl === null) {
        // First, try to detect from script path (most reliable)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $parts = explode('/', trim($scriptName, '/'));
        
        // If we have at least 2 parts (e.g., karyalayportal/admin/...), use the first part
        if (count($parts) >= 2) {
            $baseUrl = '/' . $parts[0];
        } else {
            // Fallback: try to get from config
            $configUrl = getenv('APP_URL');
            if (!empty($configUrl)) {
                $parsedUrl = parse_url($configUrl);
                $baseUrl = !empty($parsedUrl['path']) && $parsedUrl['path'] !== '/' ? rtrim($parsedUrl['path'], '/') : '';
            } else {
                $baseUrl = '';
            }
        }
    }
    
    return $baseUrl;
}

/**
 * Generate a URL with the correct base path
 * 
 * @param string $path Path relative to application root (e.g., '/app/dashboard.php')
 * @return string Full URL path with base
 */
function url($path) {
    $baseUrl = get_base_url();
    $path = '/' . ltrim($path, '/');
    return $baseUrl . $path;
}

/**
 * Redirect to a URL with the correct base path
 * 
 * @param string $path Path relative to application root
 * @param int $statusCode HTTP status code (default: 302)
 * @return void
 */
function redirect($path, $statusCode = 302) {
    header('Location: ' . url($path), true, $statusCode);
    exit;
}

/**
 * Check if user has specific role
 * 
 * @param string $role Role to check
 * @return bool True if user has the role
 */
function has_role($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Check if user is admin
 * 
 * @return bool True if user is admin
 */
function is_admin() {
    return has_role('ADMIN');
}

/**
 * Render an accessible form field with label and error handling
 * 
 * @param array $config Field configuration
 *   - id: Field ID (required)
 *   - name: Field name (required)
 *   - label: Field label text (required)
 *   - type: Input type (default: 'text')
 *   - value: Field value
 *   - required: Whether field is required (default: false)
 *   - error: Error message for this field
 *   - placeholder: Placeholder text
 *   - help: Help text
 *   - attributes: Additional HTML attributes
 * @return string HTML for the form field
 */
function render_accessible_field($config) {
    $id = $config['id'] ?? '';
    $name = $config['name'] ?? '';
    $label = $config['label'] ?? '';
    $type = $config['type'] ?? 'text';
    $value = $config['value'] ?? '';
    $required = $config['required'] ?? false;
    $error = $config['error'] ?? '';
    $placeholder = $config['placeholder'] ?? '';
    $help = $config['help'] ?? '';
    $attributes = $config['attributes'] ?? [];
    
    if (empty($id) || empty($name) || empty($label)) {
        return '';
    }
    
    $html = '<div class="form-group' . ($error ? ' has-error' : '') . '">';
    
    // Label
    $html .= '<label for="' . esc_attr($id) . '" class="form-label">';
    $html .= esc_html($label);
    if ($required) {
        $html .= ' <span class="text-red-500" aria-label="required">*</span>';
    }
    $html .= '</label>';
    
    // Build input attributes
    $inputAttrs = [
        'type' => $type,
        'id' => $id,
        'name' => $name,
        'class' => 'form-control' . ($error ? ' is-invalid' : ''),
        'value' => $value
    ];
    
    if ($required) {
        $inputAttrs['required'] = 'required';
        $inputAttrs['aria-required'] = 'true';
    }
    
    if ($placeholder) {
        $inputAttrs['placeholder'] = $placeholder;
    }
    
    // Add describedby for error or help text
    $describedBy = [];
    if ($error) {
        $describedBy[] = $id . '-error';
    }
    if ($help) {
        $describedBy[] = $id . '-help';
    }
    if (!empty($describedBy)) {
        $inputAttrs['aria-describedby'] = implode(' ', $describedBy);
    }
    
    if ($error) {
        $inputAttrs['aria-invalid'] = 'true';
    }
    
    // Merge additional attributes
    $inputAttrs = array_merge($inputAttrs, $attributes);
    
    // Render input
    if ($type === 'textarea') {
        $html .= '<textarea';
        foreach ($inputAttrs as $key => $val) {
            if ($key !== 'type' && $key !== 'value') {
                $html .= ' ' . esc_attr($key) . '="' . esc_attr($val) . '"';
            }
        }
        $html .= '>' . esc_html($value) . '</textarea>';
    } else {
        $html .= '<input';
        foreach ($inputAttrs as $key => $val) {
            $html .= ' ' . esc_attr($key) . '="' . esc_attr($val) . '"';
        }
        $html .= '>';
    }
    
    // Error message
    if ($error) {
        $html .= '<div id="' . esc_attr($id) . '-error" class="form-error" role="alert" aria-live="polite">';
        $html .= esc_html($error);
        $html .= '</div>';
    }
    
    // Help text
    if ($help) {
        $html .= '<small id="' . esc_attr($id) . '-help" class="form-help">';
        $html .= esc_html($help);
        $html .= '</small>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render accessible form errors summary
 * 
 * @param array $errors Array of error messages
 * @return string HTML for errors summary
 */
function render_form_errors($errors) {
    if (empty($errors)) {
        return '';
    }
    
    $html = '<div class="alert alert-danger" role="alert" aria-live="assertive">';
    $html .= '<h3 class="alert-heading">Please correct the following errors:</h3>';
    $html .= '<ul class="error-list">';
    
    foreach ($errors as $field => $error) {
        $html .= '<li><a href="#' . esc_attr($field) . '">' . esc_html($error) . '</a></li>';
    }
    
    $html .= '</ul>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Check if a form field has an associated label
 * 
 * @param string $html HTML content to check
 * @param string $fieldId Field ID to check for
 * @return bool True if field has associated label
 */
function has_associated_label($html, $fieldId) {
    // Check for label with for attribute
    $pattern = '/<label[^>]+for=["\']' . preg_quote($fieldId, '/') . '["\'][^>]*>/i';
    return preg_match($pattern, $html) === 1;
}

/**
 * Check if a form field has ARIA attributes for errors
 * 
 * @param string $html HTML content to check
 * @param string $fieldId Field ID to check for
 * @return bool True if field has proper ARIA error attributes
 */
function has_aria_error_attributes($html, $fieldId) {
    // Check for aria-invalid and aria-describedby
    $pattern = '/<[^>]+id=["\']' . preg_quote($fieldId, '/') . '["\'][^>]*aria-invalid=["\']true["\'][^>]*>/i';
    $hasInvalid = preg_match($pattern, $html) === 1;
    
    $pattern = '/<[^>]+id=["\']' . preg_quote($fieldId, '/') . '["\'][^>]*aria-describedby=["\'][^"\']*' . preg_quote($fieldId, '/') . '-error[^"\']*["\'][^>]*>/i';
    $hasDescribedBy = preg_match($pattern, $html) === 1;
    
    return $hasInvalid && $hasDescribedBy;
}

/**
 * Validate that all form fields have labels
 * 
 * @param string $html HTML content to validate
 * @return array Array of field IDs without labels
 */
function validate_form_accessibility($html) {
    $missingLabels = [];
    
    // Find all input, select, and textarea elements with IDs
    preg_match_all('/<(?:input|select|textarea)[^>]+id=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $fieldId) {
            // Skip hidden fields
            if (preg_match('/<input[^>]+id=["\']' . preg_quote($fieldId, '/') . '["\'][^>]+type=["\']hidden["\'][^>]*>/i', $html)) {
                continue;
            }
            
            if (!has_associated_label($html, $fieldId)) {
                $missingLabels[] = $fieldId;
            }
        }
    }
    
    return $missingLabels;
}

/**
 * Render an image tag with lazy loading support
 * 
 * @param string $src Image source URL
 * @param string $alt Alt text for accessibility
 * @param array $options Additional options
 *   - lazy: Enable lazy loading (default: true)
 *   - class: CSS classes
 *   - width: Image width
 *   - height: Image height
 *   - placeholder: Placeholder image URL for lazy loading
 *   - attributes: Additional HTML attributes
 * @return string HTML img tag
 */
function render_image($src, $alt = '', array $options = []) {
    $lazy = $options['lazy'] ?? true;
    $class = $options['class'] ?? '';
    $width = $options['width'] ?? null;
    $height = $options['height'] ?? null;
    $placeholder = $options['placeholder'] ?? null;
    $attributes = $options['attributes'] ?? [];
    
    $html = '<img';
    
    // Add src or data-src for lazy loading
    if ($lazy) {
        // Use placeholder or low-quality placeholder
        if ($placeholder) {
            $html .= ' src="' . esc_attr($placeholder) . '"';
        } else {
            // Use a 1x1 transparent placeholder
            $html .= ' src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 1 1\'%3E%3C/svg%3E"';
        }
        $html .= ' data-src="' . esc_attr($src) . '"';
        $html .= ' loading="lazy"';
        
        // Add lazy class for JavaScript fallback
        $class = trim($class . ' lazy-load');
    } else {
        $html .= ' src="' . esc_attr($src) . '"';
    }
    
    // Add alt text (required for accessibility)
    $html .= ' alt="' . esc_attr($alt) . '"';
    
    // Add class
    if (!empty($class)) {
        $html .= ' class="' . esc_attr($class) . '"';
    }
    
    // Add dimensions if provided
    if ($width !== null) {
        $html .= ' width="' . esc_attr($width) . '"';
    }
    if ($height !== null) {
        $html .= ' height="' . esc_attr($height) . '"';
    }
    
    // Add additional attributes
    foreach ($attributes as $key => $value) {
        $html .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
    }
    
    $html .= '>';
    
    return $html;
}

/**
 * Render pagination
 * 
 * @param int $totalItems Total number of items
 * @param int $perPage Items per page (default: 20)
 * @param array $options Rendering options
 * @return string HTML pagination
 */
function render_pagination($totalItems, $perPage = 20, array $options = []) {
    require_once __DIR__ . '/../classes/Services/PaginationService.php';
    
    $pagination = new \Karyalay\Services\PaginationService($totalItems, $perPage);
    return $pagination->render($options);
}

/**
 * Get pagination instance
 * 
 * @param int $totalItems Total number of items
 * @param int $perPage Items per page (default: 20)
 * @return \Karyalay\Services\PaginationService
 */
function get_pagination($totalItems, $perPage = 20) {
    require_once __DIR__ . '/../classes/Services/PaginationService.php';
    
    return new \Karyalay\Services\PaginationService($totalItems, $perPage);
}

/**
 * Render a responsive image with srcset for different screen sizes
 * 
 * @param string $src Base image source URL
 * @param string $alt Alt text for accessibility
 * @param array $srcset Array of image sources with sizes (e.g., ['1x' => 'image.jpg', '2x' => 'image@2x.jpg'])
 * @param array $options Additional options (same as render_image)
 * @return string HTML img tag with srcset
 */
function render_responsive_image($src, $alt = '', array $srcset = [], array $options = []) {
    $lazy = $options['lazy'] ?? true;
    $class = $options['class'] ?? '';
    $width = $options['width'] ?? null;
    $height = $options['height'] ?? null;
    $attributes = $options['attributes'] ?? [];
    
    $html = '<img';
    
    // Add src
    if ($lazy) {
        $html .= ' src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 1 1\'%3E%3C/svg%3E"';
        $html .= ' data-src="' . esc_attr($src) . '"';
        $html .= ' loading="lazy"';
        
        // Add srcset for lazy loading
        if (!empty($srcset)) {
            $srcsetStr = [];
            foreach ($srcset as $size => $url) {
                $srcsetStr[] = esc_attr($url) . ' ' . $size;
            }
            $html .= ' data-srcset="' . implode(', ', $srcsetStr) . '"';
        }
        
        $class = trim($class . ' lazy-load');
    } else {
        $html .= ' src="' . esc_attr($src) . '"';
        
        // Add srcset
        if (!empty($srcset)) {
            $srcsetStr = [];
            foreach ($srcset as $size => $url) {
                $srcsetStr[] = esc_attr($url) . ' ' . $size;
            }
            $html .= ' srcset="' . implode(', ', $srcsetStr) . '"';
        }
    }
    
    // Add alt text
    $html .= ' alt="' . esc_attr($alt) . '"';
    
    // Add class
    if (!empty($class)) {
        $html .= ' class="' . esc_attr($class) . '"';
    }
    
    // Add dimensions
    if ($width !== null) {
        $html .= ' width="' . esc_attr($width) . '"';
    }
    if ($height !== null) {
        $html .= ' height="' . esc_attr($height) . '"';
    }
    
    // Add additional attributes
    foreach ($attributes as $key => $value) {
        $html .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
    }
    
    $html .= '>';
    
    return $html;
}
