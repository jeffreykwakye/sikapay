<?php
// public/index.php

// 1. Load Composer Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Start Session Management immediately (needed for Auth)
\Jeffrey\Sikapay\Core\SessionManager::start();

// 3. Load the Bootstrap file and get the application instance
$app = require_once __DIR__ . '/../bootstrap/app.php';

// 4. Run the application
$app->run();