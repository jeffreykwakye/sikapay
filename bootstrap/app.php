<?php
declare(strict_types=1);

use Jeffrey\Sikapay\Core\Router;
use Jeffrey\Sikapay\Config\AppConfig;
use Jeffrey\Sikapay\Core\Database;

// Ensure CORE utilities are available for critical error handling
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;

// 1. Define a simple Application class (minimal runner)
$app = new class {
    private Router $router;
    private array $config; // To hold the loaded configuration
    
    
    public function __construct()
    {
        // 1. Load Configuration
        try {
            $this->config = AppConfig::load(); // Call the config loader
        } catch (\Throwable $e) {
            // Log this fatal config failure manually, as the system isn't fully up
            http_response_code(500);
            error_log("FATAL: Configuration loading failed. " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            die("<h1>500 System Error</h1><p>The application failed to load configuration.</p>");
        }
        
        // CRITICAL STEP: Initialize the Database connection
        // Database::connect now handles its own logging and halting (ErrorResponder::respond(500))
        try {
            Database::connect($this->config['db']);
        } catch (\Throwable $e) {
            // If Database::connect somehow failed to halt gracefully, 
            // log the error and stop execution. This should be extremely rare.
            Log::critical("Application failed to start due to unhandled DB error: " . $e->getMessage());
            ErrorResponder::respond(500, "Application failed to start due to a critical database issue.");
        }

        // 3. Instantiate the Router
        $this->router = new Router();
        
        // 4. Load the routes and set them on the Router instance
        try {
            $routes = require __DIR__ . '/../app/routes.php';
            $this->router->setRoutes($routes);
        } catch (\Throwable $e) {
            // Fatal error if routes file is missing or contains an error
            Log::critical("CRITICAL: Route file loading failed. " . $e->getMessage(), [
                'file' => $e->getFile(), 
                'line' => $e->getLine()
            ]);
            ErrorResponder::respond(500, "Application failed to load necessary routes.");
        }
    }

    
    // 5. Run the application (Dispatch the request)
    public function run(): void
    {
        try {
            // Get the URI and HTTP Method
            $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');
            $httpMethod = $_SERVER['REQUEST_METHOD'];

            // Dispatch the request. The Core\Router handles its own dispatch exceptions.
            $this->router->dispatch($httpMethod, "/{$uri}"); 
            
        } catch (\Throwable $e) {
            // Log any exception that escaped the entire framework
            Log::critical("FATAL UNHANDLED EXCEPTION in bootstrap run(): " . $e->getMessage(), [
                'file' => $e->getFile(), 
                'line' => $e->getLine()
            ]);
            ErrorResponder::respond(500, "A catastrophic error occurred.");
        }
    }
};

return $app;