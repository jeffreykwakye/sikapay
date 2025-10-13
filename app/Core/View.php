<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

class View
{
    /**
     * Renders a view file, extracting the provided data for use within the view.
     * * @param string $viewPath The path to the view file (e.g., 'auth/login').
     * @param array $data Data to be passed to the view and extracted.
     */
    public static function render(string $viewPath, array $data = []): void
    {
        // Define the base directory for all views
        $baseDir = __DIR__ . '/../../resources/views/';
        
        // Construct the full file path. Assumes files end with .php
        $filePath = $baseDir . $viewPath . '.php';

        if (!file_exists($filePath)) {
            // In a real application, you'd log this and show a 404 page.
            throw new \Exception("View file not found: " . $filePath);
        }

        // The extract() function turns the associative array keys into local variables.
        // e.g., $data = ['error' => 'message'] becomes $error = 'message' in the view scope.
        extract($data); 

        // Start output buffering to capture the contents of the view file
        ob_start();
        
        // Include the view file. The extracted variables are now available here.
        require $filePath;
        
        // Output the captured content and end buffering
        ob_end_flush();
    }
}