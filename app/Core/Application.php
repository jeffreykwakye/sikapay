<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

// Removed all FastRoute dependencies, now handled by Router.php

class Application
{
    public \PDO $database;
    protected Router $router;

    public function __construct(array $config)
    {
        $this->database = Database::connect($config['db']);
        $this->router = new Router(); // Initialize the new router
    }

    public function setRoutes(array $routes): void
    {
        $this->router->setRoutes($routes);
    }

    public function run(): void
    {
        // Get the requested URI (using the path from the .htaccess rewrite)
        $uri = $_GET['path'] ?? '/';
        $uri = '/' . trim($uri, '/');
        
        $httpMethod = $_SERVER['REQUEST_METHOD'];

        // Dispatch the request using the dedicated Router
        $this->router->dispatch($httpMethod, $uri);
    }
}