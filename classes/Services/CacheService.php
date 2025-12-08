<?php

namespace Karyalay\Services;

/**
 * Cache Service
 * 
 * Provides caching functionality for query results and frequently accessed data.
 * Supports multiple cache backends: APCu, file-based, and in-memory.
 */
class CacheService
{
    private static ?string $cacheDriver = null;
    private static array $memoryCache = [];
    private static string $cacheDir = '';

    /**
     * Initialize cache service
     * 
     * @return void
     */
    public static function init(): void
    {
        // Determine available cache driver
        if (extension_loaded('apcu') && ini_get('apc.enabled')) {
            self::$cacheDriver = 'apcu';
        } else {
            self::$cacheDriver = 'file';
            self::$cacheDir = __DIR__ . '/../../storage/cache';
            
            // Create cache directory if it doesn't exist
            if (!is_dir(self::$cacheDir)) {
                mkdir(self::$cacheDir, 0755, true);
            }
        }
    }

    /**
     * Get cache driver
     * 
     * @return string
     */
    public static function getDriver(): string
    {
        if (self::$cacheDriver === null) {
            self::init();
        }
        
        return self::$cacheDriver;
    }

    /**
     * Store a value in cache
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds (default: 3600 = 1 hour)
     * @return bool Success status
     */
    public static function set(string $key, $value, int $ttl = 3600): bool
    {
        if (self::$cacheDriver === null) {
            self::init();
        }

        // Always store in memory cache for current request
        self::$memoryCache[$key] = [
            'value' => $value,
            'expires' => time() + $ttl
        ];

        // Store in persistent cache
        switch (self::$cacheDriver) {
            case 'apcu':
                return apcu_store($key, $value, $ttl);
                
            case 'file':
                return self::setFileCache($key, $value, $ttl);
                
            default:
                return true; // Memory cache only
        }
    }

    /**
     * Retrieve a value from cache
     * 
     * @param string $key Cache key
     * @param mixed $default Default value if key not found
     * @return mixed Cached value or default
     */
    public static function get(string $key, $default = null)
    {
        if (self::$cacheDriver === null) {
            self::init();
        }

        // Check memory cache first
        if (isset(self::$memoryCache[$key])) {
            $cached = self::$memoryCache[$key];
            if ($cached['expires'] > time()) {
                return $cached['value'];
            } else {
                unset(self::$memoryCache[$key]);
            }
        }

        // Check persistent cache
        switch (self::$cacheDriver) {
            case 'apcu':
                $value = apcu_fetch($key, $success);
                if ($success) {
                    // Store in memory cache for current request
                    self::$memoryCache[$key] = [
                        'value' => $value,
                        'expires' => time() + 3600
                    ];
                    return $value;
                }
                break;
                
            case 'file':
                $value = self::getFileCache($key);
                if ($value !== null) {
                    // Store in memory cache for current request
                    self::$memoryCache[$key] = [
                        'value' => $value,
                        'expires' => time() + 3600
                    ];
                    return $value;
                }
                break;
        }

        return $default;
    }

    /**
     * Check if a key exists in cache
     * 
     * @param string $key Cache key
     * @return bool
     */
    public static function has(string $key): bool
    {
        if (self::$cacheDriver === null) {
            self::init();
        }

        // Check memory cache
        if (isset(self::$memoryCache[$key])) {
            if (self::$memoryCache[$key]['expires'] > time()) {
                return true;
            } else {
                unset(self::$memoryCache[$key]);
            }
        }

        // Check persistent cache
        switch (self::$cacheDriver) {
            case 'apcu':
                return apcu_exists($key);
                
            case 'file':
                return self::getFileCache($key) !== null;
                
            default:
                return false;
        }
    }

    /**
     * Delete a value from cache
     * 
     * @param string $key Cache key
     * @return bool Success status
     */
    public static function delete(string $key): bool
    {
        if (self::$cacheDriver === null) {
            self::init();
        }

        // Remove from memory cache
        unset(self::$memoryCache[$key]);

        // Remove from persistent cache
        switch (self::$cacheDriver) {
            case 'apcu':
                return apcu_delete($key);
                
            case 'file':
                return self::deleteFileCache($key);
                
            default:
                return true;
        }
    }

    /**
     * Clear all cache entries
     * 
     * @return bool Success status
     */
    public static function clear(): bool
    {
        if (self::$cacheDriver === null) {
            self::init();
        }

        // Clear memory cache
        self::$memoryCache = [];

        // Clear persistent cache
        switch (self::$cacheDriver) {
            case 'apcu':
                return apcu_clear_cache();
                
            case 'file':
                return self::clearFileCache();
                
            default:
                return true;
        }
    }

    /**
     * Remember a value in cache (get or set)
     * 
     * @param string $key Cache key
     * @param callable $callback Callback to generate value if not cached
     * @param int $ttl Time to live in seconds
     * @return mixed Cached or generated value
     */
    public static function remember(string $key, callable $callback, int $ttl = 3600)
    {
        $value = self::get($key);
        
        if ($value === null) {
            $value = $callback();
            self::set($key, $value, $ttl);
        }
        
        return $value;
    }

    /**
     * Store value in file cache
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live
     * @return bool Success status
     */
    private static function setFileCache(string $key, $value, int $ttl): bool
    {
        $filename = self::getCacheFilename($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        return file_put_contents($filename, serialize($data)) !== false;
    }

    /**
     * Retrieve value from file cache
     * 
     * @param string $key Cache key
     * @return mixed|null Cached value or null
     */
    private static function getFileCache(string $key)
    {
        $filename = self::getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($filename));
        
        if ($data['expires'] < time()) {
            unlink($filename);
            return null;
        }
        
        return $data['value'];
    }

    /**
     * Delete value from file cache
     * 
     * @param string $key Cache key
     * @return bool Success status
     */
    private static function deleteFileCache(string $key): bool
    {
        $filename = self::getCacheFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }

    /**
     * Clear all file cache
     * 
     * @return bool Success status
     */
    private static function clearFileCache(): bool
    {
        $files = glob(self::$cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }

    /**
     * Get cache filename for a key
     * 
     * @param string $key Cache key
     * @return string Filename
     */
    private static function getCacheFilename(string $key): string
    {
        return self::$cacheDir . '/' . md5($key) . '.cache';
    }

    /**
     * Get cache statistics
     * 
     * @return array Cache statistics
     */
    public static function getStats(): array
    {
        if (self::$cacheDriver === null) {
            self::init();
        }

        $stats = [
            'driver' => self::$cacheDriver,
            'memory_cache_size' => count(self::$memoryCache),
        ];

        switch (self::$cacheDriver) {
            case 'apcu':
                $info = apcu_cache_info();
                $stats['apcu_memory_size'] = $info['mem_size'] ?? 0;
                $stats['apcu_num_entries'] = $info['num_entries'] ?? 0;
                break;
                
            case 'file':
                $files = glob(self::$cacheDir . '/*.cache');
                $stats['file_cache_entries'] = count($files);
                $stats['file_cache_size'] = array_sum(array_map('filesize', $files));
                break;
        }

        return $stats;
    }
}
