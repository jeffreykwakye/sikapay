<?php
declare(strict_types=1);
namespace Jeffrey\Sikapay\Core;

use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;

class SessionManager
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            
            // Set session security configuration
            if (!ini_set('session.use_only_cookies', '1')) {
                Log::critical("Failed to set session.use_only_cookies to 1.");
            }
            if (!ini_set('session.cookie_httponly', '1')) {
                Log::critical("Failed to set session.cookie_httponly to 1.");
            }
            // NOTE: Keep 'session.cookie_secure' at 0 for development, but must be 1 in production.
            if (!ini_set('session.cookie_secure', '0')) {
                Log::critical("Failed to set session.cookie_secure to 0.");
            }

            // Attempt to start the session
            if (!session_start()) {
                // Critical failure to start session
                Log::critical("CRITICAL: PHP session_start() failed unexpectedly.");
                // Halt the application since core functionality is broken
                ErrorResponder::respond(500, "A critical system error prevents the user session from starting.");
            }
        }
    }
    

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    
    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    
    public static function remove(string $key): void
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    
    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}