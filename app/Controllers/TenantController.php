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
        $this->tenantModel = new TenantModel();
        $this->planModel = new PlanModel();
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
        $this->auditModel = new AuditModel();
        $this->subscriptionModel = new SubscriptionModel();
    }

    /**
     * Shows a list of all tenants (Super Admin only).
     */
    public function index(): void
    {
        $tenants = $this->tenantModel->all(); 
        
        $this->view('superadmin/tenants/index', [
            'title' => 'Tenant Management',
            'tenants' => $tenants,
            'success' => $_SESSION['flash_success'] ?? null,
        ]);
        unset($_SESSION['flash_success']);
    }

    /**
     * Shows the form for creating a new tenant.
     */
    public function create(): void
    {
        $availablePlans = $this->planModel->all(); 
        
        $this->view('superadmin/tenants/create', [
            'title' => 'Create New Tenant',
            'error' => $_SESSION['flash_error'] ?? null,
            'input' => $_SESSION['flash_input'] ?? [],
            'plans' => $availablePlans,
        ]);
        unset($_SESSION['flash_error'], $_SESSION['flash_input']);
    }

    /**
     * Handles the POST request to save the new tenant and admin user.
     */
    public function store(): void
    {
        // 1. Basic validation
        if (empty($_POST['tenant_name']) || empty($_POST['subdomain']) || empty($_POST['admin_email']) || empty($_POST['admin_password']) || empty($_POST['plan_id'])) {
            $_SESSION['flash_error'] = 'All required fields (Company Name, Subdomain, Admin Email/Password, and Plan) must be filled.';
            $_SESSION['flash_input'] = $_POST;
            $this->redirect('/tenants/create');
            return;
        }

        try {
            // 2. Pre-Transaction Setup: Look up the Tenant Admin Role ID dynamically
            $tenantAdminRoleId = $this->roleModel->findIdByName('tenant_admin');
            
            if (!$tenantAdminRoleId) {
                throw new \Exception("System error: 'tenant_admin' role not found in the database.");
            }
            
            // SECURITY CHECK: Ensure the Super Admin's ID is valid for logging
            $actingUserId = Auth::userId();
            if ($actingUserId === 0) {
                 throw new \Exception("Security/Audit Failure: Super Admin user ID could not be retrieved from session. Please log out and log back in.");
            }
            
            // 3. Start Transaction
            $this->tenantModel->getDB()->beginTransaction();

            // A. Prepare Data
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
            
            // B. Execute Creation (Model 1: Tenant)
            $tenantId = $this->tenantModel->create($tenantData); 

            // C. Execute Creation (Model 2: User)
            // Execute the creation and CAPTURE the newly created Admin User's ID
            $adminUserId = $this->userModel->createUser($tenantId, $userData);

            // D. Execute Creation (Model 3: Subscription)
            $this->subscriptionModel->recordInitialSubscription(
                $tenantId, 
                $tenantData['plan_id'], 
                $tenantData['subscription_status'] // Should be 'trial' as per your initial setting
            );

            // E. Audit Logging (MUST occur before commit)
            $this->auditModel->log(
                $tenantId, 
                'TENANT_CREATED_WITH_ADMIN',
                [
                    'tenant_name' => $tenantData['name'],
                    'admin_email' => $userData['email'],
                    'plan_id' => $tenantData['plan_id'],
                ]
            );

            // 4. Commit Transaction
            $this->tenantModel->getDB()->commit();

            // 5. Trigger Success Notifications (After commit)
            
            // 5a. Notify the Super Admin (Existing)
            $this->notificationService->notifyUser(
                $tenantId, 
                $this->userId, // The Super Admin who performed the action
                'TENANT_PROVISIONING_SUCCESS', 
                "Tenant '{$tenantData['name']}' Provisioned",
                "Successfully created tenant {$tenantData['name']} and admin user {$userData['email']}."
            );

            // 5b. Notify the new Tenant Admin (NEW) 
            $this->notificationService->notifyUser(
                $tenantId, 
                $adminUserId, // The new admin user's ID
                'WELCOME_NEW_TENANT_ADMIN', 
                "Welcome to SikaPay, {$userData['first_name']}!",
                "Your account for the tenant '{$tenantData['name']}' has been successfully provisioned. You can now log in and begin setting up payroll."
            );
            
            // 6. Success Handling
            $_SESSION['flash_success'] = "Tenant '{$tenantData['name']}' created successfully. Initial admin: {$userData['email']}.";
            $this->redirect('/tenants');

        } catch (\Exception $e) {
            // 7. Failure: Rollback
            if ($this->tenantModel->getDB()->inTransaction()) {
                $this->tenantModel->getDB()->rollBack();
            }
            
            $_SESSION['flash_error'] = "Error creating tenant: " . $e->getMessage();
            $_SESSION['flash_input'] = $_POST;
            $this->redirect('/tenants/create');
        }
    }
}