<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

class ErrorResponder
{
    public static function respond(int $code, string $message = ''): void
    {
        http_response_code($code);
        
        switch ($code) {
            case 404:
                $title = "404 Not Found";
                $defaultMessage = "The page you requested could not be found.";
                break;
            case 405:
                $title = "405 Method Not Allowed";
                $defaultMessage = "The request method is not supported for this resource.";
                break;
            case 500:
                $title = "500 Internal Server Error";
                $defaultMessage = "An unexpected error occurred.";
                break;
            default:
                $title = (string)$code . " Error";
                $defaultMessage = "An unexpected error occurred.";
        }

        $displayMessage = !empty($message) ? $message : $defaultMessage;

        // In a real application, we would load a View here (e.g., Views/errors/404.php)
        echo "<h1>{$title}</h1>";
        echo "<p>{$displayMessage}</p>";

        exit();
    }
}