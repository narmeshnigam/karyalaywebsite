<?php

namespace Karyalay\Services;

/**
 * Input Sanitization Service
 * 
 * Provides utilities for sanitizing user input to prevent XSS and SQL injection
 */
class InputSanitizationService
{
    /**
     * Sanitize string input for XSS prevention
     * Removes HTML tags and encodes special characters
     * 
     * @param string|null $input The input to sanitize
     * @return string The sanitized input
     */
    public function sanitizeString(?string $input): string
    {
        if ($input === null) {
            return '';
        }
        
        // Remove HTML tags
        $sanitized = strip_tags($input);
        
        // Remove javascript: protocol
        $sanitized = preg_replace('/javascript:/i', '', $sanitized);
        
        // Remove data: protocol
        $sanitized = preg_replace('/data:/i', '', $sanitized);
        
        // Encode special characters (but don't double-encode)
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
        
        return $sanitized;
    }

    /**
     * Sanitize HTML input - allows safe HTML tags
     * Useful for rich text content
     * 
     * @param string|null $input The HTML input to sanitize
     * @param array $allowedTags Array of allowed HTML tags (default: safe tags)
     * @return string The sanitized HTML
     */
    public function sanitizeHtml(?string $input, array $allowedTags = []): string
    {
        if ($input === null) {
            return '';
        }
        
        // Default safe tags if none specified
        if (empty($allowedTags)) {
            $allowedTags = [
                'p', 'br', 'strong', 'em', 'u', 'a', 'ul', 'ol', 'li',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'code', 'pre'
            ];
        }
        
        // Build allowed tags string for strip_tags
        $allowedTagsString = '<' . implode('><', $allowedTags) . '>';
        
        // Remove disallowed tags
        $sanitized = strip_tags($input, $allowedTagsString);
        
        // Remove dangerous attributes from allowed tags
        $sanitized = $this->removeDangerousAttributes($sanitized);
        
        return $sanitized;
    }

    /**
     * Sanitize email input
     * 
     * @param string|null $input The email to sanitize
     * @return string The sanitized email
     */
    public function sanitizeEmail(?string $input): string
    {
        if ($input === null) {
            return '';
        }
        
        // Remove all characters except letters, numbers, and email characters
        $sanitized = filter_var($input, FILTER_SANITIZE_EMAIL);
        
        return $sanitized ?: '';
    }

    /**
     * Sanitize URL input
     * 
     * @param string|null $input The URL to sanitize
     * @return string The sanitized URL
     */
    public function sanitizeUrl(?string $input): string
    {
        if ($input === null) {
            return '';
        }
        
        // Remove all characters except those allowed in URLs
        $sanitized = filter_var($input, FILTER_SANITIZE_URL);
        
        if (!$sanitized) {
            return '';
        }
        
        // Remove dangerous protocols
        $sanitized = preg_replace('/^javascript:/i', '', $sanitized);
        $sanitized = preg_replace('/^data:/i', '', $sanitized);
        $sanitized = preg_replace('/^vbscript:/i', '', $sanitized);
        
        return $sanitized;
    }

    /**
     * Sanitize integer input
     * 
     * @param mixed $input The input to sanitize
     * @return int The sanitized integer
     */
    public function sanitizeInt($input): int
    {
        return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize float input
     * 
     * @param mixed $input The input to sanitize
     * @return float The sanitized float
     */
    public function sanitizeFloat($input): float
    {
        return (float) filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Sanitize array of inputs
     * 
     * @param array $input The array to sanitize
     * @param string $type The type of sanitization (string, email, url, int, float, html)
     * @return array The sanitized array
     */
    public function sanitizeArray(array $input, string $type = 'string'): array
    {
        $sanitized = [];
        
        foreach ($input as $key => $value) {
            $sanitizedKey = $this->sanitizeString($key);
            
            if (is_array($value)) {
                $sanitized[$sanitizedKey] = $this->sanitizeArray($value, $type);
            } else {
                $sanitized[$sanitizedKey] = $this->sanitizeByType($value, $type);
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize input based on type
     * 
     * @param mixed $input The input to sanitize
     * @param string $type The type of sanitization
     * @return mixed The sanitized input
     */
    public function sanitizeByType($input, string $type)
    {
        switch ($type) {
            case 'email':
                return $this->sanitizeEmail($input);
            case 'url':
                return $this->sanitizeUrl($input);
            case 'int':
                return $this->sanitizeInt($input);
            case 'float':
                return $this->sanitizeFloat($input);
            case 'html':
                return $this->sanitizeHtml($input);
            case 'string':
            default:
                return $this->sanitizeString($input);
        }
    }

    /**
     * Sanitize all POST data
     * 
     * @param array|null $data The POST data (defaults to $_POST)
     * @param array $types Array mapping field names to sanitization types
     * @return array The sanitized POST data
     */
    public function sanitizePostData(?array $data = null, array $types = []): array
    {
        $data = $data ?? $_POST;
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $sanitizedKey = $this->sanitizeString($key);
            $type = $types[$key] ?? 'string';
            
            if (is_array($value)) {
                $sanitized[$sanitizedKey] = $this->sanitizeArray($value, $type);
            } else {
                $sanitized[$sanitizedKey] = $this->sanitizeByType($value, $type);
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize all GET data
     * 
     * @param array|null $data The GET data (defaults to $_GET)
     * @param array $types Array mapping field names to sanitization types
     * @return array The sanitized GET data
     */
    public function sanitizeGetData(?array $data = null, array $types = []): array
    {
        $data = $data ?? $_GET;
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $sanitizedKey = $this->sanitizeString($key);
            $type = $types[$key] ?? 'string';
            
            if (is_array($value)) {
                $sanitized[$sanitizedKey] = $this->sanitizeArray($value, $type);
            } else {
                $sanitized[$sanitizedKey] = $this->sanitizeByType($value, $type);
            }
        }
        
        return $sanitized;
    }

    /**
     * Remove dangerous attributes from HTML
     * Removes event handlers and javascript: URLs
     * 
     * @param string $html The HTML to clean
     * @return string The cleaned HTML
     */
    private function removeDangerousAttributes(string $html): string
    {
        // Remove event handler attributes (onclick, onload, etc.)
        $html = preg_replace('/\s*on\w+\s*=\s*["\'].*?["\']/i', '', $html);
        
        // Remove javascript: URLs
        $html = preg_replace('/href\s*=\s*["\']javascript:.*?["\']/i', '', $html);
        
        // Remove data: URLs (can be used for XSS)
        $html = preg_replace('/href\s*=\s*["\']data:.*?["\']/i', '', $html);
        
        // Remove style attributes (can contain javascript)
        $html = preg_replace('/\s*style\s*=\s*["\'].*?["\']/i', '', $html);
        
        return $html;
    }

    /**
     * Escape output for safe display in HTML
     * Use this when displaying user-generated content
     * 
     * @param string|null $output The output to escape
     * @return string The escaped output
     */
    public function escapeOutput(?string $output): string
    {
        if ($output === null) {
            return '';
        }
        
        return htmlspecialchars($output, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Escape output for safe use in JavaScript
     * 
     * @param string|null $output The output to escape
     * @return string The escaped output
     */
    public function escapeJs(?string $output): string
    {
        if ($output === null) {
            return '';
        }
        
        // Escape for JavaScript context - order matters!
        $output = str_replace('\\', '\\\\', $output);  // Escape backslashes first
        $output = str_replace("'", "\\'", $output);     // Escape single quotes
        $output = str_replace('"', '\\"', $output);     // Escape double quotes
        $output = str_replace("\n", '\\n', $output);    // Escape newlines
        $output = str_replace("\r", '\\r', $output);    // Escape carriage returns
        $output = str_replace('<', '\\x3C', $output);   // Escape < to prevent </script>
        $output = str_replace('>', '\\x3E', $output);   // Escape >
        $output = str_replace(';', '\\x3B', $output);   // Escape semicolons
        
        return $output;
    }

    /**
     * Validate and sanitize file upload
     * 
     * @param array $file The file from $_FILES
     * @param array $allowedTypes Array of allowed MIME types
     * @param int $maxSize Maximum file size in bytes
     * @return array ['success' => bool, 'error' => string|null, 'sanitized_name' => string|null]
     */
    public function sanitizeFileUpload(array $file, array $allowedTypes = [], int $maxSize = 5242880): array
    {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return [
                'success' => false,
                'error' => 'No file uploaded or invalid upload',
                'sanitized_name' => null
            ];
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            return [
                'success' => false,
                'error' => 'File size exceeds maximum allowed size',
                'sanitized_name' => null
            ];
        }
        
        // Check MIME type if specified
        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                return [
                    'success' => false,
                    'error' => 'File type not allowed',
                    'sanitized_name' => null
                ];
            }
        }
        
        // Sanitize filename
        $filename = $file['name'];
        $sanitizedName = $this->sanitizeFilename($filename);
        
        return [
            'success' => true,
            'error' => null,
            'sanitized_name' => $sanitizedName
        ];
    }

    /**
     * Sanitize filename
     * 
     * @param string $filename The filename to sanitize
     * @return string The sanitized filename
     */
    public function sanitizeFilename(string $filename): string
    {
        // Get file extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        
        // Remove special characters from basename
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
        
        // Limit length
        $basename = substr($basename, 0, 100);
        
        // Sanitize extension
        $extension = preg_replace('/[^a-zA-Z0-9]/', '', $extension);
        
        return $basename . '.' . $extension;
    }
}
