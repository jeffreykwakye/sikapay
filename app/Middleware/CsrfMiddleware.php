<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Middleware;

use Jeffrey\Sikapay\Security\CsrfToken;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;

class CsrfMiddleware
{
    /**
     * Executes the CSRF validation check before processing state-modifying requests.
     */
    public static function enforce(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // 1. Skip check for safe methods (GET, HEAD, OPTIONS)
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            CsrfToken::init(); 
            return;
        }

        // 2. Retrieve the token from the request body (typically POST data)
        $requestToken = $_POST['csrf_token'] ?? '';
        
        // 3. Validate the token
        if (!CsrfToken::validate($requestToken)) {
            
            // Log the unauthorized attempt
            $userId = $_SESSION['user_id'] ?? 'N/A';
            Log::alert("CSRF validation failed for user ID {$userId}. Request blocked.", [
                'method' => $method,
                'path' => $_SERVER['REQUEST_URI'] ?? 'N/A',
                'ip' => $_SERVER['REMOTE_ADDR'],
                'referrer' => $_SERVER['HTTP_REFERER'] ?? 'N/A'
            ]);
            
            // Call the public method to destroy the token safely
            CsrfToken::destroyToken();
            
            // Halt execution and return a generic 403 error
            ErrorResponder::respond(403, "The security token is missing or invalid. Your action was blocked.");
        }
        
        // If validation passes, we rotate the token by calling init(). 
        // Since the old one is still present, init() currently does nothing. 
        // For true token rotation (best practice), a dedicated rotate() method 
        // in CsrfToken.php should be used here instead.
        CsrfToken::init(); 
    }
}