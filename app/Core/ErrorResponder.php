<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

use Jeffrey\Sikapay\Core\Log; 
use Jeffrey\Sikapay\Core\Auth; 
use Jeffrey\Sikapay\Helpers\ViewHelper; // Added

class ErrorResponder
{
    /**
     * Renders the appropriate error view file based on the status code and chosen layout.
     */
    private static function renderErrorView(string $file, array $data, string $layoutPath): void
    {
        // Define the absolute path to the views directory for error content
        $errorViewPath = __DIR__ . "/../../resources/views/error/{$file}"; 
        
        // Security helpers to be available in all views
        $securityHelpers = [
            'h'         => [ViewHelper::class, 'h'],
            'id'        => [ViewHelper::class, 'id'],
            'nl2br_h'   => [ViewHelper::class, 'nl2br_h'],
        ];

        // Merge security helpers with controller-specific data
        $finalData = array_merge($securityHelpers, $data);

        // Ensure the data variables are available to the included view file
        extract($finalData);

        // Define a variable that the layout expects for the content file
        $__content_file = $errorViewPath;

        // Load the chosen layout, which in turn will include our error view file
        if (file_exists($layoutPath)) {
            require_once $layoutPath;
        } else {
            // CRITICAL FALLBACK if the chosen layout is missing
            echo "<!DOCTYPE html><html><head><title>{$finalData['code']} Error</title></head><body>";
            echo "<h1>{$finalData['code']} Error: {$finalData['title']}</h1>";
            echo "<p>{$finalData['message']}</p>";
            echo "<p>NOTE: The chosen layout file '{$layoutPath}' is missing.</p>";
            echo "<p>Also, custom error view file '{$errorViewPath}' might be missing.</p></body></html>";
        }
    }


    /**
     * Sets the HTTP response code and displays the relevant error page.
     * @param int $code The HTTP status code (e.g., 404, 500).
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

        // Determine login status (still needed for conditional buttons in error templates)
        $authInstance = Auth::getInstance();
        $isLoggedIn = $authInstance->check();

        // Always use minimal.php for error pages to ensure stability and reduce dependencies.
        $layoutPath = __DIR__ . "/../../resources/layout/minimal.php";

        // Load the custom error view
        self::renderErrorView($viewFile, [
            'code' => $code,
            'title' => $title,
            'message' => $displayMessage,
            'isLoggedIn' => $isLoggedIn, // Pass login status to the view for conditional navigation
        ], $layoutPath);

        // Terminate execution
        exit();
    }
}