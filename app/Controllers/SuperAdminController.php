<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use Jeffrey\Sikapay\Models\TenantModel;
use Jeffrey\Sikapay\Models\PlanModel;
use Jeffrey\Sikapay\Models\SubscriptionModel;
use Jeffrey\Sikapay\Models\UserModel;
use Jeffrey\Sikapay\Models\RoleModel;
use Jeffrey\Sikapay\Models\AuditModel;
use Jeffrey\Sikapay\Models\EmployeeModel;
use Jeffrey\Sikapay\Models\FeatureModel; // NEW
use Jeffrey\Sikapay\Models\DepartmentModel; // NEW
use Jeffrey\Sikapay\Models\PositionModel; // NEW
use Jeffrey\Sikapay\Models\PayrollPeriodModel; // NEW
use Jeffrey\Sikapay\Models\PayslipModel; // NEW

use Jeffrey\Sikapay\Services\NotificationService;
use Jeffrey\Sikapay\Services\EmailService;
use Jeffrey\Sikapay\Core\Validator;

class SuperAdminController extends Controller
{
    protected TenantModel $tenantModel;
    protected PlanModel $planModel;
    protected SubscriptionModel $subscriptionModel;
    protected UserModel $userModel;
    protected RoleModel $roleModel;
    protected AuditModel $auditModel;
    protected EmployeeModel $employeeModel;
    protected NotificationService $notificationService;
    protected FeatureModel $featureModel; // NEW
    protected DepartmentModel $departmentModel; // NEW
    protected PositionModel $positionModel; // NEW
    protected PayrollPeriodModel $payrollPeriodModel; // NEW
    protected PayslipModel $payslipModel; // NEW
    protected EmailService $emailService;

    public function __construct()
    {
        parent::__construct();
        if (!$this->auth->isSuperAdmin()) {
            ErrorResponder::respond(403, 'Access denied. Super administrator privileges required.');
        }

        try {
            $this->tenantModel = new TenantModel();
            $this->planModel = new PlanModel();
            $this->subscriptionModel = new SubscriptionModel();
            $this->userModel = new UserModel();
            $this->roleModel = new RoleModel();
            $this->auditModel = new AuditModel();
            $this->employeeModel = new EmployeeModel();
            $this->notificationService = new NotificationService();
            $this->emailService = new EmailService();
            $this->featureModel = new FeatureModel(); // NEW
            $this->departmentModel = new DepartmentModel(); // NEW
            $this->positionModel = new PositionModel(); // NEW
            $this->payrollPeriodModel = new PayrollPeriodModel(); // NEW
            $this->payslipModel = new PayslipModel(); // NEW
        } catch (\Throwable $e) {
            Log::critical('SuperAdminController failed to initialize models/services: ' . $e->getMessage());
            ErrorResponder::respond(500, 'A critical system error occurred during Super Admin initialization.');
        }
    }
    
    public function sendEmailToTenant(string $tenantId): void
    {
        $tenantId = (int) $tenantId;
        $this->checkPermission('super:manage_tenants');

        $validator = new Validator($_POST);
        $validator->validate([
            'subject' => 'required|min:3',
            'body' => 'required|min:10',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = 'Email failed: ' . implode('<br>', $validator->errors());
            $this->redirect('/tenants/' . $tenantId);
            return;
        }

        try {
            $tenant = $this->tenantModel->find($tenantId);
            if (!$tenant) {
                ErrorResponder::respond(404, 'Tenant not found.');
                return;
            }

            $adminUser = $this->userModel->findTenantAdminUser($tenantId);
            if (!$adminUser) {
                $_SESSION['flash_error'] = 'Could not find an admin user for this tenant to send the email to.';
                $this->redirect('/tenants/' . $tenantId);
                return;
            }

            $subject = $validator->get('subject');
            $body = $validator->get('body');

            if ($this->emailService->send($adminUser['email'], $subject, $body)) {
                $_SESSION['flash_success'] = 'Email sent successfully to ' . $adminUser['email'];
                $this->auditModel->log($tenantId, 'EMAIL_SENT_TO_TENANT', ['subject' => $subject, 'sent_by_user_id' => Auth::userId()]);
            } else {
                $_SESSION['flash_error'] = 'Failed to send email.';
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send email to tenant ' . $tenantId . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error sending email: ' . $e->getMessage();
        }

        $this->redirect('/tenants/' . $tenantId);
    }

    /**
     * Display the main Super Admin dashboard.
     */
    public function index(): void
    {
        try {
            $stats = [
                'total_tenants' => $this->tenantModel->countAllTenants(),
                'active_subscriptions' => $this->subscriptionModel->countActiveSubscriptions(),
                'mrr' => $this->subscriptionModel->calculateMRR(),
                'new_tenants_last_30_days' => $this->tenantModel->countNewTenantsLast30Days(),
            ];

            $charts = [
                'revenue_trend' => $this->subscriptionModel->getRevenueTrend(),
                'plan_distribution' => $this->planModel->getPlanDistribution(),
            ];

            $tables = [
                'new_tenants' => $this->tenantModel->getNewTenants(30),
                'at_risk_subscriptions' => $this->subscriptionModel->getAtRiskSubscriptions(7),
            ];

            $this->view('superadmin/index', [
                'title' => 'Super Admin Dashboard',
                'stats' => $stats,
                'charts' => $charts,
                'tables' => $tables,
            ]);
        } catch (\Throwable $e) {
            Log::critical('Super Admin Dashboard failed to load: ' . $e->getMessage());
            ErrorResponder::respond(500, 'Could not load the Super Admin dashboard.');
        }
    }

    /**
     * Display all subscription plans.
     */
    public function plans(): void
    {
        try {
            $plans = $this->planModel->all();
            $allFeatures = $this->featureModel->all(); // NEW
            $this->view('superadmin/plans/index', [
                'title' => 'Manage Subscription Plans',
                'plans' => $plans,
                'allFeatures' => $allFeatures, // NEW
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to load subscription plans page: ' . $e->getMessage());
            ErrorResponder::respond(500, 'Could not load subscription plans.');
        }
    }

    /**
     * Display all subscriptions.
     */
    public function subscriptions(): void
    {
        try {
            $subscriptions = $this->subscriptionModel->getAllSubscriptionsWithTenantAndPlan();
            $this->view('superadmin/subscriptions/index', [
                'title' => 'View All Subscriptions',
                'subscriptions' => $subscriptions,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to load subscriptions page: ' . $e->getMessage());
            ErrorResponder::respond(500, 'Could not load subscriptions.');
        }
    }

    /**
     * Shows a list of all tenants (Super Admin only).
     */
    public function tenantsIndex(): void
    {
        try {
            $tenants = $this->tenantModel->all(); 
            
            $this->view('superadmin/tenants/index', [
                'title' => 'Tenant Management',
                'tenants' => $tenants,
                'success' => $_SESSION['flash_success'] ?? null,
            ]);
            unset($_SESSION['flash_success']);
        } catch (\Throwable $e) {
            Log::error("Failed to load tenant list: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load the tenant list due to a system error.");
        }
    }

    /**
     * Shows the form for creating a new tenant.
     */
    public function tenantsCreate(): void
    {
        try {
            $availablePlans = $this->planModel->all(); 
            
            $this->view('superadmin/tenants/create', [
                'title' => 'Create New Tenant',
                'error' => $_SESSION['flash_error'] ?? null,
                'input' => $_SESSION['flash_input'] ?? [],
                'plans' => $availablePlans,
            ]);
            unset($_SESSION['flash_error'], $_SESSION['flash_input']);
        } catch (\Throwable $e) {
            Log::error("Failed to load tenant creation form/plans: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load the tenant creation form due to a system error.");
        }
    }

    /**
     * Handles the POST request to save the new tenant and admin user.
     */
    public function tenantsStore(): void
    {
        //  1. HARDENED: Validation and Sanitization
        $validator = new Validator($_POST);
        
        $validator->validate([
            'tenant_name' => 'required|min:3|max:100',
            'subdomain' => 'required|alpha_dash|min:3|max:50', // alpha_dash enforces safe URLs
            'admin_email' => 'required|email',
            'admin_password' => 'required|min:8',
            'admin_fname' => 'required|min:2',
            'admin_lname' => 'required|min:2',
            'plan_id' => 'required|int|min:1',
            'subscription_status' => 'optional', // Sanitized later
            'payroll_flow' => 'optional', // Sanitized later
        ]);

        if ($validator->fails()) {
            $errors = implode('<br>', $validator->errors());
            $_SESSION['flash_error'] = "Tenant creation failed due to invalid input: <br>{$errors}";
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/tenants/create');
            return;
        }

        // Initialize variables for rollback logging
        $tenantData = [];
        $userData = [];
        $adminUserId = null;
        $tenantId = null;
        $db = $this->tenantModel->getDB(); // Get PDO object for transaction control
        $actingUserId = Auth::userId();

        try {
            // SECURITY CHECK: Ensure the Super Admin's ID is valid for logging
            if ($actingUserId === 0) {
                 throw new \Exception("Security/Audit Failure: Super Admin user ID could not be retrieved from session.");
            }
            
            // 2. Pre-Transaction Setup: Look up the Tenant Admin Role ID dynamically
            $tenantAdminRoleId = $this->roleModel->findIdByName('tenant_admin');
            
            if ($tenantAdminRoleId === null) {
                // If the system role is missing, this is a critical configuration error.
                throw new \Exception("System error: 'tenant_admin' role not found in the database. Aborting.");
            }
            
            // 3. Start Transaction
            $db->beginTransaction();

            // A. Prepare Data (Assign to pre-declared variables for safe logging in catch) -  USE VALIDATOR::GET()
            $tenantData = [
                'name' => $validator->get('tenant_name'),
                'subdomain' => strtolower($validator->get('subdomain')), // Ensure subdomain is lowercase
                // Apply defaults/sanitization for optional fields
                'subscription_status' => 'active', // Default to active when plan is selected
                'payroll_approval_flow' => $validator->get('payroll_flow') ?? 'ACCOUNTANT_FINAL',
                'plan_id' => $validator->get('plan_id', 'int'),
            ];

            $userData = [
                'role_id' => $tenantAdminRoleId, 
                'email' => $validator->get('admin_email'),
                'password' => password_hash($validator->get('admin_password'), PASSWORD_DEFAULT),
                'first_name' => $validator->get('admin_fname'),
                'last_name' => $validator->get('admin_lname'),
                'other_name' => $validator->get('admin_other_name'), 
                'phone' => $validator->get('admin_phone'), 
            ];
            
            // B. Execute Creation (Model 1: Tenant) - Must throw on failure
            $tenantId = $this->tenantModel->create($tenantData); 
            if (!$tenantId) {
                throw new \Exception("Tenant record creation failed unexpectedly.");
            }

            // --- FEATURE GATING: Check Tenant Admin Seats ---
            $subscriptionService = new \Jeffrey\Sikapay\Services\SubscriptionService();
            if (!$subscriptionService->canAddRoleUser($tenantId, 'tenant_admin')) {
                $planName = $subscriptionService->getCurrentPlanName($tenantId);
                $limit = $subscriptionService->getFeatureLimit($tenantId, 'tenant_admin_seats');
                throw new \Exception("Tenant Admin creation failed. The selected {$planName} Plan allows a maximum of {$limit} Tenant Admin seats. Please choose a different plan or upgrade.");
            }
            // --- END FEATURE GATING ---

            // C. Execute Creation (Model 2: User) - Must throw on failure
            // Note: createUser is assumed to sanitize $userData again internally, but it's already validated/sanitized here.
            $adminUserId = $this->userModel->createUser($tenantId, $userData);
            if (!$adminUserId) {
                throw new \Exception("Admin user creation failed unexpectedly.");
            }

            // Create User Profile record for the admin user with sensible defaults
            $userProfileModel = new \Jeffrey\Sikapay\Models\UserProfileModel();
            if (!$userProfileModel->createProfile([
                'user_id' => $adminUserId,
                'date_of_birth' => '2000-01-01', // Sensible default
                'nationality' => 'Ghanaian',
                'marital_status' => 'Single',
                'gender' => 'Other',
                'home_address' => 'N/A',
                'ssnit_number' => null,
                'tin_number' => null,
                'id_card_type' => 'Other',
                'id_card_number' => null,
                'emergency_contact_name' => 'Tenant Admin Emergency Contact', // Generic default
                'emergency_contact_phone' => 'N/A', // Default
            ])) {
                throw new \Exception("Admin user profile creation failed unexpectedly.");
            }

            // Create employee record for the admin user
            $hireDate = date('Y-m-d');
            $this->employeeModel->createEmployeeRecord([
                'user_id' => $adminUserId,
                'tenant_id' => $tenantId,
                'employee_id' => 'EMP-' . $adminUserId,
                'hire_date' => $hireDate,
                'current_position_id' => null, // No default position set
                'employment_type' => 'Full-Time', // Default
                'current_salary_ghs' => 0.00, // Default to 0.00
                'payment_method' => 'Bank Transfer', // Default
                'bank_name' => null,
                'bank_account_number' => null,
                'bank_branch' => null,
                'bank_account_name' => null,
                'is_payroll_eligible' => 1,
            ]);

            // Log employment history (Hired)
            $employmentHistoryModel = new \Jeffrey\Sikapay\Models\EmploymentHistoryModel();
            if (!$employmentHistoryModel->create([
                'user_id' => $adminUserId,
                'tenant_id' => $tenantId,
                'effective_date' => $hireDate,
                'record_type' => 'Hired',
                'old_salary' => null,
                'new_salary' => 0.00, // Matching default current_salary_ghs
                'notes' => 'Initial hiring record for Tenant Admin during tenant provisioning.',
            ])) {
                Log::error("Failed to create employment history for new Tenant Admin {$adminUserId}.");
            }

            // Fetch the plan details to check if default users should be created
            $selectedPlan = $this->planModel->find($tenantData['plan_id']);

            // Create HR Manager and Accountant users ONLY for Professional and Enterprise plans
            if ($selectedPlan && in_array($selectedPlan['name'], ['Professional', 'Enterprise'])) {
                $this->createDefaultUser($tenantId, 'hr_manager', 'HR', 'Manager', 'hr@' . $tenantData['subdomain'] . '.com');
                $this->createDefaultUser($tenantId, 'accountant', 'Accountant', 'User', 'accountant@' . $tenantData['subdomain'] . '.com');
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

        } catch (\Throwable $e) { 
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
            $_SESSION['flash_error'] = "Error creating tenant: A critical system error occurred. Please check logs for details. (" . substr($e->getMessage(), 0, 50) . "...)" ;
            $_SESSION['flash_input'] = $validator->all(); // Use sanitized input for flashback
            $this->redirect('/tenants/create');
        }
    }

    /**
     * Display details of a specific tenant.
     * @param int $id The ID of the tenant.
     */
    public function tenantsShow(string $id): void
    {
        $id = (int) $id;
        try {
            $tenant = $this->tenantModel->find($id);
            if (!$tenant) {
                ErrorResponder::respond(404, 'Tenant not found.');
                return;
            }

            $subscription = $this->subscriptionModel->getCurrentSubscription($id);
            $adminUser = $this->userModel->findTenantAdminUser($id);
            $subscriptionHistory = $this->subscriptionModel->getHistoryForTenant($id);

            // Fetch additional metrics for the tenant
            $totalEmployees = $this->employeeModel->getEmployeeCount($id);
            $totalDepartments = $this->departmentModel->countAllByTenantId($id);
            $totalPositions = $this->positionModel->countAllByTenantId($id);
            
            $lastPayrollPeriod = $this->payrollPeriodModel->getLatestClosedPeriod($id);
            $lastPayrollRunDate = $lastPayrollPeriod['end_date'] ?? 'N/A';
            $lastPayrollGrossPay = 0.0;
            if ($lastPayrollPeriod) {
                $payrollAggregates = $this->payslipModel->getAggregatedPayslipData((int)$lastPayrollPeriod['id'], $id);
                $lastPayrollGrossPay = $payrollAggregates['total_gross_pay'] ?? 0.0;
            }

            $availablePlans = $this->planModel->all(); // Fetch all plans for upgrade/downgrade modals

            $this->view('superadmin/tenants/show', [
                'title' => 'Tenant Details: ' . $tenant['name'],
                'tenant' => $tenant,
                'subscription' => $subscription,
                'adminUser' => $adminUser,
                'subscriptionHistory' => $subscriptionHistory,
                'totalEmployees' => $totalEmployees,
                'totalDepartments' => $totalDepartments,
                'totalPositions' => $totalPositions,
                'lastPayrollRunDate' => $lastPayrollRunDate,
                'lastPayrollGrossPay' => $lastPayrollGrossPay,
                'availablePlans' => $availablePlans, // NEW: Pass available plans
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to load tenant details for ID ' . $id . ': ' . $e->getMessage());
            ErrorResponder::respond(500, 'Could not load tenant details.');
        }
    }

    private function createDefaultUser(int $tenantId, string $roleName, string $firstName, string $lastName, string $email): void
    {
        $subscriptionService = new \Jeffrey\Sikapay\Services\SubscriptionService(); // Lazy load
        
        // --- FEATURE GATING: Check Role Seats ---
        if (!$subscriptionService->canAddRoleUser($tenantId, $roleName)) {
            $planName = $subscriptionService->getCurrentPlanName($tenantId);
            $limit = $subscriptionService->getFeatureLimit($tenantId, $roleName . '_seats');
            Log::warning("Default user creation skipped for role '{$roleName}'. Tenant {$tenantId} (Plan: {$planName}) has reached its limit of {$limit} {$roleName} seats.");
            // Optionally throw an exception here if default user creation is strictly mandatory for the plan
            // throw new \Exception("Default user creation failed for role '{$roleName}'. Plan limit exceeded.");
            return; // Skip creating this default user if limit is reached
        }
        // --- END FEATURE GATING ---

        $roleId = $this->roleModel->findIdByName($roleName);
        if ($roleId === null) {
            throw new \Exception("System error: '{$roleName}' role not found in the database. Aborting.");
        }

        $userId = $this->userModel->createUser($tenantId, [
            'role_id' => $roleId,
            'email' => $email,
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);

        if (!$userId) {
            throw new \Exception("Failed to create default user with role '{$roleName}'.");
        }

        // Create User Profile record for the default user with sensible defaults
        $userProfileModel = new \Jeffrey\Sikapay\Models\UserProfileModel();
        if (!$userProfileModel->createProfile([
            'user_id' => $userId,
            'date_of_birth' => '2000-01-01', // Sensible default
            'nationality' => 'Ghanaian',
            'marital_status' => 'Single',
            'gender' => 'Other',
            'home_address' => 'N/A',
            'ssnit_number' => null,
            'tin_number' => null,
            'id_card_type' => 'Other',
            'id_card_number' => null,
            'emergency_contact_name' => "{$roleName} Emergency Contact", // Generic default
            'emergency_contact_phone' => 'N/A', // Default
        ])) {
            throw new \Exception("Default user profile creation failed unexpectedly for role '{$roleName}'.");
        }

        $hireDate = date('Y-m-d');
        $this->employeeModel->createEmployeeRecord([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'employee_id' => 'EMP-' . $userId,
            'hire_date' => $hireDate,
            'current_position_id' => null,
            'employment_type' => 'Full-Time',
            'current_salary_ghs' => 0.00,
            'payment_method' => 'Bank Transfer',
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_branch' => null,
            'bank_account_name' => null,
            'is_payroll_eligible' => 1,
        ]);

        // Log employment history (Hired)
        $employmentHistoryModel = new \Jeffrey\Sikapay\Models\EmploymentHistoryModel();
        if (!$employmentHistoryModel->create([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'effective_date' => $hireDate,
            'record_type' => 'Hired',
            'old_salary' => null,
            'new_salary' => 0.00,
            'notes' => "Initial hiring record for {$roleName} during tenant provisioning.",
        ])) {
            Log::error("Failed to create employment history for default user {$userId} ({$roleName}).");
        }
    }

    /**
     * Handles the cancellation of a tenant's subscription.
     * @param int $id The ID of the tenant.
     */
    public function cancelTenantSubscription(string $id): void
    {
        $id = (int) $id;
        $this->checkPermission('super:manage_subscriptions');

        $validator = new Validator($_POST);
        $validator->validate([
            'reason' => 'required|min:3',
            'cancellation_date' => 'optional|date',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = 'Cancellation failed: ' . implode('<br>', $validator->errors());
            $this->redirect('/tenants/' . $id);
            return;
        }

        try {
            $reason = $validator->get('reason');
            $cancellationDate = $validator->get('cancellation_date', 'string', null);
            $success = $this->subscriptionModel->cancelSubscription($id, $reason, $cancellationDate);

            if ($success) {
                $_SESSION['flash_success'] = "Subscription for tenant {$id} cancelled successfully.";
                $this->auditModel->log(1, 'SUBSCRIPTION_CANCELLED', ['tenant_id' => $id, 'reason' => $reason]);
            } else {
                $_SESSION['flash_error'] = "Failed to cancel subscription for tenant {$id}.";
            }
        } catch (\Throwable $e) {
            Log::error('Failed to cancel subscription for tenant ' . $id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error cancelling subscription: ' . $e->getMessage();
        }
        $this->redirect('/tenants/' . $id);
    }

    /**
     * Handles the renewal of a tenant's subscription.
     * @param int $id The ID of the tenant.
     */
    public function renewTenantSubscription(string $id): void
    {
        $id = (int) $id;
        $this->checkPermission('super:manage_subscriptions');

        $validator = new Validator($_POST);
        $validator->validate([
            'plan_id' => 'required|int',
            'amount_paid' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = 'Renewal failed: ' . implode('<br>', $validator->errors());
            $this->redirect('/tenants/' . $id);
            return;
        }

        try {
            $planId = $validator->get('plan_id', 'int');
            $amountPaid = $validator->get('amount_paid', 'float');

            // --- Calculate new end date on the backend using strtotime for robustness ---
            $currentSubscription = $this->subscriptionModel->getCurrentSubscription($id);
            
            // Get the later of today or the current end date as a base timestamp
            $baseTimestamp = time();
            if ($currentSubscription && $currentSubscription['end_date']) {
                $currentEndTimestamp = strtotime($currentSubscription['end_date']);
                if ($currentEndTimestamp > $baseTimestamp) {
                    $baseTimestamp = $currentEndTimestamp;
                }
            }
            
            // Add one month to the base timestamp and format
            $newEndDate = date('Y-m-d', strtotime('+1 month', $baseTimestamp));
            // --- End of calculation ---

            $success = $this->subscriptionModel->renewSubscription($id, $planId, $newEndDate, $amountPaid);

            if ($success) {
                $_SESSION['flash_success'] = "Subscription for tenant {$id} renewed successfully.";
                $this->auditModel->log(1, 'SUBSCRIPTION_RENEWED', ['tenant_id' => $id, 'plan_id' => $planId, 'new_end_date' => $newEndDate, 'amount_paid' => $amountPaid]);
            } else {
                $_SESSION['flash_error'] = "Failed to renew subscription for tenant {$id}.";
            }
        } catch (\Throwable $e) {
            Log::error('Failed to renew subscription for tenant ' . $id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error renewing subscription: ' . $e->getMessage();
        }
        $this->redirect('/tenants/' . $id);
    }

    /**
     * Handles upgrading a tenant's subscription.
     * @param int $id The ID of the tenant.
     */
    public function upgradeTenantSubscription(string $id): void
    {
        $id = (int) $id;
        $this->checkPermission('super:manage_subscriptions');

        $validator = new Validator($_POST);
        $validator->validate([
            'new_plan_id' => 'required|int',
            'amount_paid' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = 'Upgrade failed: ' . implode('<br>', $validator->errors());
            $this->redirect('/tenants/' . $id);
            return;
        }

        try {
            $newPlanId = $validator->get('new_plan_id', 'int');
            $amountPaid = $validator->get('amount_paid', 'float');
            $success = $this->subscriptionModel->upgradeSubscription($id, $newPlanId, $amountPaid);

            if ($success) {
                $_SESSION['flash_success'] = "Subscription for tenant {$id} upgraded successfully.";
                $this->auditModel->log(1, 'SUBSCRIPTION_UPGRADED', ['tenant_id' => $id, 'new_plan_id' => $newPlanId, 'amount_paid' => $amountPaid]);
            } else {
                $_SESSION['flash_error'] = "Failed to upgrade subscription for tenant {$id}.";
            }
        } catch (\Throwable $e) {
            Log::error('Failed to upgrade subscription for tenant ' . $id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error upgrading subscription: ' . $e->getMessage();
        }
        $this->redirect('/tenants/' . $id);
    }

    /**
     * Handles downgrading a tenant's subscription.
     * @param int $id The ID of the tenant.
     */
    public function downgradeTenantSubscription(string $id): void
    {
        $id = (int) $id;
        $this->checkPermission('super:manage_subscriptions');

        $validator = new Validator($_POST);
        $validator->validate([
            'new_plan_id' => 'required|int',
            'amount_paid' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = 'Downgrade failed: ' . implode('<br>', $validator->errors());
            $this->redirect('/tenants/' . $id);
            return;
        }

        try {
            $newPlanId = $validator->get('new_plan_id', 'int');
            $amountPaid = $validator->get('amount_paid', 'float');
            $success = $this->subscriptionModel->downgradeSubscription($id, $newPlanId, $amountPaid);

            if ($success) {
                $_SESSION['flash_success'] = "Subscription for tenant {$id} downgraded successfully.";
                $this->auditModel->log(1, 'SUBSCRIPTION_DOWNGRADED', ['tenant_id' => $id, 'new_plan_id' => $newPlanId, 'amount_paid' => $amountPaid]);
            } else {
                $_SESSION['flash_error'] = "Failed to downgrade subscription for tenant {$id}.";
            }
        } catch (\Throwable $e) {
            Log::error('Failed to downgrade subscription for tenant ' . $id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error downgrading subscription: ' . $e->getMessage();
        }
        $this->redirect('/tenants/' . $id);
    }

    /**
     * Allows a Super Admin to impersonate a Tenant Admin.
     * @param int $tenantId The ID of the tenant whose admin will be impersonated.
     */
    public function impersonateTenantAdmin(string $tenantId): void
    {
        $tenantId = (int) $tenantId;
        $this->checkPermission('super:impersonate_tenant_admin'); // Ensure Super Admin has permission

        try {
            // Find the Tenant Admin user for this tenant
            $tenantAdmin = $this->userModel->findTenantAdminUser($tenantId);

            if (!$tenantAdmin) {
                $_SESSION['flash_error'] = "Tenant Admin not found for tenant ID {$tenantId}.";
                $this->redirect('/tenants/' . $tenantId);
                return;
            }

            $auth = Auth::getInstance();
            if ($auth->startImpersonation((int)$tenantAdmin['id'])) {
                $_SESSION['flash_success'] = "Successfully impersonating Tenant Admin '{$tenantAdmin['first_name']} {$tenantAdmin['last_name']}'.";
                // Redirect to the tenant's dashboard after impersonation
                $this->redirect('/dashboard'); 
            } else {
                $_SESSION['flash_error'] = "Failed to start impersonation.";
                $this->redirect('/tenants/' . $tenantId);
            }
        } catch (\Throwable $e) {
            Log::error("Failed to impersonate tenant admin for tenant ID {$tenantId}: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Error starting impersonation: ' . $e->getMessage();
            $this->redirect('/tenants/' . $tenantId);
        }
    }

    /**
     * Display system-wide reports for Super Admin.
     */
    public function reports(): void
    {
        try {
            $this->view('superadmin/reports/index', [
                'title' => 'System Reports',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to load system reports page: ' . $e->getMessage());
            ErrorResponder::respond(500, 'Could not load system reports.');
        }
    }
}
