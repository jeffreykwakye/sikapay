<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

class Router
{
    private Dispatcher $dispatcher;
    private array $routes = [];

    public function setRoutes(array $routes): void
    {
        $this->routes = $routes;
    }

    private function initDispatcher(): void
    {
        $this->dispatcher = simpleDispatcher(function (RouteCollector $r) {
            foreach ($this->routes as $route) {
                // $route = [method, path, handler]
                $r->addRoute($route[0], $route[1], $route[2]);
            }
        });
    }

    public function dispatch(string $httpMethod, string $uri): void
    {
        $this->initDispatcher();

        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                ErrorResponder::respond(404);
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                ErrorResponder::respond(405, "Allowed methods: " . implode(', ', $allowedMethods));
                break;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                $this->executeHandler($handler, $vars);
                break;
        }
    }

    /**
     * Executes the route handler, which may include middleware processing.
     */
    private function executeHandler($handler, array $vars): void
    {
        // --- STEP 1: Check for Middleware Structure ---
        if (is_array($handler) && isset($handler['handler'])) {
            
            $authMiddlewareInfo = $handler['auth'] ?? null;      // NEW: Basic Auth check
            $permissionMiddlewareInfo = $handler['permission'] ?? null; // Existing Permission check
            $controllerHandler = $handler['handler'];
            
            // --- A. Run Auth Middleware (Basic Login Check) ---
            if ($authMiddlewareInfo === 'AuthMiddleware') {
                $authMiddlewareName = "Jeffrey\\Sikapay\\Middleware\\AuthMiddleware";
                if (class_exists($authMiddlewareName)) {
                    $middleware = new $authMiddlewareName();
                    // The handle method will redirect and exit if not logged in.
                    $middleware->handle(); 
                } else {
                    ErrorResponder::respond(500, "AuthMiddleware class not found.");
                    return;
                }
            }

            // --- B. Run Permission Middleware (RBAC Check) ---
            if ($permissionMiddlewareInfo && is_array($permissionMiddlewareInfo)) {
                $middlewareName = "Jeffrey\\Sikapay\\Middleware\\" . $permissionMiddlewareInfo[0]; // e.g., PermissionMiddleware
                $permissionKey = $permissionMiddlewareInfo[1]; // e.g., 'tenant:create'
                
                if (class_exists($middlewareName)) {
                    $middleware = new $middlewareName();
                    // CRITICAL: Call the handle method. If the handle method fails, 
                    // it is responsible for redirecting and exiting.
                    $middleware->handle($permissionKey); 
                    
                } else {
                    ErrorResponder::respond(500, "Permission Middleware class not found: {$middlewareName}");
                    return;
                }
            }
            
            // Replace the current $handler with the controller handler for STEP 2
            $handler = $controllerHandler;
        }
        
        // --- STEP 2: Execute the Controller Handler (Applies to both old and new structures) ---
        if (is_callable($handler)) {
            call_user_func_array($handler, $vars);
            
        } elseif (is_array($handler)) {
            // Assumes Controller Handler: ['ControllerName', 'method']
            $controllerName = "Jeffrey\\Sikapay\\Controllers\\" . $handler[0];
            $methodName = $handler[1];
            
            if (class_exists($controllerName)) {
                // Instantiating the controller will now call Controller::__construct(), 
                // which correctly uses Auth::getInstance().
                $controller = new $controllerName(); 
                call_user_func_array([$controller, $methodName], $vars);
                
            } else {
                ErrorResponder::respond(500, "Controller class not found: {$controllerName}");
            }
        } else {
            ErrorResponder::respond(500, "Invalid route handler specified.");
        }
    }
}