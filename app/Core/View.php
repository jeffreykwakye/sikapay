<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;


class View
{
    /**
     * Renders a view file, extracting the provided data for use within the view.
     * @param string $viewPath The path to the view file (e.g., 'auth/login').
     * @param array $data Data to be passed to the view and extracted.
     */
    public static function render(string $viewPath, array $data = []): void
    {
        // Define the base directory for all views
        $baseDir = __DIR__ . '/../../resources/views/';
        
        // Construct the full file path. Assumes files end with .php
        $filePath = $baseDir . $viewPath . '.php';

        if (!file_exists($filePath)) {
            // Log a critical error that the view file is missing
            Log::critical("View rendering failed. View file not found: " . $filePath);

            // Halt execution and show a 500 error page, as a 
            // view file missing is a configuration error the user cannot fix.
            ErrorResponder::respond(500, "The requested view is unavailable due to a configuration error.");
        }

        // The extract() function turns the associative array keys into local variables.
        extract($data); 

        // Start output buffering to capture the contents of the view file
        ob_start();
        
        try {
            // Include the view file. The extracted variables are now available here.
            require $filePath;
        } catch (\Throwable $e) {
            // Catch any error/exception that occurs *inside* the included view file
            ob_end_clean(); // Stop buffering and discard content to prevent partial output
            
            Log::critical("Unhandled rendering error inside view file: {$viewPath}. Error: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Halt the application and show a generic server error
            ErrorResponder::respond(500, "An unexpected error occurred while rendering the page content.");
        }
        
        // Output the captured content and end buffering
        ob_end_flush();
    }
}