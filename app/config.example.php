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
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'your_smtp_user',
        'password' => 'your_smtp_password',
        'encryption' => 'tls', // 'tls' or 'ssl'
        'from_address' => 'no-reply@your-domain.com',
        'from_name' => 'SikaPay',
    ],

    'security' => [
        'session_lifetime' => 7200, // 2 hours in seconds
        'csrf_protection' => true,
    ],
];
