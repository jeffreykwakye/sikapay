<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;


use Jeffrey\Sikapay\Core\Log; 

class ErrorResponder
{
    /**
     * Renders the appropriate error view file based on the status code.
     */
    private static function renderErrorView(string $file, array $data): void
    {
        // Define the absolute path to the views directory
        // Assumes Core is in src/Core and views is in src/views/errors
        $path = __DIR__ . "/../../views/errors/{$file}"; 
        
        // Make the data variables available to the included view file
        extract($data);

        // Load the view file or fall back to a minimal display
        if (file_exists($path)) {
            require_once $path;
        } else {
            // CRITICAL FALLBACK: If the designed view file is missing
            echo "<!DOCTYPE html><html><head><title>{$data['code']} Error</title></head><body>";
            echo "<h1>{$data['code']} Error: {$data['title']}</h1>";
            echo "<p>{$data['message']}</p>";
            echo "<p>NOTE: Custom error view file '{$file}' is missing.</p></body></html>";
        }
    }


    /**
     * Sets the HTTP response code and displays the relevant error page.
     * * @param int $code The HTTP status code (e.g., 404, 500).
     * @param string $message Optional custom message to override the default.
     */
    public static function respond(int $code, string $message = ''): void
    {
        http_response_code($code);
        
        // Define standard messages and the target view file
        $viewFile = "error.php"; // Set generic as default
        
        switch ($code) {
            case 404:
                $title = "404 Page Not Found";
                $defaultMessage = "The page you requested could not be located on our servers. Please check the URL and try again.";
                $viewFile = "404.php";
                break;
            case 403: 
                $title = "403 Access Denied";
                // Security-conscious message: friendly, but firm.
                $defaultMessage = "You do not have the necessary authorization to view this page or perform this action. If you believe this is an error, please contact your administrator.";
                $viewFile = "403.php";
                break;
            case 405:
                $title = "405 Request Not Allowed";
                // Technical but necessary for Method Not Allowed
                $defaultMessage = "The method used for this request is not supported for this address.";
                $viewFile = "405.php";
                break;
            case 500:
                $title = "500 Internal Server Error";
                // User-friendly, generic message. Assumes logging has been triggered.
                $defaultMessage = "A temporary server issue has occurred. We have been notified and are working to resolve the problem. Please try again shortly.";
                $viewFile = "500.php";
                break;
            default:
                $title = (string)$code . " System Error";
                // Catch-all for other codes (e.g., 400, 401, 429)
                $defaultMessage = "An unexpected error occurred and our team has been alerted.";
                // Uses the generic 'error.php' template
        }

        $displayMessage = !empty($message) ? $message : $defaultMessage;

        // Load the custom error view
        self::renderErrorView($viewFile, [
            'code' => $code,
            'title' => $title,
            'message' => $displayMessage
        ]);

        // Terminate execution
        exit();
    }
}