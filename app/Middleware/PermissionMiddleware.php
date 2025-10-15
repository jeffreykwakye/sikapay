<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Middleware;

use Jeffrey\Sikapay\Core\Auth; 
use Jeffrey\Sikapay\Core\Log;

class PermissionMiddleware
{
    /**
     * Executes the authorization check by checking login status and required permission.
     * * @param string $requiredPermission The key name (e.g., 'tenant:create').
     * @return bool True if the user is authorized.
     */
    public function handle(string $requiredPermission): bool
    {
        // Safely retrieve the Auth instance using the Singleton pattern
        $auth = Auth::getInstance(); 

        // 1. Check Login Status first
        if (!$auth->check()) {
            $_SESSION['flash_error'] = "You must be logged in to access this page.";
            header('Location: /login');
            exit;
        }

        // 2. Check Permission using the central Auth::can() gate
        if (!$auth->can($requiredPermission)) {
            
            // Log the unauthorized attempt (good practice)
            Log::error("Unauthorized access attempt.", [
                'user_id' => $auth->userId(),
                'tenant_id' => $auth->tenantId(),
                'permission' => $requiredPermission,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A' 
            ]);

            $_SESSION['flash_error'] = "Access denied. You do not have permission for this action ({$requiredPermission}).";
            
            // Redirect to a safe page
            header('Location: /dashboard'); 
            exit;
        }

        return true; // Authorization successful
    }
}