<?php

/**
 * Application Configuration
 * 
 * This file contains general application settings.
 */

return [
    'name' => 'SellerPortal',
    'env' => getenv('APP_ENV') ?: 'development',
    'debug' => getenv('APP_DEBUG') === 'true',
    'url' => getenv('APP_URL') ?: 'http://localhost',
    
    // Session configuration
    'session' => [
        'lifetime' => 120, // minutes
        'cookie_name' => 'karyalay_session',
        'cookie_secure' => getenv('APP_ENV') === 'production',
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ],
    
    // Security
    'csrf_token_name' => 'csrf_token',
    'password_cost' => 12, // bcrypt cost factor
    
    // Pagination
    'items_per_page' => 20,
    
    // Payment Gateway (Razorpay)
    'razorpay_key_id' => getenv('RAZORPAY_KEY_ID') ?: '',
    'razorpay_key_secret' => getenv('RAZORPAY_KEY_SECRET') ?: '',
    'razorpay_webhook_secret' => getenv('RAZORPAY_WEBHOOK_SECRET') ?: '',
    
    // Admin email
    'admin_email' => getenv('ADMIN_EMAIL') ?: 'admin@example.com',
];
