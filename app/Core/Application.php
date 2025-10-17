<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

use Jeffrey\Sikapay\Core\Database;
use Jeffrey\Sikapay\Core\Router;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use \PDO;

class Application
{
    public \PDO $database;
    protected Router $router;

    public function __construct(array $config)
    {
        try {
            // 1. Initialize Database (Database::connect handles its own critical error logging)
            $this->database = Database::connect($config['db']);
            
            // 2. Initialize Router
            $this->router = new Router();
            
            // 3. Initialize Session (Ensure it starts before Auth/Models are used)
            SessionManager::start();
            
        } catch (\Throwable $e) {
            // Catch critical initialization errors not caught by lower layers
            Log::critical("Application Initialization Failed: " . $e->getMessage(), [
                'file' => $e->getFile(), 
                'line' => $e->getLine()
            ]);
            // Halt the entire application flow gracefully
            ErrorResponder::respond(500, "A critical system error prevents the application from starting.");
        }
    }

    public function setRoutes(array $routes): void
    {
        // This is primarily configuration, but adding a basic check is safer
        if (empty($routes)) {
            Log::warning("No routes defined for the application.");
        }
        $this->router->setRoutes($routes);
    }

    public function run(): void
    {
        try {
            // Get the requested URI (using the path from the .htaccess rewrite)
            $uri = $_GET['path'] ?? '/';
            $uri = '/' . trim($uri, '/');
            
            $httpMethod = $_SERVER['REQUEST_METHOD'];

            // Dispatch the request using the dedicated Router
            // The Router handles its own dispatch exceptions (404, 405, 500)
            $this->router->dispatch($httpMethod, $uri);
            
        } catch (\Throwable $e) {
            // Final catch for any exceptions that escaped the Router
            Log::critical("FATAL UNHANDLED EXCEPTION (Escaped Router): " . $e->getMessage(), [
                'file' => $e->getFile(), 
                'line' => $e->getLine(),
                'uri' => $uri ?? 'unknown',
                'method' => $httpMethod ?? 'unknown'
            ]);
            // Final fallback response to hide sensitive details
            ErrorResponder::respond(500, "A catastrophic error occurred.");
        }
    }
}