<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Middleware;

use Jeffrey\Sikapay\Core\Auth;

class AuthMiddleware
{
    /**
     * Checks if the user is authenticated.
     * * @param mixed $dummyParam A parameter to match the router's execution signature.
     */
    public function handle($dummyParam = null): void
    {
        $auth = Auth::getInstance();

        if (!$auth->check()) {
            // Store the requested URI so the user can be redirected back after login
            $_SESSION['redirect_back_to'] = $_SERVER['REQUEST_URI'];
            
            // Redirect unauthenticated users to the login page and STOP execution
            header("Location: /login");
            exit(); 
        }
    }
}