<?php
/**
 * CDN Configuration
 * 
 * This file contains configuration for Content Delivery Network (CDN) integration
 * for static assets to improve performance and reduce server load.
 */

return [
    // Enable or disable CDN usage
    'enabled' => getenv('CDN_ENABLED') === 'true',
    
    // CDN base URL (e.g., https://cdn.example.com or CloudFront URL)
    'base_url' => getenv('CDN_BASE_URL') ?: '',
    
    // Asset types to serve from CDN
    'asset_types' => [
        'css' => true,
        'js' => true,
        'images' => true,
        'fonts' => true,
    ],
    
    // Cache control settings for different asset types
    'cache_control' => [
        'css' => 'public, max-age=31536000, immutable',
        'js' => 'public, max-age=31536000, immutable',
        'images' => 'public, max-age=31536000, immutable',
        'fonts' => 'public, max-age=31536000, immutable',
    ],
    
    // CDN providers configuration examples
    'providers' => [
        // CloudFront example
        'cloudfront' => [
            'distribution_id' => getenv('CLOUDFRONT_DISTRIBUTION_ID') ?: '',
            'domain' => getenv('CLOUDFRONT_DOMAIN') ?: '',
        ],
        
        // Cloudflare example
        'cloudflare' => [
            'zone_id' => getenv('CLOUDFLARE_ZONE_ID') ?: '',
            'domain' => getenv('CLOUDFLARE_DOMAIN') ?: '',
        ],
        
        // Custom CDN
        'custom' => [
            'domain' => getenv('CUSTOM_CDN_DOMAIN') ?: '',
        ],
    ],
    
    // Fallback to local assets if CDN fails
    'fallback_enabled' => true,
    
    // Assets to exclude from CDN (serve locally)
    'exclude_patterns' => [
        // Example: '/admin/*' to exclude admin assets
    ],
];
