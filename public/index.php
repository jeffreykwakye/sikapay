<?php
// public/index.php

// 1. Load Composer Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Start Session Management immediately (Assuming SessionManager::start() is implemented)
// If you don't have this class, just use session_start()
if (!class_exists('\\Jeffrey\\Sikapay\\Core\\SessionManager')) {
    session_start();
} else {
    \Jeffrey\Sikapay\Core\SessionManager::start();
}

// 3. Load the Bootstrap file and get the application instance
$app = require_once __DIR__ . '/../bootstrap/app.php';

// 4. Run the application (This triggers the dispatch logic)
$app->run();