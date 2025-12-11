<?php
/**
 * Template Helper Functions
 * Provides utility functions for template rendering and includes
 */

/**
 * Get the configured brand name from settings
 * Uses static caching to avoid multiple database queries per request
 * 
 * @return string Brand name or default fallback "SellerPortal"
 */
function get_brand_name(): string
{
    static $brandName = null;
    
    // Return cached value if already retrieved
    if ($brandName !== null) {
        return $brandName;
    }
    
    $fallback = 'SellerPortal';
    
    try {
        require_once __DIR__ . '/../classes/Models/Setting.php';
        
        $setting = new \Karyalay\Models\Setting();
        $value = $setting->get('brand_name');
        
        // Check if value is empty or whitespace-only
        if ($value === null || trim($value) === '') {
            $brandName = $fallback;
        } else {
            $brandName = $value;
        }
    } catch (\Exception $e) {
        // Log the error and return fallback
        error_log("Error retrieving brand name: " . $e->getMessage());
        $brandName = $fallback;
    }
    
    return $brandName;
}

/**
 * Get the logo URL for light backgrounds (dark logo)
 * Uses static caching to avoid multiple database queries per request
 * 
 * @return string|null Full logo URL or null if not set
 */
function get_logo_light_bg(): ?string
{
    static $logoUrl = null;
    static $fetched = false;
    
    if ($fetched) {
        return $logoUrl;
    }
    
    try {
        require_once __DIR__ . '/../classes/Models/Setting.php';
        
        $setting = new \Karyalay\Models\Setting();
        $value = $setting->get('logo_light_bg');
        
        if (!empty($value) && trim($value) !== '') {
            // Prepend app base URL if the path is relative
            if (strpos($value, 'http') !== 0) {
                $logoUrl = get_app_base_url() . $value;
            } else {
                $logoUrl = $value;
            }
        } else {
            $logoUrl = null;
        }
        $fetched = true;
    } catch (\Exception $e) {
        error_log("Error retrieving logo_light_bg: " . $e->getMessage());
        $fetched = true;
    }
    
    return $logoUrl;
}

/**
 * Get the logo URL for dark backgrounds (light logo)
 * Uses static caching to avoid multiple database queries per request
 * 
 * @return string|null Full logo URL or null if not set
 */
function get_logo_dark_bg(): ?string
{
    static $logoUrl = null;
    static $fetched = false;
    
    if ($fetched) {
        return $logoUrl;
    }
    
    try {
        require_once __DIR__ . '/../classes/Models/Setting.php';
        
        $setting = new \Karyalay\Models\Setting();
        $value = $setting->get('logo_dark_bg');
        
        if (!empty($value) && trim($value) !== '') {
            // Prepend app base URL if the path is relative
            if (strpos($value, 'http') !== 0) {
                $logoUrl = get_app_base_url() . $value;
            } else {
                $logoUrl = $value;
            }
        } else {
            $logoUrl = null;
        }
        $fetched = true;
    } catch (\Exception $e) {
        error_log("Error retrieving logo_dark_bg: " . $e->getMessage());
        $fetched = true;
    }
    
    return $logoUrl;
}

/**
 * Render brand logo HTML with fallback to text
 * 
 * @param string $variant 'light_bg' for light backgrounds (use dark logo), 'dark_bg' for dark backgrounds (use light logo)
 * @param string $class Additional CSS classes for the logo element
 * @param int|null $height Optional height constraint for the image
 * @return string HTML for logo (img tag or span with text)
 */
function render_brand_logo(string $variant = 'light_bg', string $class = '', ?int $height = null): string
{
    $logoUrl = ($variant === 'dark_bg') ? get_logo_dark_bg() : get_logo_light_bg();
    $brandName = get_brand_name();
    
    if ($logoUrl) {
        $heightAttr = $height ? ' height="' . (int)$height . '"' : '';
        return '<img src="' . esc_attr($logoUrl) . '" alt="' . esc_attr($brandName) . '" class="brand-logo-img ' . esc_attr($class) . '"' . $heightAttr . '>';
    }
    
    return '<span class="brand-logo-text ' . esc_attr($class) . '">' . esc_html($brandName) . '</span>';
}

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
        // Assets are in the root, not in public directory
        // Get the base path without /public
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $parts = explode('/', trim($scriptName, '/'));
        
        if (count($parts) >= 2) {
            $baseUrl = '/' . $parts[0] . '/assets/';
        } else {
            $baseUrl = '/assets/';
        }
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
 * Automatically detects if running in a subdirectory and includes /public
 * 
 * @return string Base URL path (e.g., '/karyalayportal/public' or '/public')
 */
function get_base_url() {
    static $baseUrl = null;
    
    if ($baseUrl === null) {
        // First, try to detect from script path (most reliable)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $parts = explode('/', trim($scriptName, '/'));
        
        // Check if we're in the public directory
        $inPublic = in_array('public', $parts);
        
        // If we have at least 2 parts (e.g., karyalayportal/public/...), use the first part + /public
        if (count($parts) >= 2) {
            $baseUrl = '/' . $parts[0];
            // Add /public if we're in the public directory and it's not already included
            if ($inPublic && $parts[1] === 'public') {
                $baseUrl .= '/public';
            } elseif (!$inPublic) {
                // If not in public directory, still add /public for consistency
                $baseUrl .= '/public';
            }
        } else {
            // Fallback: try to get from config
            $configUrl = getenv('APP_URL');
            if (!empty($configUrl)) {
                $parsedUrl = parse_url($configUrl);
                $baseUrl = !empty($parsedUrl['path']) && $parsedUrl['path'] !== '/' ? rtrim($parsedUrl['path'], '/') : '';
                // Ensure /public is included
                if (!str_ends_with($baseUrl, '/public')) {
                    $baseUrl .= '/public';
                }
            } else {
                $baseUrl = '/public';
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
 * Get the application base URL without /public
 * Used for app directory and other non-public resources
 * 
 * @return string Base URL path without /public (e.g., '/karyalayportal' or '')
 */
function get_app_base_url() {
    static $appBaseUrl = null;
    
    if ($appBaseUrl === null) {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $parts = explode('/', trim($scriptName, '/'));
        
        // Get just the first part (project directory)
        if (count($parts) >= 2) {
            $appBaseUrl = '/' . $parts[0];
        } else {
            $configUrl = getenv('APP_URL');
            if (!empty($configUrl)) {
                $parsedUrl = parse_url($configUrl);
                $appBaseUrl = !empty($parsedUrl['path']) && $parsedUrl['path'] !== '/' ? rtrim($parsedUrl['path'], '/') : '';
            } else {
                $appBaseUrl = '';
            }
        }
    }
    
    return $appBaseUrl;
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
 * Check if user has specific role (legacy - simple check)
 * Note: For multi-role support, use has_role() from admin_helpers.php
 * 
 * @param string $role Role to check
 * @return bool True if user has the role
 */
if (!function_exists('has_role')) {
    function has_role($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
}

/**
 * Check if user is admin
 * 
 * @return bool True if user is admin
 */
if (!function_exists('is_admin')) {
    function is_admin() {
        return has_role('ADMIN');
    }
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
 * Supports two signatures for backward compatibility:
 * 1. New: render_pagination($totalItems, $perPage, array $options)
 * 2. Old: render_pagination($currentPage, $totalPages, $baseUrl)
 * 
 * @param int $arg1 Total items (new) or current page (old)
 * @param int|array $arg2 Per page (new) or total pages (old)
 * @param array|string $arg3 Options array (new) or base URL (old)
 * @return string HTML pagination
 */
function render_pagination($arg1, $arg2 = 20, $arg3 = []) {
    require_once __DIR__ . '/../classes/Services/PaginationService.php';
    
    // Detect which signature is being used
    if (is_string($arg3)) {
        // Old signature: render_pagination($currentPage, $totalPages, $baseUrl)
        $currentPage = $arg1;
        $totalPages = $arg2;
        $baseUrl = $arg3;
        
        // Calculate total items from total pages (assuming 20 per page)
        $perPage = 20;
        $totalItems = $totalPages * $perPage;
        
        $pagination = new \Karyalay\Services\PaginationService($totalItems, $perPage, $currentPage, $baseUrl);
        echo $pagination->render();
    } else {
        // New signature: render_pagination($totalItems, $perPage, array $options)
        $totalItems = $arg1;
        $perPage = is_int($arg2) ? $arg2 : 20;
        $options = is_array($arg3) ? $arg3 : [];
        
        $pagination = new \Karyalay\Services\PaginationService($totalItems, $perPage);
        echo $pagination->render($options);
    }
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


/**
 * Format a price with currency symbol using localisation settings
 * 
 * @param float|int $amount The amount to format
 * @param bool $showDecimals Whether to show decimal places (default: true)
 * @return string Formatted price with currency symbol
 */
function format_price($amount, bool $showDecimals = true): string
{
    static $localisation = null;
    
    if ($localisation === null) {
        require_once __DIR__ . '/../classes/Helpers/Localisation.php';
        $localisation = \Karyalay\Helpers\Localisation::getInstance();
    }
    
    return $localisation->formatPrice($amount, $showDecimals);
}

/**
 * Get the currency symbol from localisation settings
 * 
 * @return string Currency symbol (e.g., '₹', '$')
 */
function get_currency_symbol(): string
{
    static $localisation = null;
    
    if ($localisation === null) {
        require_once __DIR__ . '/../classes/Helpers/Localisation.php';
        $localisation = \Karyalay\Helpers\Localisation::getInstance();
    }
    
    return $localisation->getCurrencySymbol();
}

/**
 * Get the currency code from localisation settings
 * 
 * @return string Currency code (e.g., 'INR', 'USD')
 */
function get_currency_code(): string
{
    static $localisation = null;
    
    if ($localisation === null) {
        require_once __DIR__ . '/../classes/Helpers/Localisation.php';
        $localisation = \Karyalay\Helpers\Localisation::getInstance();
    }
    
    return $localisation->getCurrencyCode();
}

/**
 * Get the timezone from localisation settings
 * 
 * @return string Timezone (e.g., 'Asia/Kolkata')
 */
function get_locale_timezone(): string
{
    static $localisation = null;
    
    if ($localisation === null) {
        require_once __DIR__ . '/../classes/Helpers/Localisation.php';
        $localisation = \Karyalay\Helpers\Localisation::getInstance();
    }
    
    return $localisation->getTimezone();
}

/**
 * Format a date using localisation settings
 * 
 * @param string|DateTime $date Date to format
 * @param string|null $format Optional custom format (uses locale setting if null)
 * @return string Formatted date
 */
function format_locale_date($date, ?string $format = null): string
{
    static $localisation = null;
    
    if ($localisation === null) {
        require_once __DIR__ . '/../classes/Helpers/Localisation.php';
        $localisation = \Karyalay\Helpers\Localisation::getInstance();
    }
    
    return $localisation->formatDate($date, $format);
}

/**
 * Format a datetime using localisation settings
 * 
 * @param string|DateTime $datetime DateTime to format
 * @param string|null $dateFormat Optional custom date format
 * @param string|null $timeFormat Optional custom time format
 * @return string Formatted datetime
 */
function format_locale_datetime($datetime, ?string $dateFormat = null, ?string $timeFormat = null): string
{
    static $localisation = null;
    
    if ($localisation === null) {
        require_once __DIR__ . '/../classes/Helpers/Localisation.php';
        $localisation = \Karyalay\Helpers\Localisation::getInstance();
    }
    
    return $localisation->formatDateTime($datetime, $dateFormat, $timeFormat);
}

/**
 * Get the ISD code for the configured country
 * 
 * @return string ISD code with + prefix (e.g., '+91', '+1')
 */
function get_isd_code(): string
{
    static $localisation = null;
    
    if ($localisation === null) {
        require_once __DIR__ . '/../classes/Helpers/Localisation.php';
        $localisation = \Karyalay\Helpers\Localisation::getInstance();
    }
    
    return $localisation->getIsdCode();
}

/**
 * Get the country name for the configured country
 * 
 * @return string Country name
 */
function get_country_name(): string
{
    static $localisation = null;
    
    if ($localisation === null) {
        require_once __DIR__ . '/../classes/Helpers/Localisation.php';
        $localisation = \Karyalay\Helpers\Localisation::getInstance();
    }
    
    return $localisation->getCountryName();
}

/**
 * Get the country code from localisation settings
 * 
 * @return string ISO 3166-1 alpha-2 country code (e.g., 'IN', 'US')
 */
function get_country_code(): string
{
    static $localisation = null;
    
    if ($localisation === null) {
        require_once __DIR__ . '/../classes/Helpers/Localisation.php';
        $localisation = \Karyalay\Helpers\Localisation::getInstance();
    }
    
    return $localisation->getCountryCode();
}

/**
 * Render a phone input field with auto-selected, non-changeable country ISD code
 * 
 * @param array $config Field configuration
 *   - id: Field ID (default: 'phone')
 *   - name: Field name (default: 'phone')
 *   - value: Phone number value (without ISD code)
 *   - required: Whether field is required (default: false)
 *   - error: Error message for this field
 *   - placeholder: Placeholder text (default: auto-generated)
 *   - class: Additional CSS classes for the wrapper
 *   - inputClass: Additional CSS classes for the input
 *   - disabled: Whether the input is disabled
 *   - readonly: Whether the input is readonly
 * @return string HTML for the phone input field
 */
function render_phone_input(array $config = []): string
{
    static $localisation = null;
    static $assetsIncluded = false;
    
    if ($localisation === null) {
        require_once __DIR__ . '/../classes/Helpers/Localisation.php';
        $localisation = \Karyalay\Helpers\Localisation::getInstance();
    }
    
    $id = $config['id'] ?? 'phone';
    $name = $config['name'] ?? 'phone';
    $value = $config['value'] ?? '';
    $required = $config['required'] ?? false;
    $error = $config['error'] ?? '';
    $class = $config['class'] ?? '';
    $inputClass = $config['inputClass'] ?? '';
    $disabled = $config['disabled'] ?? false;
    $readonly = $config['readonly'] ?? false;
    
    $isdCode = $localisation->getIsdCode();
    $countryCode = $localisation->getCountryCode();
    $countryName = $localisation->getCountryName();
    
    // Strip ISD code from value if present
    $cleanValue = $value;
    if (!empty($value)) {
        // Remove the ISD code if it's at the beginning
        $cleanValue = preg_replace('/^' . preg_quote($isdCode, '/') . '\s*/', '', $value);
        // Also try without the + sign
        $isdWithoutPlus = ltrim($isdCode, '+');
        $cleanValue = preg_replace('/^\+?' . preg_quote($isdWithoutPlus, '/') . '\s*/', '', $cleanValue);
    }
    
    $placeholder = $config['placeholder'] ?? 'XXXXXXXXXX';
    
    $html = '';
    
    // Include CSS only once per page
    if (!$assetsIncluded) {
        $html .= '<style>
.phone-input-wrapper {
    display: flex;
    align-items: stretch;
    border: 2px solid var(--color-gray-200, #e5e7eb);
    border-radius: var(--radius-lg, 0.5rem);
    overflow: hidden;
    transition: border-color 0.2s, box-shadow 0.2s;
    background: var(--color-white, #fff);
}
.phone-input-wrapper:focus-within {
    border-color: var(--color-primary, #667eea);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
.phone-input-wrapper.has-error {
    border-color: #dc2626;
}
.phone-input-wrapper.has-error:focus-within {
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}
.phone-isd-prefix {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.625rem 0.75rem;
    background: var(--color-gray-50, #f9fafb);
    border-right: 1px solid var(--color-gray-200, #e5e7eb);
    color: var(--color-gray-700, #374151);
    font-weight: 500;
    font-size: 0.875rem;
    white-space: nowrap;
    user-select: none;
}
.phone-isd-prefix .country-flag {
    font-size: 1.125rem;
    line-height: 1;
}
.phone-isd-prefix .isd-code {
    font-family: var(--font-mono, monospace);
    letter-spacing: 0.025em;
}
.phone-input-field {
    flex: 1;
    border: none;
    padding: 0.625rem 0.75rem;
    font-size: 1rem;
    background: transparent;
    outline: none;
    min-width: 0;
}
.phone-input-field::placeholder {
    color: var(--color-gray-400, #9ca3af);
}
.phone-input-field:disabled,
.phone-input-field:read-only {
    background: var(--color-gray-50, #f9fafb);
    color: var(--color-gray-500, #6b7280);
}
.phone-input-hidden {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
</style>';
        $assetsIncluded = true;
    }
    
    // Build wrapper classes
    $wrapperClass = 'phone-input-wrapper';
    if (!empty($class)) {
        $wrapperClass .= ' ' . esc_attr($class);
    }
    if (!empty($error)) {
        $wrapperClass .= ' has-error';
    }
    
    // Build input classes
    $inputClasses = 'phone-input-field';
    if (!empty($inputClass)) {
        $inputClasses .= ' ' . esc_attr($inputClass);
    }
    
    // Build aria attributes
    $ariaAttrs = '';
    if ($required) {
        $ariaAttrs .= ' aria-required="true"';
    }
    if (!empty($error)) {
        $ariaAttrs .= ' aria-invalid="true" aria-describedby="' . esc_attr($id) . '-error"';
    }
    
    // Get country flag emoji
    $flagEmoji = get_country_flag_emoji($countryCode);
    
    $html .= '<div class="' . $wrapperClass . '">';
    $html .= '<div class="phone-isd-prefix" title="' . esc_attr($countryName) . '">';
    $html .= '<span class="country-flag" aria-hidden="true">' . $flagEmoji . '</span>';
    $html .= '<span class="isd-code">' . esc_html($isdCode) . '</span>';
    $html .= '</div>';
    
    // Visible input for phone number (without ISD code)
    $html .= '<input type="tel" ';
    $html .= 'id="' . esc_attr($id) . '-input" ';
    $html .= 'class="' . $inputClasses . '" ';
    $html .= 'value="' . esc_attr($cleanValue) . '" ';
    $html .= 'placeholder="' . esc_attr($placeholder) . '" ';
    $html .= 'data-isd-code="' . esc_attr($isdCode) . '" ';
    $html .= 'data-country-code="' . esc_attr($countryCode) . '" ';
    $html .= 'autocomplete="tel-national" ';
    $html .= 'inputmode="tel" ';
    if ($required) {
        $html .= 'required ';
    }
    if ($disabled) {
        $html .= 'disabled ';
    }
    if ($readonly) {
        $html .= 'readonly ';
    }
    $html .= $ariaAttrs;
    $html .= '>';
    
    // Hidden input that stores the full phone number with ISD code
    $fullValue = !empty($cleanValue) ? $isdCode . $cleanValue : '';
    $html .= '<input type="hidden" ';
    $html .= 'id="' . esc_attr($id) . '" ';
    $html .= 'name="' . esc_attr($name) . '" ';
    $html .= 'value="' . esc_attr($fullValue) . '" ';
    $html .= 'class="phone-input-hidden">';
    
    $html .= '</div>';
    
    // JavaScript to sync visible input with hidden input
    $html .= '<script>
(function() {
    var visibleInput = document.getElementById("' . esc_attr($id) . '-input");
    var hiddenInput = document.getElementById("' . esc_attr($id) . '");
    var isdCode = "' . esc_attr($isdCode) . '";
    
    if (visibleInput && hiddenInput) {
        visibleInput.addEventListener("input", function() {
            var value = this.value.replace(/[^\d]/g, "");
            this.value = value;
            hiddenInput.value = value ? isdCode + value : "";
        });
        
        // Handle paste events
        visibleInput.addEventListener("paste", function(e) {
            e.preventDefault();
            var pastedText = (e.clipboardData || window.clipboardData).getData("text");
            // Remove ISD code if pasted
            pastedText = pastedText.replace(/^\\+?\\d{1,4}\\s*/, "");
            pastedText = pastedText.replace(/[^\d]/g, "");
            this.value = pastedText;
            hiddenInput.value = pastedText ? isdCode + pastedText : "";
        });
    }
})();
</script>';
    
    return $html;
}

/**
 * Get country flag emoji from country code
 * 
 * @param string $countryCode ISO 3166-1 alpha-2 country code
 * @return string Flag emoji
 */
function get_country_flag_emoji(string $countryCode): string
{
    $countryCode = strtoupper($countryCode);
    if (strlen($countryCode) !== 2) {
        return '🏳️';
    }
    
    // Convert country code to regional indicator symbols
    $firstChar = ord($countryCode[0]) - ord('A') + 0x1F1E6;
    $secondChar = ord($countryCode[1]) - ord('A') + 0x1F1E6;
    
    return mb_chr($firstChar) . mb_chr($secondChar);
}

/**
 * Get phone input configuration as JSON for JavaScript
 * 
 * @return string JSON-encoded configuration
 */
function get_phone_input_config_json(): string
{
    static $localisation = null;
    
    if ($localisation === null) {
        require_once __DIR__ . '/../classes/Helpers/Localisation.php';
        $localisation = \Karyalay\Helpers\Localisation::getInstance();
    }
    
    return json_encode($localisation->getPhoneInputConfig());
}
/**
 * Get the footer company description from settings
 * Uses static caching to avoid multiple database queries per request
 * 
 * @return string Company description for footer
 */
function get_footer_company_description(): string
{
    static $description = null;
    
    // Return cached value if already retrieved
    if ($description !== null) {
        return $description;
    }
    
    $fallback = 'Comprehensive business management system designed to streamline your operations and boost productivity.';
    
    try {
        require_once __DIR__ . '/../classes/Models/Setting.php';
        
        $setting = new \Karyalay\Models\Setting();
        $value = $setting->get('footer_company_description');
        
        // Check if value is empty or whitespace-only
        if ($value === null || trim($value) === '') {
            $description = $fallback;
        } else {
            $description = $value;
        }
    } catch (\Exception $e) {
        // Log the error and return fallback
        error_log("Error retrieving footer company description: " . $e->getMessage());
        $description = $fallback;
    }
    
    return $description;
}

/**
 * Get the footer copyright text from settings
 * Uses static caching to avoid multiple database queries per request
 * 
 * @return string Copyright text for footer (without year and company name)
 */
function get_footer_copyright_text(): string
{
    static $copyrightText = null;
    
    // Return cached value if already retrieved
    if ($copyrightText !== null) {
        return $copyrightText;
    }
    
    $fallback = 'All rights reserved.';
    
    try {
        require_once __DIR__ . '/../classes/Models/Setting.php';
        
        $setting = new \Karyalay\Models\Setting();
        $value = $setting->get('footer_copyright_text');
        
        // Check if value is empty or whitespace-only
        if ($value === null || trim($value) === '') {
            $copyrightText = $fallback;
        } else {
            $copyrightText = $value;
        }
    } catch (\Exception $e) {
        // Log the error and return fallback
        error_log("Error retrieving footer copyright text: " . $e->getMessage());
        $copyrightText = $fallback;
    }
    
    return $copyrightText;
}

/**
 * Get the complete footer copyright line
 * Combines year, brand name, and copyright text
 * 
 * @return string Complete copyright line
 */
function get_footer_copyright_line(): string
{
    $year = date('Y');
    $brandName = get_brand_name();
    $copyrightText = get_footer_copyright_text();
    
    return "&copy; {$year} {$brandName}. {$copyrightText}";
}