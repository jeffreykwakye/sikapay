<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Middleware;

use Jeffrey\Sikapay\Core\Auth; 
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use \Throwable; 

class PermissionMiddleware
{
    /**
     * Executes the authorization check by checking login status and required permission.
     * * @param string $requiredPermission The key name (e.g., 'tenant:create').
     * @return bool True if the user is authorized.
     */
    public function handle(string $requiredPermission): bool
    {
        try {
            $auth = Auth::getInstance(); 
            $userId = $auth->userId() ?? 0;
            $tenantId = $auth->tenantId() ?? 0;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'N/A';

            // 1. Check Login Status (Fallback for AuthMiddleware failure)
            if (!$auth->check()) {
                $_SESSION['flash_error'] = "You must be logged in to access this page.";
                header('Location: /login');
                exit; 
                
                return false; 
            }

            // 2. Check Permission using the central Auth::hasPermission() gate
            if (!$auth->hasPermission($requiredPermission)) {
                
                Log::warning("Unauthorized access denied to User {$userId}.", [
                    'tenant_id' => $tenantId,
                    'permission' => $requiredPermission,
                    'ip_address' => $ipAddress
                ]);

                // Halt the request using the definitive ErrorResponder 403
                $message = "Access denied. You do not have the required permission: {$requiredPermission}.";
                ErrorResponder::respond(403, $message); 
                
                return false; 
            }

            return true; // Authorization successful

        } catch (Throwable $e) {
            
            Log::critical("Middleware Execution Failed (PermissionMiddleware).", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'permission' => $requiredPermission,
                'user_id' => $userId ?? 'unknown' 
            ]);

            // If the middleware fails due to a system error, deny access with a 500
            ErrorResponder::respond(500, "A critical system error occurred during authorization.");
            
            return false; 
        }
    }
}