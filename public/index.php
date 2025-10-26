<?php
// public/index.php

// Note: Ensure the required classes are available via autoloading
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
// NEW IMPORTS:
use Jeffrey\Sikapay\Security\CsrfToken;
use Jeffrey\Sikapay\Middleware\CsrfMiddleware;

// Define a simple, immediate exception handler for bootstrap failures
set_exception_handler(function (\Throwable $e) {
    // ... (Existing exception handler code remains the same)
    if (class_exists(Log::class)) {
        Log::critical("FATAL BOOTSTRAP FAILURE: Uncaught exception outside application run. " . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        if (class_exists(ErrorResponder::class)) {
            ErrorResponder::respond(500, "A critical system error occurred during startup.");
        } else {
            http_response_code(500);
            echo "<h1>500 Internal Server Error</h1><p>A fatal error occurred during startup.</p>";
        }
    } else {
        http_response_code(500);
        error_log("CRITICAL: Autoloader or logging failed. Message: " . $e->getMessage());
        echo "<h1>500 Internal Server Error</h1><p>The system failed to initialize. Check server logs.</p>";
    }
    exit(1);
});


require_once __DIR__ . '/../vendor/autoload.php';


// ==========================================================
// 1. SESSION START & CSRF INITIALIZATION (CRITICAL SECURITY BLOCK)
// ==========================================================

// Start the session immediately. This allows CSRF token access and Auth checks.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure the CSRF token is initialized in the session for the page being rendered.
CsrfToken::init();

// ENFORCE CSRF PROTECTION: Check if the incoming request token is valid.
// This must run before any controller logic executes.
CsrfMiddleware::enforce();


// ==========================================================
// 2. LOAD & RUN APPLICATION
// ==========================================================

// Load the Bootstrap file and get the application instance
$app = require_once __DIR__ . '/../bootstrap/app.php';

// 3. Run the application (This triggers the dispatch logic)
$app->run();