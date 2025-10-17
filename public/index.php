<?php
// public/index.php

use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;

// Define a simple, immediate exception handler for bootstrap failures
set_exception_handler(function (\Throwable $e) {
    // This is the absolute fallback for errors before our system is fully initialized
    if (class_exists(Log::class)) {
        Log::critical("FATAL BOOTSTRAP FAILURE: Uncaught exception outside application run. " . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        // If ErrorResponder is available, use it for a clean 500 page
        if (class_exists(ErrorResponder::class)) {
            ErrorResponder::respond(500, "A critical system error occurred during startup.");
        } else {
            // Raw fallback
            http_response_code(500);
            echo "<h1>500 Internal Server Error</h1><p>A fatal error occurred during startup.</p>";
        }
    } else {
        // Fallback if the autoloader/Log class failed (EXTREMELY rare)
        http_response_code(500);
        error_log("CRITICAL: Autoloader or logging failed. Message: " . $e->getMessage());
        echo "<h1>500 Internal Server Error</h1><p>The system failed to initialize. Check server logs.</p>";
    }
    exit(1);
});


require_once __DIR__ . '/../vendor/autoload.php';

// 2. Load the Bootstrap file and get the application instance
// The bootstrap file (bootstrap/app.php) now handles all initial setup: 
// SessionManager::start() and Database::connect().
$app = require_once __DIR__ . '/../bootstrap/app.php';

// 3. Run the application (This triggers the dispatch logic)
$app->run();