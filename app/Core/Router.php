<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;


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
                // Log all 404s for monitoring
                Log::info("Route Not Found (404): {$httpMethod} {$uri}");
                ErrorResponder::respond(404);
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                $allowedMethodsStr = implode(', ', $allowedMethods);
                // Log all 405s for potential attacker probing
                Log::info("Method Not Allowed (405): {$httpMethod} {$uri}. Allowed: {$allowedMethodsStr}");
                ErrorResponder::respond(405, "Allowed methods: " . $allowedMethodsStr);
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
        $originalHandler = $handler; // Keep a reference to log properly on failure

        // --- STEP 1: Check for Middleware Structure ---
        if (is_array($handler) && isset($handler['handler'])) {
            
            $authMiddlewareInfo = $handler['auth'] ?? null;
            $permissionMiddlewareInfo = $handler['permission'] ?? null; 
            $controllerHandler = $handler['handler'];
            
            // --- A. Run Auth Middleware (Basic Login Check) ---
            if ($authMiddlewareInfo === 'AuthMiddleware') {
                $authMiddlewareName = "Jeffrey\\Sikapay\\Middleware\\AuthMiddleware";
                if (class_exists($authMiddlewareName)) {
                    $middleware = new $authMiddlewareName();
                    $middleware->handle(); 
                } else {
                    // Critical configuration error
                    Log::critical("AuthMiddleware class not found: {$authMiddlewareName}. Route: " . json_encode($originalHandler));
                    ErrorResponder::respond(500, "AuthMiddleware class not found.");
                    return;
                }
            }

            // --- B. Run Permission Middleware (RBAC Check) ---
            if ($permissionMiddlewareInfo && is_array($permissionMiddlewareInfo)) {
                $middlewareName = "Jeffrey\\Sikapay\\Middleware\\" . $permissionMiddlewareInfo[0]; 
                $permissionKey = $permissionMiddlewareInfo[1]; 
                
                if (class_exists($middlewareName)) {
                    $middleware = new $middlewareName();
                    // CRITICAL: The middleware handles 403 response/redirect and exits itself.
                    $middleware->handle($permissionKey); 
                    
                } else {
                    // Critical configuration error
                    Log::critical("Permission Middleware class not found: {$middlewareName}. Route: " . json_encode($originalHandler));
                    ErrorResponder::respond(500, "Permission Middleware class not found: {$middlewareName}");
                    return;
                }
            }
            
            // Replace the current $handler with the controller handler for STEP 2
            $handler = $controllerHandler;
        }
        
        // --- STEP 2: Execute the Controller Handler (Applies to both old and new structures) ---
        if (is_callable($handler)) {
            // Function/Closure Handler
            call_user_func_array($handler, $vars);
            
        } elseif (is_array($handler)) {
            // Assumes Controller Handler: ['ControllerName', 'method']
            $controllerName = "Jeffrey\\Sikapay\\Controllers\\" . $handler[0];
            $methodName = $handler[1];
            
            if (class_exists($controllerName)) {
                
                try {
                    // Instantiating the controller
                    $controller = new $controllerName(); 
                    call_user_func_array([$controller, $methodName], $vars);
                } catch (\Exception $e) {
                    // Catch any unhandled exception from the controller/action
                    Log::critical("Unhandled Exception in Controller {$controllerName}::{$methodName}. Error: " . $e->getMessage(), [
                        'exception' => $e->getFile() . ':' . $e->getLine(),
                        'vars' => $vars
                    ]);
                    // Display a generic error, preventing internal details from being shown
                    ErrorResponder::respond(500, "An unexpected server error occurred during page processing.");
                }
                
            } else {
                // Critical configuration error
                Log::critical("Controller class not found: {$controllerName}. Route: " . json_encode($originalHandler));
                ErrorResponder::respond(500, "Controller class not found: {$controllerName}");
            }
        } else {
            // Critical configuration error
            Log::critical("Invalid route handler specified. Route: " . json_encode($originalHandler));
            ErrorResponder::respond(500, "Invalid route handler specified.");
        }
    }
}