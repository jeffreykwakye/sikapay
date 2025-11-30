<?php

// SikaPay Production Configuration
// Copy this file to config.php and fill in your production details.
// This file should NOT be committed to version control.

return [
    'app' => [
        'name' => 'SikaPay',
        'env' => 'production', // Should be 'production' on your server
        'url' => 'http://your-production-domain.com',
    ],

    'db' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'sikapay_db',
        'user' => 'db_user',
        'password' => 'db_password',
    ],

    'mail' => [
        'host' => 'sikapay.nexusonegh.com',
        'port' => 465,
        'username' => 'admin@sikapay.nexusonegh.com',
        'password' => 'YOUR_EMAIL_ACCOUNT_PASSWORD', // Replace with your actual email password
        'encryption' => 'ssl', // 'tls' or 'ssl'
        'from_address' => 'admin@sikapay.nexusonegh.com',
        'from_name' => 'SikaPay',
    ],

    'security' => [
        'session_lifetime' => 7200, // 2 hours in seconds
        'csrf_protection' => true,
    ],
];
