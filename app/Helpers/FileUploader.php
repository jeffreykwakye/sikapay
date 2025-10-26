<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use Jeffrey\Sikapay\Core\Log;

class FileUploader
{
    /**
     * Uploads a file, performs validation, and returns the public path.
     *
     * @param array $fileData The data from the $_FILES array.
     * @param string $destinationDir The relative directory path starting from the public/ directory.
     * @param array $allowedExtensions List of allowed extensions.
     * @param int $maxSize Max file size in bytes.
     * @return string The public URL path to the uploaded file.
     * @throws \Exception If the upload fails validation or processing.
     */
    public static function upload(array $fileData, string $destinationDir, array $allowedExtensions, int $maxSize): string
    {
        // 1. Basic validation (Error code and Size)
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = match ($fileData['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the maximum file size limit.",
                UPLOAD_ERR_PARTIAL => "The file was only partially uploaded.",
                UPLOAD_ERR_NO_FILE => "No file was uploaded.",
                default => "An unknown upload error occurred (Code: {$fileData['error']}).",
            };
            throw new \Exception($errorMsg);
        }
        if ($fileData['size'] > $maxSize) {
            throw new \Exception("File size exceeds the limit of " . round($maxSize / 1024 / 1024, 2) . "MB.");
        }
        
        // 2. Extension check (Calculate once)
        $extension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception("Invalid file type: .{$extension} not allowed. Only " . implode(', ', $allowedExtensions) . " are permitted.");
        }

        // 3. Define Paths and Unique Name
        
        // Construct the ABSOLUTE path to the root of the project
        // Helpers is 2 levels deep from project root: sikapay/Helpers -> sikapay/
        $projectRoot = dirname(__DIR__, 2); 
        
        // Define the target directory on the server's file system
        // Path: /sikapay/public/{assets/images/tenant_logos/1}
        $serverUploadDir = $projectRoot . '/public/' . $destinationDir . '/';
        
        // Define the public URL path
        // Path: /{assets/images/tenant_logos/1}
        $publicBaseUrl = '/' . $destinationDir . '/';
        
        // Generate a cryptographically secure, unique filename, reusing the calculated $extension
        $uniqueFilename = bin2hex(random_bytes(16)) . '.' . $extension;

        // Final paths
        $serverFinalPath = $serverUploadDir . $uniqueFilename;
        $publicFinalPath = $publicBaseUrl . $uniqueFilename;

        // 4. Create Directory if it doesn't exist
        if (!is_dir($serverUploadDir)) {
            if (!mkdir($serverUploadDir, 0755, true)) {
                Log::critical("Failed to create upload directory: " . $serverUploadDir);
                throw new \Exception("Server error: Failed to create upload directory. Check permissions.");
            }
        }

        // 5. Securely Move the Uploaded File
        if (!move_uploaded_file($fileData['tmp_name'], $serverFinalPath)) {
            Log::critical("Failed to move uploaded file to {$serverFinalPath}.");
            throw new \Exception("Server error: Failed to securely store the uploaded file. Check file permissions.");
        }
        
        Log::info("File uploaded successfully to: {$serverFinalPath}. Public path: {$publicFinalPath}");

        // 6. Return the public URL path
        return $publicFinalPath;
    }
}