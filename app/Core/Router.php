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

    private function executeHandler($handler, array $vars): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $vars);
        } elseif (is_array($handler)) {
            // Assumes Controller Handler: ['ControllerName', 'method']
            $controllerName = "Jeffrey\\Sikapay\\Controllers\\" . $handler[0];
            $methodName = $handler[1];
            
            if (class_exists($controllerName)) {
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