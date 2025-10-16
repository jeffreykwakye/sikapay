<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Middleware;

use Jeffrey\Sikapay\Core\Auth; 
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;

class PermissionMiddleware
{
    /**
     * Executes the authorization check by checking login status and required permission.
     * * @param string $requiredPermission The key name (e.g., 'tenant:create').
     * @return bool True if the user is authorized.
     */
    public function handle(string $requiredPermission): bool
    {
        $auth = Auth::getInstance(); 

        // 1. Check Login Status first (Fallback check, as AuthMiddleware should run first)
        if (!$auth->check()) {
            // Note: AuthMiddleware should handle this, but as a fallback, 
            // a direct redirect to /login is safer than using ErrorResponder here.
            $_SESSION['flash_error'] = "You must be logged in to access this page.";
            header('Location: /login');
            exit;
        }

        // 2. Check Permission using the central Auth::can() gate
        if (!$auth->can($requiredPermission)) {
            
            // Log the unauthorized attempt
            Log::error("Unauthorized access attempt.", [
                'user_id' => $auth->userId(),
                'tenant_id' => $auth->tenantId(),
                'permission' => $requiredPermission,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A' 
            ]);

            // !!! CRITICAL CHANGE: Halt the request using the ErrorResponder !!!
            $message = "Access denied. Required permission: {$requiredPermission}.";
            ErrorResponder::respond(403, $message); 
            // Note: The ErrorResponder will call exit()

            // The code below is unreachable due to ErrorResponder::respond(403)
            // $_SESSION['flash_error'] = "Access denied. ...";
            // header('Location: /dashboard'); 
            // exit;
        }

        return true; // Authorization successful
    }
}