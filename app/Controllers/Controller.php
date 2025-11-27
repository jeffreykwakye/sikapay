<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Core\Auth; 
use Jeffrey\Sikapay\Core\View;
use Jeffrey\Sikapay\Core\Log; 
use Jeffrey\Sikapay\Core\ErrorResponder;
use Jeffrey\Sikapay\Services\SubscriptionService;
use Jeffrey\Sikapay\Models\TenantModel;
use Jeffrey\Sikapay\Models\UserModel;
use Jeffrey\Sikapay\Models\TenantProfileModel;
use Jeffrey\Sikapay\Models\SubscriptionModel;
use Jeffrey\Sikapay\Models\UserProfileModel; // NEW
use Jeffrey\Sikapay\Models\SupportMessageModel; // NEW
use Jeffrey\Sikapay\Helpers\ViewHelper;
use Jeffrey\Sikapay\Security\CsrfToken;
use Jeffrey\Sikapay\Services\NotificationService;


abstract class Controller
{
    protected Auth $auth;
    protected View $view; 
    protected int $userId; 
    protected int $tenantId; 

    protected NotificationService $notificationService;
    protected SubscriptionService $subscriptionService;
    protected TenantModel $tenantModel;
    protected UserModel $userModel; 
    protected TenantProfileModel $tenantProfileModel;
    protected SubscriptionModel $subscriptionModel;
    protected UserProfileModel $userProfileModel; // NEW PROPERTY
    protected SupportMessageModel $supportMessageModel; // NEW PROPERTY
    protected int $openSupportTicketsCount = 0; // NEW PROPERTY

    
    protected ?string $tenantName = null;
    protected ?string $tenantLogo = null;
    protected ?string $subscriptionPlan = null;
    protected ?string $subscriptionStatus = null;
    protected array $userName = ['first_name' => null, 'last_name' => null];
    protected ?string $userEmail = null;
    protected ?string $userProfileImageUrl = null; // NEW PROPERTY

    
    public function __construct()
    {
        // 1. Initialize Auth service and Context
        $this->auth = Auth::getInstance();
        $this->view = new View();
        
        $this->userId = Auth::userId();
        $this->tenantId = Auth::tenantId();

        // Call global security header methods early
        $this->setSecurityHeaders();
        
        // CONDITIONAL INITIALIZATION BLOCK
        if ($this->userId > 0) {
            
            // Prevent caching for ALL authenticated pages
            $this->preventCache(); 
            
            // Initialize Models/Services
            $this->userModel = new UserModel();
            $this->notificationService = new NotificationService();
            $this->subscriptionService = new SubscriptionService();
            $this->tenantModel = new TenantModel();
            $this->tenantProfileModel = new TenantProfileModel();
            $this->subscriptionModel = new SubscriptionModel();
            $this->userProfileModel = new UserProfileModel(); // NEW INSTANTIATION
            $this->supportMessageModel = new SupportMessageModel(); // NEW INSTANTIATION

            // Fetch contextual data
            try {
                $user = $this->userModel->find($this->userId);
                if ($user) {
                    $this->userName = ['first_name' => $user['first_name'], 'last_name' => $user['last_name']];
                    $this->userEmail = $user['email'];
                }
                $userProfile = $this->userProfileModel->findByUserId($this->userId); // NEW FETCH
                $this->userProfileImageUrl = $userProfile['profile_picture_url'] ?? null; // NEW ASSIGNMENT

                if ($this->tenantId > 0 && !$this->auth->isSuperAdmin()) {
                    $this->tenantName = $this->tenantModel->getNameById($this->tenantId);
                    $tenantProfile = $this->tenantProfileModel->findByTenantId($this->tenantId);
                    $this->tenantLogo = $tenantProfile['logo_path'] ?? null;
                    $subscription = $this->subscriptionModel->getCurrentSubscription($this->tenantId);
                    $this->subscriptionPlan = $subscription['plan_name'] ?? null;
                    $this->subscriptionStatus = $subscription['status'] ?? null;
                }

                // NEW: Fetch open support tickets count for Super Admin
                if (Auth::isSuperAdmin()) {
                    $this->openSupportTicketsCount = $this->supportMessageModel->getOpenTicketsCount();
                }
            } catch (\Exception $e) {
                // Catch model initialization failure (e.g., DB down)
                Log::critical("Base Controller Context Initialization Failed for User ID {$this->userId}: " . $e->getMessage());

                // Halt flow with a generic server error response to the user
                ErrorResponder::respond(500, "A critical system error occurred during initialization. Please try again.");
            }
        } 
    }

    // ------------------------------------------------------------------
    // SECURITY-FOCUSED AUTHORIZATION METHODS
    // ------------------------------------------------------------------

    /**
     * Checks if the currently authenticated user has the required permission.
     * If permission is denied, execution is halted with a generic 403 response, 
     * but the failure is logged with specific context for debugging.
     *
     * @param string $permissionKey The key of the required permission (e.g., 'employee:create').
     */
    protected function checkPermission(string $permissionKey): void
    {
        if (!$this->auth->hasPermission($permissionKey)) {
            
            // 1. Log the specific permission failure for audit/debugging purposes
            Log::error("Authorization Failed (403): User {$this->userId} in Tenant {$this->tenantId} attempted access without permission '{$permissionKey}'.", [
                'user_id' => $this->userId,
                'tenant_id' => $this->tenantId,
                'permission_key' => $permissionKey
            ]);

            // 2. Show a generic, non-specific 403 error to the user/hacker
            ErrorResponder::respond(403, "Access to this feature is restricted by your role permissions.");
        }
        // If permission check passes, the method returns normally, and execution continues.
    }

    /**
     * Checks if the tenant's subscription is active enough to perform state-changing actions.
     * If not, redirects to the subscription management page.
     */
    protected function checkActionIsAllowed(): void
    {
        if ($this->auth->isSuperAdmin()) {
            return; // Super admins are not subject to this
        }

        if (!$this->subscriptionService->isActionable($this->tenantId)) {
            $_SESSION['flash_error'] = 'Your subscription is not active. Please renew to perform this action.';
            $this->redirect('/subscription');
        }
    }


    /**
     * Loads the view using the master layout and passes common system data.
     */
    protected function view(string $viewPath, array $data = []): void
    {
        // 1. Common system data
        $commonData = [
            // CRITICAL: We pass the AUTH object here. The sidebar must use $auth->hasPermission()
            'auth' => $this->auth, 
            'userId' => $this->userId,
            'tenantId' => $this->tenantId,
            
            'tenantName' => $this->tenantName ?? 'System/Public',
            'tenantLogo' => $this->tenantLogo,
            'subscriptionPlan' => $this->subscriptionPlan,
            'subscriptionStatus' => $this->subscriptionStatus,
            'userFirstName' => $this->userName['first_name'] ?? 'User',
            'userLastName' => $this->userName['last_name'] ?? '',
            'userEmail' => $this->userEmail,
            'userProfileImageUrl' => $this->userProfileImageUrl, // NEW DATA

            'isSuperAdmin' => $this->auth->isSuperAdmin(),
            'openSupportTicketsCount' => $this->openSupportTicketsCount, // NEW DATA
            'viewPath' => $viewPath, // Pass the view path for sidebar active state
            
            'unreadNotificationCount' => (isset($this->notificationService) && $this->userId > 0)
                ? $this->notificationService->getUnreadCount($this->userId) 
                : 0,
            'navbarNotifications' => (isset($this->notificationService) && $this->userId > 0)
                ? $this->notificationService->getRecentNotifications($this->userId, 5)
                : [],
        ];

        // 2. Security-focused helpers to be available in all views
        $securityHelpers = [
            // Aliases for XSS and ID hardening
            'h'         => [ViewHelper::class, 'h'],
            'id'        => [ViewHelper::class, 'id'],
            'nl2br_h'   => [ViewHelper::class, 'nl2br_h'],
            
            // CSRF Token Class/Reference
            'CsrfToken' => CsrfToken::class, 
        ];

        // Merge common data, security helpers, and controller-specific data
        $finalData = array_merge($commonData, $securityHelpers, $data);
        
        $contentFile = $this->getViewPath($viewPath);
        
        // 4. Check for View File Existence and Handle Failure
        if (!file_exists($contentFile)) {
            Log::critical("View file not found: {$contentFile}. Requested path: {$viewPath}");
            ErrorResponder::respond(500, "View file {$viewPath} not found. System Configuration Error.");
        }

        // 5. Extract data and load master layout
        extract($finalData);
        $__content_file = $contentFile;
        
        $projectRoot = dirname(__DIR__, 2); 
        $masterLayoutPath = $projectRoot . '/resources/layout/master.php'; 

        // 6. Check for Master Layout Existence and Handle Failure
        if (!file_exists($masterLayoutPath)) {
            Log::critical("Master layout file not found at: {$masterLayoutPath}");
            ErrorResponder::respond(500, "Master layout file is missing. System Configuration Error.");
        }

        require $masterLayoutPath;
    }


    /**
     * Loads a view without the full master layout (for public pages like Login).
     */
    protected function viewLogin(string $viewPath, array $data = []): void
    {
        $securityHelpers = [
            'h'         => [ViewHelper::class, 'h'],
            'id'        => [ViewHelper::class, 'id'],
            'CsrfToken' => CsrfToken::class, 
        ];

        $finalData = array_merge($securityHelpers, $data);
        $contentFile = $this->getViewPath($viewPath);
        
        // Check for View File Existence and Handle Failure
        if (!file_exists($contentFile)) {
            Log::critical("Login View file not found: {$contentFile}. Requested path: {$viewPath}");
            ErrorResponder::respond(500, "Login View file {$viewPath} not found. System Configuration Error.");
        }

        extract($finalData);
        
        $projectRoot = dirname(__DIR__, 2);
        $minimalLayoutPath = $projectRoot . '/resources/layout/minimal.php';

        if (file_exists($minimalLayoutPath)) {
            $__content_file = $contentFile;
            require $minimalLayoutPath;
        } else {
            Log::critical("Minimal layout file not found at: {$minimalLayoutPath}. Loading content directly.");
            require $contentFile;
        }
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
     * @param string $uri The URI to redirect to.
     */
    protected function redirect(string $uri): void
    {
        // Explicitly call session_write_close() before redirect
        // This releases the session file lock, preventing deadlocks (especially common 
        // when one request causes a redirect followed by an immediate second request).
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        header("Location: {$uri}");
        exit();
    }

    
    /**
     * Sends HTTP headers to prevent the browser from caching the page.
     */
    protected function preventCache(): void
    {
        // Standard Cache Prevention
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); 
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        
        // Back/Forward Cache (BFcache) Prevention
        header('Permissions-Policy: interest-cohort=()');
    }


    /**
     * Enforces critical HTTP security headers globally.
     */
    protected function setSecurityHeaders(): void
    {
        // XSS Protection: Tells the browser to enable its built-in XSS filter.
        header('X-XSS-Protection: 1; mode=block');

        // Clickjacking Protection: Prevents the page from being rendered in an iframe.
        header('X-Frame-Options: SAMEORIGIN');

        // MIME Type Sniffing Prevention: Forces the browser to strictly follow the MIME types declared in the Content-Type header.
        header('X-Content-Type-Options: nosniff');
        
        // Content Security Policy (CSP): Most advanced, but requires tuning. 
        // Start with a strict default (e.g., only allow content from the same origin).
        // If the application uses external resources, this policy will need to be expanded.
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
    }

}