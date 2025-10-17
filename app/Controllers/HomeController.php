<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use \Throwable; // Catch all runtime exceptions

class HomeController extends Controller
{
    /**
     * Handles the root route '/' by redirecting based on authentication status.
     */
    public function index(): void
    {
        try {
            // Check authentication status using the inherited Auth instance
            if ($this->auth->check()) {
                // If authenticated, go to the dashboard
                $this->redirect('/dashboard');
            } else {
                // If not authenticated, redirect to the login page
                $this->redirect('/login');
            }
            // Note: $this->redirect() calls exit() internally, so no explicit return is needed.

        } catch (Throwable $e) {
            // Catch any critical error during the auth check or redirection (e.g., Auth component failure)
            
            // Log the critical failure
            Log::critical("Home Controller Auth Check Failed: Cannot determine user status.", [
                'error' => $e->getMessage(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
            ]);

            // Enforce fail-safe: Display a controlled 500 error page. 
            // We cannot safely redirect to /login or /dashboard, so we must halt.
            ErrorResponder::respond(500, "A critical system error occurred during initial access check.");
        }
    }
}