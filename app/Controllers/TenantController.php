<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Models\TenantModel;
use Jeffrey\Sikapay\Models\PlanModel;
use Jeffrey\Sikapay\Models\SubscriptionModel;
use Jeffrey\Sikapay\Models\UserModel;
use Jeffrey\Sikapay\Models\RoleModel;
use Jeffrey\Sikapay\Models\AuditModel;
use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder; 
use \Throwable;

class TenantController extends Controller
{
    protected TenantModel $tenantModel;
    protected PlanModel $planModel;
    protected SubscriptionModel $subscriptionModel;
    protected UserModel $userModel;
    protected RoleModel $roleModel;
    protected AuditModel $auditModel;

    public function __construct()
    {
        parent::__construct();
        
        try {
            // Model/Service Instantiation Check
            $this->tenantModel = new TenantModel();
            $this->planModel = new PlanModel();
            $this->userModel = new UserModel();
            $this->roleModel = new RoleModel();
            $this->auditModel = new AuditModel();
            $this->subscriptionModel = new SubscriptionModel();
        } catch (Throwable $e) {
            // If any model/service fails to initialize (e.g., DB connection issue)
            Log::critical("TenantController failed to initialize models: " . $e->getMessage());
            ErrorResponder::respond(500, "A critical system error occurred during tenant management initialization.");
        }
    }

    /**
     * Shows a list of all tenants (Super Admin only).
     */
    public function index(): void
    {
        try {
            // Note: Permissions check for Super Admin should ideally be handled by a middleware/parent check.
            if (!Auth::isSuperAdmin()) {
                ErrorResponder::respond(403, "Access Denied. Super Admin privileges required.");
                return;
            }

            $tenants = $this->tenantModel->all(); 
            
            $this->view('superadmin/tenants/index', [
                'title' => 'Tenant Management',
                'tenants' => $tenants,
                'success' => $_SESSION['flash_success'] ?? null,
            ]);
            unset($_SESSION['flash_success']);
        } catch (Throwable $e) {
            Log::error("Failed to load tenant list: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load the tenant list due to a system error.");
        }
    }

    /**
     * Shows the form for creating a new tenant.
     */
    public function create(): void
    {
        try {
             if (!Auth::isSuperAdmin()) {
                ErrorResponder::respond(403, "Access Denied. Super Admin privileges required.");
                return;
            }
            
            $availablePlans = $this->planModel->all(); 
            
            $this->view('superadmin/tenants/create', [
                'title' => 'Create New Tenant',
                'error' => $_SESSION['flash_error'] ?? null,
                'input' => $_SESSION['flash_input'] ?? [],
                'plans' => $availablePlans,
            ]);
            unset($_SESSION['flash_error'], $_SESSION['flash_input']);
        } catch (Throwable $e) {
            Log::error("Failed to load tenant creation form/plans: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load the tenant creation form due to a system error.");
        }
    }

    /**
     * Handles the POST request to save the new tenant and admin user.
     */
    public function store(): void
    {
        // 1. Basic validation (remains unchanged)
        if (empty($_POST['tenant_name']) || empty($_POST['subdomain']) || empty($_POST['admin_email']) || empty($_POST['admin_password']) || empty($_POST['plan_id'])) {
            $_SESSION['flash_error'] = 'All required fields (Company Name, Subdomain, Admin Email/Password, and Plan) must be filled.';
            $_SESSION['flash_input'] = $_POST;
            $this->redirect('/tenants/create');
            return;
        }

        // Check Super Admin privilege before proceeding with a major system action
        if (!Auth::isSuperAdmin()) {
            $_SESSION['flash_error'] = 'Permission denied. System Admin required.';
            $this->redirect('/tenants');
            return;
        }

        // Initialize variables for rollback logging
        $tenantData = [];
        $userData = [];
        $adminUserId = null;
        $tenantId = null;
        $db = $this->tenantModel->getDB(); // Get PDO object for transaction control

        try {
            // 2. Pre-Transaction Setup: Look up the Tenant Admin Role ID dynamically
            $tenantAdminRoleId = $this->roleModel->findIdByName('tenant_admin');
            
            if (!$tenantAdminRoleId) {
                // If the system role is missing, this is a critical configuration error.
                throw new \Exception("System error: 'tenant_admin' role not found in the database. Aborting.");
            }
            
            // SECURITY CHECK: Ensure the Super Admin's ID is valid for logging
            $actingUserId = Auth::userId();
            if ($actingUserId === 0) {
                 throw new \Exception("Security/Audit Failure: Super Admin user ID could not be retrieved from session.");
            }
            
            // 3. Start Transaction
            $db->beginTransaction();

            // A. Prepare Data (Assign to pre-declared variables for safe logging in catch)
            $tenantData = [
                'name' => $_POST['tenant_name'],
                'subdomain' => strtolower($_POST['subdomain']),
                'subscription_status' => $_POST['subscription_status'] ?? 'trial',
                'payroll_approval_flow' => $_POST['payroll_flow'] ?? 'ACCOUNTANT_FINAL',
                'plan_id' => (int)$_POST['plan_id'],
            ];

            $userData = [
                'role_id' => $tenantAdminRoleId, 
                'email' => $_POST['admin_email'],
                'password' => password_hash($_POST['admin_password'], PASSWORD_DEFAULT),
                'first_name' => $_POST['admin_fname'],
                'last_name' => $_POST['admin_lname'],
                'other_name' => null, 
                'phone' => null, 
            ];
            
            // B. Execute Creation (Model 1: Tenant) - Must throw on failure
            $tenantId = $this->tenantModel->create($tenantData); 
            if (!$tenantId) {
                throw new \Exception("Tenant record creation failed unexpectedly.");
            }

            // C. Execute Creation (Model 2: User) - Must throw on failure
            $adminUserId = $this->userModel->createUser($tenantId, $userData);
            if (!$adminUserId) {
                throw new \Exception("Admin user creation failed unexpectedly.");
            }

            // D. Execute Creation (Model 3: Subscription) - Must throw on failure
            if (!$this->subscriptionModel->recordInitialSubscription(
                $tenantId, 
                $tenantData['plan_id'], 
                $tenantData['subscription_status']
            )) {
                 throw new \Exception("Initial subscription record failed unexpectedly.");
            }

            // E. Audit Logging (MUST occur before commit)
            $this->auditModel->log(
                $tenantId, // Log against the newly created tenant
                'TENANT_CREATED_WITH_ADMIN',
                [
                    'tenant_name' => $tenantData['name'],
                    'admin_email' => $userData['email'],
                    'plan_id' => $tenantData['plan_id'],
                    'super_admin_id' => $actingUserId // Log who did it
                ]
            );

            // 4. Commit Transaction
            $db->commit();

            // 5. Trigger Success Notifications (After commit) - Log errors if notifications fail
            
            // 5a. Notify the Super Admin
            if (!$this->notificationService->notifyUser(
                $tenantId, 
                $actingUserId, 
                'TENANT_PROVISIONING_SUCCESS', 
                "Tenant '{$tenantData['name']}' Provisioned",
                "Successfully created tenant {$tenantData['name']} and admin user {$userData['email']}."
            )) {
                Log::error("Failed to notify Super Admin {$actingUserId} of tenant creation success.");
            }

            // 5b. Notify the new Tenant Admin
            if (!$this->notificationService->notifyUser(
                $tenantId, 
                $adminUserId, 
                'WELCOME_NEW_TENANT_ADMIN', 
                "Welcome to SikaPay, {$userData['first_name']}!",
                "Your account for the tenant '{$tenantData['name']}' has been successfully provisioned. You can now log in and begin setting up payroll."
            )) {
                 Log::error("Failed to notify new Tenant Admin {$adminUserId} of tenant creation success.");
            }
            
            // 6. Success Handling
            $_SESSION['flash_success'] = "Tenant '{$tenantData['name']}' created successfully. Initial admin: {$userData['email']}.";
            $this->redirect('/tenants');

        } catch (Throwable $e) { 
            // Use Throwable to catch all critical system/DB errors
            // 7. Failure: Rollback
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            // Log failure as CRITICAL since this is a failed transaction involving new tenant creation.
            Log::critical("Tenant Provisioning Transaction FAILED: " . $e->getMessage(), [
                'super_admin_id' => $actingUserId ?? 'N/A',
                'tenant_name' => $tenantData['name'] ?? 'N/A',
                'admin_email' => $userData['email'] ?? 'N/A',
                'line' => $e->getLine()
            ]);
            
            // Provide a generic, safe error message to the user
            $_SESSION['flash_error'] = "Error creating tenant: A critical system error occurred. Please check logs for details.";
            $_SESSION['flash_input'] = $_POST;
            $this->redirect('/tenants/create');
        }
    }
}