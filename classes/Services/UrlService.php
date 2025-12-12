<?php

namespace Karyalay\Services;

/**
 * URL Service
 * 
 * Handles URL resolution for the application, supporting dual-environment setup.
 * Provides consistent base URL resolution across localhost and production environments.
 * 
 * Requirements: 7.1, 7.2, 7.4
 */
class UrlService
{
    /**
     * Resolve the base URL for the application
     * 
     * Resolution priority:
     * 1. If APP_URL is configured, use it as the base
     * 2. If APP_URL is not configured, detect from current request
     * 3. Protocol matches the detected request protocol (HTTP or HTTPS)
     * 
     * @param array|null $serverVars Optional server variables for testing (defaults to $_SERVER)
     * @return string The resolved base URL (e.g., 'https://example.com/karyalayportal')
     */
    public function resolveBaseUrl(?array $serverVars = null): string
    {
        $server = $serverVars ?? $_SERVER;
        
        // First, check if APP_URL is configured
        $appUrl = $this->getAppUrl();
        
        if (!empty($appUrl)) {
            // APP_URL is configured - use it but respect detected protocol
            return $this->normalizeAppUrl($appUrl, $server);
        }
        
        // APP_URL not configured - detect from request
        return $this->detectBaseUrlFromRequest($server);
    }

    /**
     * Get the APP_URL environment variable
     * 
     * @return string|null The APP_URL value or null if not set
     */
    public function getAppUrl(): ?string
    {
        $appUrl = getenv('APP_URL');
        
        if ($appUrl === false || $appUrl === '') {
            return null;
        }
        
        return $appUrl;
    }

    /**
     * Normalize APP_URL to respect detected protocol
     * 
     * If APP_URL specifies HTTP but request is HTTPS (or vice versa),
     * use the detected protocol to avoid mixed content issues.
     * 
     * @param string $appUrl The configured APP_URL
     * @param array $server Server variables
     * @return string Normalized URL with correct protocol
     */
    private function normalizeAppUrl(string $appUrl, array $server): string
    {
        $detectedProtocol = $this->detectProtocol($server);
        
        // Parse the APP_URL
        $parsed = parse_url($appUrl);
        
        if ($parsed === false) {
            // Invalid URL, return as-is
            return rtrim($appUrl, '/');
        }
        
        // If APP_URL has a scheme, check if it matches detected protocol
        if (isset($parsed['scheme'])) {
            $configuredProtocol = $parsed['scheme'] . '://';
            
            // Replace protocol with detected one
            if ($configuredProtocol !== $detectedProtocol) {
                $appUrl = $detectedProtocol . substr($appUrl, strlen($configuredProtocol));
            }
        } else {
            // No scheme in APP_URL, prepend detected protocol
            $appUrl = $detectedProtocol . $appUrl;
        }
        
        return rtrim($appUrl, '/');
    }

    /**
     * Detect the base URL from the current request
     * 
     * @param array $server Server variables
     * @return string Detected base URL
     */
    private function detectBaseUrlFromRequest(array $server): string
    {
        $protocol = $this->detectProtocol($server);
        $host = $this->detectHost($server);
        $basePath = $this->detectBasePath($server);
        
        return $protocol . $host . $basePath;
    }

    /**
     * Detect the protocol (HTTP or HTTPS) from the request
     * 
     * Checks multiple indicators:
     * - HTTPS server variable
     * - X-Forwarded-Proto header (for reverse proxies)
     * - Server port
     * 
     * @param array $server Server variables
     * @return string 'https://' or 'http://'
     */
    public function detectProtocol(array $server): string
    {
        // Check HTTPS server variable
        if (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off') {
            return 'https://';
        }
        
        // Check X-Forwarded-Proto header (common with reverse proxies/load balancers)
        if (!empty($server['HTTP_X_FORWARDED_PROTO'])) {
            if (strtolower($server['HTTP_X_FORWARDED_PROTO']) === 'https') {
                return 'https://';
            }
        }
        
        // Check server port
        if (!empty($server['SERVER_PORT']) && (int)$server['SERVER_PORT'] === 443) {
            return 'https://';
        }
        
        return 'http://';
    }

    /**
     * Detect the host from the request
     * 
     * Checks multiple sources:
     * - HTTP_HOST header
     * - SERVER_NAME
     * - X-Forwarded-Host header (for reverse proxies)
     * 
     * @param array $server Server variables
     * @return string The detected host (e.g., 'example.com' or 'localhost:8080')
     */
    public function detectHost(array $server): string
    {
        // Check X-Forwarded-Host first (for reverse proxies)
        if (!empty($server['HTTP_X_FORWARDED_HOST'])) {
            // X-Forwarded-Host can contain multiple hosts, use the first one
            $hosts = explode(',', $server['HTTP_X_FORWARDED_HOST']);
            return trim($hosts[0]);
        }
        
        // Check HTTP_HOST (most common)
        if (!empty($server['HTTP_HOST'])) {
            return $server['HTTP_HOST'];
        }
        
        // Fall back to SERVER_NAME
        if (!empty($server['SERVER_NAME'])) {
            $host = $server['SERVER_NAME'];
            
            // Add port if non-standard
            if (!empty($server['SERVER_PORT'])) {
                $port = (int)$server['SERVER_PORT'];
                if ($port !== 80 && $port !== 443) {
                    $host .= ':' . $port;
                }
            }
            
            return $host;
        }
        
        // Ultimate fallback
        return 'localhost';
    }

    /**
     * Detect the base path from the request
     * 
     * Determines the application's base path (subdirectory) from the script path.
     * Removes the /install, /public, /admin, /app segments to get the root.
     * 
     * @param array $server Server variables
     * @return string The base path (e.g., '/karyalayportal' or '')
     */
    public function detectBasePath(array $server): string
    {
        $scriptName = $server['SCRIPT_NAME'] ?? '';
        
        if (empty($scriptName)) {
            return '';
        }
        
        // Get directory of the script
        $scriptDir = dirname($scriptName);
        
        // Remove known subdirectories to get the application root
        $knownSubdirs = ['/install', '/public', '/admin', '/app', '/api'];
        
        foreach ($knownSubdirs as $subdir) {
            if (str_ends_with($scriptDir, $subdir)) {
                $scriptDir = substr($scriptDir, 0, -strlen($subdir));
                break;
            }
            
            // Also check for nested paths like /install/api
            $pos = strpos($scriptDir, $subdir . '/');
            if ($pos !== false) {
                $scriptDir = substr($scriptDir, 0, $pos);
                break;
            }
        }
        
        // Clean up the path
        $scriptDir = rtrim($scriptDir, '/');
        
        // Handle root case
        if ($scriptDir === '' || $scriptDir === '/' || $scriptDir === '\\') {
            return '';
        }
        
        return $scriptDir;
    }

    /**
     * Generate a full URL for a given path
     * 
     * @param string $path The path relative to application root (e.g., '/admin/dashboard.php')
     * @param array|null $serverVars Optional server variables for testing
     * @return string Full URL
     */
    public function url(string $path, ?array $serverVars = null): string
    {
        $baseUrl = $this->resolveBaseUrl($serverVars);
        $path = '/' . ltrim($path, '/');
        
        return $baseUrl . $path;
    }

    /**
     * Generate the admin dashboard URL
     * 
     * @param array|null $serverVars Optional server variables for testing
     * @return string Full URL to admin dashboard
     */
    public function getAdminDashboardUrl(?array $serverVars = null): string
    {
        return $this->url('/admin/dashboard.php', $serverVars);
    }

    /**
     * Generate the public homepage URL
     * 
     * @param array|null $serverVars Optional server variables for testing
     * @return string Full URL to homepage
     */
    public function getHomepageUrl(?array $serverVars = null): string
    {
        return $this->url('/', $serverVars);
    }
}
