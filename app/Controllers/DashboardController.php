<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Core\Auth;
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
            // Prevent browser caching of this sensitive page
            $this->preventCache(); 

            // Note: AuthMiddleware should handle the primary login check. 
            // We proceed assuming authentication has passed.

            // --- Authentication Checks & Data Retrieval ---
            
            // These static calls rely on the Auth class successfully fetching data, which can fail.
            $isSuperAdmin = Auth::isSuperAdmin();
            $tenantId = Auth::tenantId();

            // Data we pass to the view
            $data = [
                'title' => $isSuperAdmin ? 'Super Admin Dashboard' : 'Tenant Dashboard',
                'userRole' => $isSuperAdmin ? 'Super Admin (System-wide access)' : 'Tenant User (Limited access)',
                'tenantInfo' => $isSuperAdmin ? 'Operating in System Context' : "Operating under Tenant ID: {$tenantId}",
                'welcomeMessage' => "Welcome to SikaPay!",
            ];

            $this->view('dashboard/index', $data);

        } catch (Throwable $e) {
            // Catch any critical error during dashboard load (e.g., Auth data retrieval failure)
            
            // Log the critical failure
            $userId = Auth::userId() ?? 'N/A';
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