<?php
declare(strict_types=1); // Added strict typing

use Jeffrey\Sikapay\Core\Application;
use Jeffrey\Sikapay\Config\AppConfig; 

// 1. Load configuration (which loads .env)
$config = AppConfig::load();

// 2. Instantiate the application (this connects the DB)
$app = new Application($config);

// --- Define Initial Routes (FastRoute Syntax) ---
$routes = [
    // Default welcome route
    ['GET', '/', function() use ($config) {
        // ... (Route content remains the same)
        $dbStatus = $config['db']['dsn'] ? 'Established' : 'Failed';
        echo "<h1>" . $config['app']['name'] . " Initial Boot</h1>";
        echo "<p>Configuration, Composer, and Database connection status: **{$dbStatus}**</p>";
        echo "<p>Next: Run CLI commands and implement Controller/Auth.</p>";
    }],
    ['GET', '/test-404', function() {
        // Test to confirm ErrorResponder works
        Jeffrey\Sikapay\Core\ErrorResponder::respond(404);
    }],
];

$app->setRoutes($routes);

return $app;