<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Core\Auth; 
use Jeffrey\Sikapay\Core\View;
use Jeffrey\Sikapay\Services\NotificationService;
use Jeffrey\Sikapay\Models\TenantModel;

abstract class Controller
{
    protected Auth $auth;
    protected int $userId; // For user context
    protected int $tenantId; // For tenancy context
    protected NotificationService $notificationService; // For core application service;
    protected TenantModel $tenantModel; // For fetching tenant name
    protected ?string $tenantName = null;

    
    public function __construct()
    {
        // Initialize Auth service for use in all controllers
        $this->auth = new Auth(); 
         // 2. Initialize user context (using the instance Auth::userId)
        $this->userId = $this->auth->userId();
        $this->tenantId = $this->auth->tenantId();
        
        // 3. Initialize Notification Service
        $this->notificationService = new NotificationService();
        // 4. Initialize Tenant Model and fetch name
        $this->tenantModel = new TenantModel();
        if ($this->tenantId > 0) {
            $this->tenantName = $this->tenantModel->getNameById($this->tenantId);
        }
    }


    /**
     * Loads the view using the master layout and passes common system data.
     */
    protected function view(string $viewPath, array $data = []): void
    {
        // 1. Get common data for the layout and views
        $commonData = [
            'auth' => $this->auth, // Pass the Auth instance for check/isSuperAdmin calls in views
            'userId' => $this->userId,
            'tenantId' => $this->tenantId,
            'tenantName' => $this->tenantName ?? 'System/Public',
            // Calculate unread count
            'unreadNotificationCount' => $this->userId > 0 ? $this->notificationService->getUnreadCount($this->userId) : 0,
        ];
        
        // 2. Merge page-specific data with common data
        $finalData = array_merge($commonData, $data);
        
        // 3. Define the path to the specific content file
        $contentFile = $this->getViewPath($viewPath);
        
        // Security check: Ensure the content file exists
        if (!file_exists($contentFile)) {
             // Use the base class's throw for simplicity
             throw new \Exception("View file not found: {$viewPath}");
        }

        // 4. Extract data for use in the master layout and content view
        extract($finalData);
        
        // 5. Define a special variable to hold the path to the content view.
        $__content_file = $contentFile;
        
        // 6. Load the master layout file (this will require the content file inside it)
        $projectRoot = dirname(__DIR__, 2); 
        
        // Build the absolute path to the master layout file
        $masterLayoutPath = $projectRoot . '/resources/layout/master.php'; // 🛑 Corrected target path 🛑

        // Load the master layout file
        require $masterLayoutPath;  
    }


    /**
     * Helper to resolve the full path to a view file.
     */
    protected function getViewPath(string $viewPath): string
    {
        return __DIR__ . '/../../resources/views/' . $viewPath . '.php';
    }

    /**
     * Redirects the user to a specified URI.
     * * @param string $uri The URI to redirect to.
     */
    protected function redirect(string $uri): void
    {
        header("Location: {$uri}");
        exit();
    }


    /**
     * Sends HTTP headers to prevent the browser from caching the page.
     * This is essential for preventing logged-out users from seeing cached pages
     * when using the browser's back button.
     */
    protected function preventCache(): void
    {
        // Standard Cache Prevention
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); 
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        
        // Back/Forward Cache (BFcache) Prevention
        // This instructs the browser not to use bfcache for this page.
        header('Permissions-Policy: interest-cohort=()');
    }

}