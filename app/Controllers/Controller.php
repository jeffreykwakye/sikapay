<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Core\Auth; 
use Jeffrey\Sikapay\Core\View;

abstract class Controller
{
    protected Auth $auth;

    public function __construct()
    {
        // Initialize Auth service for use in all controllers
        $this->auth = new Auth(); 
    }

    /**
     * Loads a view file from the resources/views directory.
     * * @param string $viewPath e.g., 'auth/login'
     * @param array $data Data to pass to the view
     */
    protected function view(string $viewPath, array $data = []): void
    {
        View::render($viewPath, $data);
    }

    /**
     * Redirects the user to a specified URI.
     * * @param string $uri The URI to redirect to.
     */
    protected function redirect(string $uri): void
    {
        header("Location: {$uri}");
        exit();
    }


    /**
     * Sends HTTP headers to prevent the browser from caching the page.
     * This is essential for preventing logged-out users from seeing cached pages
     * when using the browser's back button.
     */
    protected function preventCache(): void
    {
        // Standard Cache Prevention
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); 
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        
        // Back/Forward Cache (BFcache) Prevention
        // This instructs the browser not to use bfcache for this page.
        header('Permissions-Policy: interest-cohort=()');
    }

}