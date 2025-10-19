<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Core\Auth; // Kept for the static check
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder; 
use \Throwable;

class DashboardController extends Controller
{
    /**
     * Displays the primary application dashboard.
     */
    public function index(): void
    {
        try {
            // 🚨 REMOVED: $this->preventCache() is now called globally 
            // in the parent Controller's constructor for authenticated pages.

            // --- Authentication Checks & Data Retrieval ---
            // Relying on parent properties ($this->auth, $this->tenantId, etc.) 
            // initialized in the base Controller's constructor.
            
            // Check authorization if needed (e.g., if different permissions were required)
            // $this->checkPermission('self:view_dashboard'); 

            $isSuperAdmin = $this->auth->isSuperAdmin();
            // $this->tenantId is already available from the parent constructor.

            // Data we pass to the view
            $data = [
                'title' => $isSuperAdmin ? 'Super Admin Dashboard' : 'Tenant Dashboard',
                'userRole' => $isSuperAdmin ? 'Super Admin (System-wide access)' : 'Tenant User (Limited access)',
                // Use $this->tenantName from the parent for cleaner context
                'tenantInfo' => $isSuperAdmin ? 'Operating in System Context' : "Operating under Tenant: {$this->tenantName} (ID: {$this->tenantId})",
                'welcomeMessage' => "Welcome to SikaPay!",
            ];

            $this->view('dashboard/index', $data);

        } catch (Throwable $e) {
            // Catch any critical error during dashboard load
            
            // Log the critical failure
            // Use parent properties for safer logging context
            $userId = $this->userId > 0 ? (string)$this->userId : 'N/A';
            
            Log::critical("Dashboard Load Failed for User {$userId}.", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
            ]);

            // Respond with a controlled 500 error page
            ErrorResponder::respond(500, "We could not load your dashboard due to a system error.");
        }
    }
}