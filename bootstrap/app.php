<?php
declare(strict_types=1);

use Jeffrey\Sikapay\Core\Router;
use Jeffrey\Sikapay\Config\AppConfig;
use Jeffrey\Sikapay\Core\Database;

// 1. Define a simple Application class (minimal runner)
$app = new class {
    private Router $router;
    private array $config; // To hold the loaded configuration
    
    public function __construct()
    {
        // 1. Load Configuration
        $this->config = AppConfig::load(); // Call the config loader
        
        // CRITICAL STEP: Initialize the Database connection
        try {
            Database::connect($this->config['db']);
        } catch (\Exception $e) {
            // Log the error and stop execution gracefully if DB is essential
            die("Application failed to start due to DB error: " . $e->getMessage());
        }

        // 3. Instantiate the Router
        $this->router = new Router();
        
        // 4. Load the routes and set them on the Router instance
        $routes = require __DIR__ . '/../app/routes.php';
        $this->router->setRoutes($routes);
    }

    // 5. Run the application (Dispatch the request)
    public function run(): void
    {
        // Get the URI and HTTP Method
        $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');
        $httpMethod = $_SERVER['REQUEST_METHOD'];

        // Dispatch the request
        $this->router->dispatch($httpMethod, "/{$uri}"); 
    }
};

return $app;