<?php
/**
 * Debug Session - Temporary file to debug session issues
 */

// Load authentication helpers
require_once __DIR__ . '/../includes/auth_helpers.php';

// Start secure session
startSecureSession();

echo "<h1>Session Debug</h1>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "\nSession Data:\n";
print_r($_SESSION);
echo "\nCookies:\n";
print_r($_COOKIE);
echo "</pre>";

echo "<h2>Authentication Check</h2>";
echo "user_id set: " . (isset($_SESSION['user_id']) ? 'YES - ' . $_SESSION['user_id'] : 'NO') . "<br>";
echo "session_token set: " . (isset($_SESSION['session_token']) ? 'YES' : 'NO') . "<br>";
echo "isAuthenticated(): " . (isAuthenticated() ? 'TRUE' : 'FALSE') . "<br>";
