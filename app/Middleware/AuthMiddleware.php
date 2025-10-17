<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Middleware;

use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder; 
use \Throwable;

class AuthMiddleware
{
    /**
     * Checks if the user is authenticated.
     * @param mixed $dummyParam A parameter to match the router's execution signature.
     * @return void
     */
    public function handle($dummyParam = null): void
    {
        try {
            $auth = Auth::getInstance();

            if (!$auth->check()) {
                // 1. Not authenticated: Redirect to login and halt execution.
                
                // Store the requested URI so the user can be redirected back after login
                $_SESSION['redirect_back_to'] = $_SERVER['REQUEST_URI'];
                
                // Redirect unauthenticated users to the login page and STOP execution
                header("Location: /login");
                exit(); 
            }
            // If authenticated, execution continues (returns void implicitly).

        } catch (Throwable $e) {
            // Catch any unexpected system error during authentication check (e.g., DB lookup failure)
            
            // Log the critical failure
            Log::critical("Middleware Execution Failed (AuthMiddleware). Authentication status unknown.", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
            ]);

            // Enforce "fail closed": If we can't confirm authentication, we deny access with a 500 error.
            ErrorResponder::respond(500, "A critical system error occurred during authentication.");
            
            // Explicitly exit after ErrorResponder in case it fails to halt execution itself
            exit(1); 
        }
    }
}