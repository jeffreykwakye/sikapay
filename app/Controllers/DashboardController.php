<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Core\Auth; // Needed for static checks

class DashboardController extends Controller
{
    /**
     * Displays the primary application dashboard.
     */
    public function index(): void
    {
        // Prevent browser caching of this sensitive page
        $this->preventCache(); 

        // if (!$this->auth->check()) {
        //     // If not logged in, redirect to login page
        //     $this->redirect('/login');
        // }

        // --- Authentication Checks ---
        
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
    }
}