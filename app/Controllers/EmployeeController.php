<?php 
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Models\EmployeeModel;
use Jeffrey\Sikapay\Models\DepartmentModel;
use Jeffrey\Sikapay\Models\PositionModel;
use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder; 
use Jeffrey\Sikapay\Core\Validator;
use Jeffrey\Sikapay\Security\CsrfToken; // Added
use Jeffrey\Sikapay\Core\Auth; // ADDED THIS LINE
// use Jeffrey\Sikapay\Core\Router; // <-- REMOVED as redundant based on base Controller::redirect() implementation
use \Throwable;

// Lazy-loaded models/services used in specific methods
use Jeffrey\Sikapay\Models\UserModel; 
use Jeffrey\Sikapay\Models\UserProfileModel; 
use Jeffrey\Sikapay\Models\AuditModel; 
use Jeffrey\Sikapay\Models\RoleModel; 
use Jeffrey\Sikapay\Models\EmploymentHistoryModel; // Added for updateSalary()
use Jeffrey\Sikapay\Services\SubscriptionService;
use Jeffrey\Sikapay\Models\StaffFileModel;
use Jeffrey\Sikapay\Helpers\FileUploader;
use Jeffrey\Sikapay\Models\CustomPayrollElementModel;
use Jeffrey\Sikapay\Models\EmployeePayrollDetailsModel;
use Jeffrey\Sikapay\Models\PermissionModel;
use Jeffrey\Sikapay\Models\UserPermissionModel;
use Jeffrey\Sikapay\Models\PayslipModel;


class EmployeeController extends Controller
{
    private EmployeeModel $employeeModel;
    private DepartmentModel $departmentModel;
    private PositionModel $positionModel;
    private CustomPayrollElementModel $customPayrollElementModel;
    private EmployeePayrollDetailsModel $employeePayrollDetailsModel;
    private RoleModel $roleModel;
    private PermissionModel $permissionModel;
    private UserPermissionModel $userPermissionModel;
    private PayslipModel $payslipModel;
    
    // Permission Constants 
    public const PERM_EDIT_PERSONAL = 'employee:update';
    public const PERM_EDIT_STATUTORY = 'employee:update';
    public const PERM_EDIT_BANK = 'employee:update';
    public const PERM_EDIT_EMPLOYMENT = 'employee:update';
    public const PERM_EDIT_SALARY = 'employee:update';
    public const PERM_EDIT_ROLES = 'employee:update';
    public const PERM_DELETE = 'employee:delete';
    
    public function __construct()
    {
        parent::__construct();
        
        try {
            $this->employeeModel = new EmployeeModel();
            $this->departmentModel = new DepartmentModel();
            $this->positionModel = new PositionModel();
            $this->customPayrollElementModel = new CustomPayrollElementModel();
            $this->employeePayrollDetailsModel = new EmployeePayrollDetailsModel();
            $this->roleModel = new RoleModel();
            $this->permissionModel = new PermissionModel();
            $this->userPermissionModel = new UserPermissionModel();
            $this->payslipModel = new PayslipModel();
            
        } catch (Throwable $e) {
            Log::critical("EmployeeController failed to initialize core models: " . $e->getMessage());
            ErrorResponder::respond(500, "A critical system error occurred during controller initialization.");
        }
    }

// ------------------------------------------------------------------
// VIEW METHODS
// ------------------------------------------------------------------

    /**
     * Display the list of employees for the current tenant.
     */
    public function index(): void
    {
        try {
            // FIX: Use the specific, seeded permission for the directory list view.
            // Assuming 'employee:read_all' is the correct permission for viewing all employees.
            // If the seeded permission is 'employee:view', revert this.
            // $this->checkPermission('employee:read_all'); 
            
            $employees = $this->employeeModel->getAllEmployees();
            
            $this->view('employee/index', [
                'title' => 'Employee Directory',
                'employees' => $employees,
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to load employee directory for Tenant {$this->tenantId}: " . $e->getMessage());
            // It's better to use a permission-denied error (403) here if it's a permission check failure
            ErrorResponder::respond(500, "Could not load the employee directory due to a system error.");
        }
    }

    /**
     * Display the list of active employees for the current tenant.
     */
    public function activeStaff(): void
    {
        try {
            $employees = $this->employeeModel->getActiveEmployees();
            
            $this->view('employee/active', [
                'title' => 'Active Employees',
                'employees' => $employees,
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to load active employee directory for Tenant {$this->tenantId}: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load the active employee directory due to a system error.");
        }
    }

    /**
     * Display the list of inactive employees for the current tenant.
     */
    public function inactiveStaff(): void
    {
        try {
            $employees = $this->employeeModel->getInactiveEmployees();
            
            $this->view('employee/inactive', [
                'title' => 'Inactive Employees',
                'employees' => $employees,
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to load inactive employee directory for Tenant {$this->tenantId}: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load the inactive employee directory due to a system error.");
        }
    }

    /**
     * Display the form to create a new employee.
     */
    public function create(): void
    {
        try {
            $this->checkPermission('employee:create');
            // Assuming DepartmentModel and PositionModel exist and have an 'all' method
            $departments = $this->departmentModel->all();
            $positions = $this->positionModel->all(); 
            
            $this->view('employee/create', [
                'title' => 'Add New Employee',
                'departments' => $departments,
                'positions' => $positions,
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to load employee creation form for Tenant {$this->tenantId}: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load required data for employee creation.");
        }
    }

    /**
     * Display a single employee's profile.
     */
    public function show(int $userId): void
    {
        try {
            // No permission check here, the model handles tenant scoping
            $employee = $this->employeeModel->getEmployeeProfile($userId); 

            if (!$employee) {
                http_response_code(404);
                $this->view('error/404', ['title' => 'Employee Not Found']);
                return;
            }

            $staffFileModel = new StaffFileModel();
            $staffFiles = $staffFileModel->getFilesByUserId($userId);

            $availablePayrollElements = $this->customPayrollElementModel->getAllByTenant($this->tenantId);
            $assignedPayrollElements = $this->employeePayrollDetailsModel->getDetailsForEmployee($userId, $this->tenantId);

            // Calculate gross salary
            $basicSalary = (float) $employee['current_salary_ghs'];
            $totalAllowances = 0.0;

            foreach ($assignedPayrollElements as $element) {
                if ($element['category'] === 'allowance') {
                    if ($element['amount_type'] === 'fixed') {
                        $totalAllowances += (float) $element['assigned_amount'];
                    } elseif ($element['amount_type'] === 'percentage' && $element['calculation_base'] === 'basic_salary') {
                        $totalAllowances += $basicSalary * ((float) $element['assigned_amount'] / 100.0);
                    }
                }
            }
            $employee['calculated_gross_salary'] = $basicSalary + $totalAllowances;


            $this->view('employee/show', [
                'title' => 'Employee Profile: ' . $employee['first_name'] . ' ' . $employee['last_name'],
                'employee' => $employee,
                'staffFiles' => $staffFiles,
                'availablePayrollElements' => $availablePayrollElements,
                'assignedPayrollElements' => $assignedPayrollElements,
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to load employee profile (show) for User {$userId}: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load the employee profile due to a system error.");
        }
    }

    /**
     * Display the form to edit an existing employee's profile.
     */
    public function edit(int $userId): void
    {
        try {
            $this->checkPermission('employee:update'); // Generic edit permission for access
            
            $employee = $this->employeeModel->getEmployeeProfile($userId); 

            if (!$employee) {
                http_response_code(404);
                $this->view('error/404', ['title' => 'Employee Not Found']);
                return;
            }
            
            $departments = $this->departmentModel->all();
            $currentDepartmentId = $employee['department_id'] ?? 0;
            
            // Assume PositionModel has a method to fetch positions, possibly with filtering
            $positions = $currentDepartmentId > 0 
                ? $this->positionModel->all(['department_id' => $currentDepartmentId]) 
                : $this->positionModel->all(); 
            
            $roles = $this->roleModel->all(); // Fetch all roles
            $allPermissions = $this->permissionModel->all(); // Fetch all system permissions
            $rolePermissions = $this->roleModel->getPermissionsForRole($employee['role_id']); // Permissions inherited from role
            $individualPermissions = $this->userPermissionModel->getPermissionsForUser($userId); // Individual user overrides

            // --- START NEW LOGIC FOR ROLE AND PERMISSION FILTERING ---
            // Filter roles: Remove 'super_admin' role if the current user is not a super admin
            if (!$this->auth->isSuperAdmin()) {
                $roles = array_filter($roles, fn($role) => $role['name'] !== 'super_admin');
                // Re-index the array after filtering
                $roles = array_values($roles);
            }

            // Filter permissions: Remove 'super:' permissions if the current user is not a super admin
            if (!$this->auth->isSuperAdmin()) {
                $allPermissions = array_filter($allPermissions, fn($permission) => !str_starts_with($permission['key_name'], 'super:'));
                // Re-index the array after filtering
                $allPermissions = array_values($allPermissions);
            }
            // --- END NEW LOGIC FOR ROLE AND PERMISSION FILTERING ---
                
            $this->view('employee/edit', [
                'title' => 'Edit Employee: ' . $employee['first_name'] . ' ' . $employee['last_name'],
                'employee' => $employee,
                'departments' => $departments,
                'positions' => $positions,
                'currentDepartmentId' => $currentDepartmentId,
                'roles' => $roles, // Pass roles to the view
                'allPermissions' => $allPermissions,
                'rolePermissions' => array_column($rolePermissions, 'id'), // Just IDs for easy checking
                'individualPermissions' => $individualPermissions, // Pass the associative array directly
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to load employee profile (edit) for User {$userId}: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load the employee edit form due to a system error.");
        }
    }

// ------------------------------------------------------------------
// DML/ACTION METHODS - RETAINS FULL LOGIC FROM PREVIOUS STEP
// ------------------------------------------------------------------

    /**
     * Handles the creation of a new employee (User, Profile, Employee records) in a single transaction.
     */  
    public function store(): void
    {
        $this->checkPermission('employee:create');
        
        // 1. Validation and Sanitization
        $validator = new Validator($_POST);
        $validator->validate([
            'first_name' => 'required|min:2',
            'last_name' => 'required|min:2',
            'email' => 'required|email',
            'employee_id' => 'required|min:3', 
            'hire_date' => 'required|date', 
            'current_position_id' => 'required|int', 
            'gender' => 'required|min:1', 
            'monthly_base_salary' => 'required|numeric', 
            'phone' => 'optional|min:8',
            'date_of_birth' => 'required|date', 
            'emergency_contact_name' => 'required|min:3',
            'emergency_contact_phone' => 'required|min:8',
            'ssnit_number' => 'optional|max:50',
            'tin_number' => 'optional|max:50',
            'bank_name' => 'optional|max:100',
            'bank_branch' => 'optional|max:100',
            'bank_account_name' => 'optional|max:100',
            'bank_account_number' => 'optional|max:50',
        ]);

        if ($validator->fails()) {
            $errors = implode('<br>', $validator->errors());
            $_SESSION['flash_error'] = "Employee creation failed due to invalid input: <br>{$errors}";
            $_SESSION['flash_input'] = $validator->all(); 
            $this->redirect('/employees/create');
            return;
        }

        // --- START FIX: Database Uniqueness Check (SSNIT/TIN/Employee ID) ---
        $ssnit = $validator->get('ssnit_number', 'string', null);
        $tin = $validator->get('tin_number', 'string', null);
        $customErrors = [];

        // Check SSNIT uniqueness
        if (!empty($ssnit) && $this->employeeModel->isComplianceNumberInUse($ssnit, 'ssnit_number')) {
            $customErrors[] = "The **SSNIT number** '{$ssnit}' is already registered to another user.";
        }

        // Check TIN uniqueness
        if (!empty($tin) && $this->employeeModel->isComplianceNumberInUse($tin, 'tin_number')) {
            $customErrors[] = "The **TIN number** '{$tin}' is already registered to another user.";
        }
        
        // Check Employee ID uniqueness within the current tenant (FIXED to use the new method)
        $employeeId = $validator->get('employee_id');
        if ($this->employeeModel->isEmployeeIdInUse($employeeId)) { // <--- UPDATED METHOD CALL
            $customErrors[] = "The **Employee ID** '{$employeeId}' is already assigned within this tenant.";
        }

        // If custom errors exist, stop and redirect. This prevents the SQL error.
        if (!empty($customErrors)) {
            $errors = implode('<br>', $customErrors);
            $_SESSION['flash_error'] = "Employee creation failed due to duplicate data: <br>{$errors}";
            $_SESSION['flash_input'] = $validator->all(); 
            $this->redirect('/employees/create');
            return;
        }
        // --- END FIX: Database Uniqueness Check ---

        // 2. FEATURE GATING: Check Employee Limit (Lazy Load SubscriptionService)
        try {
            $subscriptionService = new SubscriptionService(); 
            $limit = $subscriptionService->getFeatureLimit($this->tenantId, 'employee_limit');
            $currentCount = $this->employeeModel->getEmployeeCount($this->tenantId); 
            
            if ($currentCount >= $limit) {
                $planName = $subscriptionService->getCurrentPlanName($this->tenantId);
                throw new \Exception("Employee creation limit reached. Your current {$planName} Plan allows a maximum of {$limit} employees. Please upgrade your subscription.");
            }
        } catch (Throwable $e) {
            Log::error("Employee Limit Check Failed for Tenant {$this->tenantId}: " . $e->getMessage());
            $_SESSION['flash_error'] = "Limit Check Error: " . $e->getMessage();
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/employees/create');
            return;
        }

        // 3. Start Transaction and Lazy Load Transaction-Dependent Models
        $db = $this->employeeModel->getDB(); 
        $newUserId = null; 

        try {
            $db->beginTransaction();

            $roleModel = new RoleModel();
            $userModel = new UserModel();
            $userProfileModel = new UserProfileModel();
            $auditModel = new AuditModel();
            
            $employeeRoleId = $roleModel->findIdByName('employee');
            
            if ($employeeRoleId === null) {
                throw new \Exception("Configuration Error: 'employee' role not found in database. Cannot create user.");
            }
            
            // 4. Create User Record (users)
            $userData = [
                'email' => $validator->get('email'),
                'password' => password_hash('default123', PASSWORD_DEFAULT), 
                'first_name' => $validator->get('first_name'),
                'last_name' => $validator->get('last_name'),
                'other_name' => $validator->get('other_name', 'string', null),
                'phone' => $validator->get('phone', 'string', null),
                'role_id' => $employeeRoleId, 
            ];
            $newUserId = $userModel->createUser($this->tenantId, $userData); 
            if (!$newUserId) {
                throw new \Exception("Failed to retrieve new user ID after creation.");
            }

            // 5. Create User Profile Record (user_profiles)
            $profileData = [
                'user_id' => $newUserId,
                'gender' => $validator->get('gender'),
                'date_of_birth' => $validator->get('date_of_birth'),
                'nationality' => $validator->get('nationality', 'string', 'Ghanaian'),
                'marital_status' => $validator->get('marital_status', 'string', 'Single'),
                'home_address' => $validator->get('home_address', 'string', null),
                'ssnit_number' => $ssnit,
                'tin_number' => $tin,
                'id_card_type' => $validator->get('id_card_type', 'string', 'Ghana Card'),
                'id_card_number' => $validator->get('id_card_number', 'string', null),
                'emergency_contact_name' => $validator->get('emergency_contact_name'),
                'emergency_contact_phone' => $validator->get('emergency_contact_phone'),
            ];
            if (!$userProfileModel->createProfile($profileData)) {
                throw new \Exception("Failed to create user profile.");
            }
            
            // 6. Create Employee Record (employees)
            $employeeData = [
                'user_id' => $newUserId,
                'tenant_id' => $this->tenantId,
                'employee_id' => $employeeId,
                'current_position_id' => $validator->get('current_position_id', 'int'),
                'hire_date' => $validator->get('hire_date'),
                'employment_type' => $validator->get('employment_type', 'string', 'Full-Time'),
                'current_salary_ghs' => $validator->get('monthly_base_salary', 'float'),
                'payment_method' => $validator->get('payment_method', 'string', 'Bank Transfer'),
                'bank_name' => $validator->get('bank_name', 'string', null),
                'bank_branch' => $validator->get('bank_branch', 'string', null),
                'bank_account_name' => $validator->get('bank_account_name', 'string', null),
                'bank_account_number' => $validator->get('bank_account_number', 'string', null),
                'is_payroll_eligible' => $validator->get('is_payroll_eligible', 'int', 1), // Bool as int
            ];
            if (!$this->employeeModel->createEmployeeRecord($employeeData)) {
                throw new \Exception("Failed to create employee record.");
            }
            
            // 7. Audit Logging 
            $auditModel->log(
                $this->tenantId, 
                'New Employee Added: ' . $userData['first_name'] . ' ' . $userData['last_name'],
                ['employee_id' => $employeeData['employee_id'], 'user_id' => $newUserId]
            );

            // 8. Commit Transaction
            $db->commit();
            
            // 9. Success Handling
            $_SESSION['flash_success'] = "Employee " . $userData['first_name'] . " saved successfully. Default password is 'default123'.";
            $this->redirect("/employees/{$newUserId}"); 

        } catch (Throwable $e) { 
            // 10. Failure: Rollback
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Log::critical("Employee Creation Transaction Failed for Tenant {$this->tenantId}: " . $e->getMessage(), [
                'user_id' => $this->userId, 
                'email' => $validator->get('email') ?? 'N/A', 
                'line' => $e->getLine()
            ]);
            
            // The error handling for Integrity Constraint Violation is now much cleaner
            $errorMsg = "Error creating employee: A critical system error occurred. Please try again.";
            // This catch block is primarily for other errors (e.g., config error, limit check failure, DB connection).
            // The SSNIT/TIN/Employee ID errors are now caught before the transaction starts.
            if (strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
                $errorMsg = "Error creating employee: A unique record (e.g., Email or Employee ID) is already in use.";
            }
            
            $_SESSION['flash_error'] = $errorMsg;
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/employees/create');
        }
    }


    /**
     * Handles the PUT request to update the employee's core **Employment Data** (Position, Hire Date, ID, Type).
     */
    public function updateEmploymentData(int $userId): void
    {
        header('Content-Type: application/json');
        
        // Security check: Must belong to tenant
        if (!$this->employeeModel->isEmployeeInTenant($userId, $this->tenantId)) {
            ErrorResponder::respond(404, "The specified employee was not found.");
            return;
        }
        
        // 1. Validation and Sanitization
        $validator = new Validator($_POST);
        $validator->validate([
            'employee_id'           => 'required|min:3|max:50', 
            'hire_date'             => 'required|date', 
            'current_position_id'   => 'required|int', 
            'employment_type'       => 'required|in:Full-Time,Part-Time,Contract,Intern',
            'termination_date'      => 'optional|date', 
        ]);
        
        if ($validator->fails()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Employment data update failed: " . implode('<br>', $validator->errors())]);
            exit;
        }

        $db = $this->employeeModel->getDB(); 

        try {
            $db->beginTransaction();

            $auditModel = new AuditModel();

            // --- Update Employee Record (employees) ---
            $employeeData = [
                'employee_id' => $validator->get('employee_id'),
                'current_position_id' => $validator->get('current_position_id', 'int'), 
                'hire_date' => $validator->get('hire_date'),
                'employment_type' => $validator->get('employment_type'),
                'termination_date' => $validator->get('termination_date', 'date', null), 
            ];
            
            $this->employeeModel->updateEmployeeRecord($userId, $employeeData);
            
            $auditModel->log($this->tenantId, 'Employee employment data updated for user with ID: ' . $userId, ['user_id' => $userId]);

            $db->commit();
            
            echo json_encode(['success' => true, 'message' => "Employment details updated successfully. 👷"]);
            exit;

        } catch (Throwable $e) { 
            if ($db->inTransaction()) { $db->rollBack(); }
            Log::critical("Employment Data Update Failed for User {$userId}: " . $e->getMessage());
            $errorMsg = (strpos($e->getMessage(), 'Integrity constraint violation') !== false)
                ? 'Error: Employee ID may already be in use.'
                : 'Error updating employment data: A critical system error occurred.';
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $errorMsg]);
            exit;
        }
    }
    
// ------------------------------------------------------------------

    /**
     * Handles the PUT request to update the employee's **Personal Information** (Name, Phone, Address, DOB, Gender).
     */
    public function updatePersonalData(int $userId): void
    {
        header('Content-Type: application/json');
        
        if (!$this->employeeModel->isEmployeeInTenant($userId, $this->tenantId)) {
            ErrorResponder::respond(404, "The specified employee was not found.");
            return;
        }
        
        $validator = new Validator($_POST);
        $validator->validate([
            'first_name'    => 'required|min:2|max:100',
            'last_name'     => 'required|min:2|max:100',
            'other_name'    => 'optional|max:100',
            'phone'         => 'optional|max:50',
            'gender'        => 'required|in:Male,Female,Other',
            'date_of_birth' => 'required|date',
            'marital_status'=> 'required|in:Single,Married,Divorced,Widowed',
            'nationality'   => 'required|max:100',
            'home_address'  => 'optional|max:500',
        ]);
        
        if ($validator->fails()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Personal data update failed: " . implode('<br>', $validator->errors())]);
            exit;
        }

        $db = $this->employeeModel->getDB(); 

        try {
            $db->beginTransaction();

            $userModel = new UserModel();
            $userProfileModel = new UserProfileModel();
            $auditModel = new AuditModel();

            // --- 1. Update User Record (users) ---
            $userData = [
                'first_name' => $validator->get('first_name'),
                'last_name' => $validator->get('last_name'),
                'other_name' => $validator->get('other_name', 'string', null),
                'phone' => $validator->get('phone', 'string', null),
            ];
            $userModel->updateUser($userId, $userData); 

            // --- 2. Update User Profile Record (user_profiles) ---
            $profileData = [
                'gender' => $validator->get('gender'),
                'date_of_birth' => $validator->get('date_of_birth'),
                'marital_status' => $validator->get('marital_status'),
                'nationality' => $validator->get('nationality'),
                'home_address' => $validator->get('home_address', 'string', null),
            ];
            $userProfileModel->updateProfile($userId, $profileData); 
            
            $auditModel->log($this->tenantId, 'Employee personal data updated for user with ID: ' . $userId, ['user_id' => $userId]);

            $db->commit();
            
            echo json_encode(['success' => true, 'message' => "Personal details updated successfully. ✍️"]);
            exit;

        } catch (Throwable $e) { 
            if ($db->inTransaction()) { $db->rollBack(); }
            Log::critical("Personal Data Update Failed for User {$userId}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => "Error updating personal data: A critical system error occurred."]);
            exit;
        }
    }
    
// ------------------------------------------------------------------

    /**
     * Handles the PUT request to update the employee's **Statutory Information** (TIN, SSNIT, ID Card).
     */
    public function updateStatutoryData(int $userId): void
    {
        header('Content-Type: application/json');
        
        if (!$this->employeeModel->isEmployeeInTenant($userId, $this->tenantId)) {
            ErrorResponder::respond(404, "The specified employee was not found.");
            return;
        }

        $validator = new Validator($_POST);
        $validator->validate([
            'ssnit_number'      => 'optional|max:50',
            'tin_number'        => 'optional|max:50',
            'id_card_type'      => 'required|in:Ghana Card,Voter ID,Passport',
            'id_card_number'    => 'optional|max:100',
        ]);
        
        if ($validator->fails()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Statutory data update failed: " . implode('<br>', $validator->errors())]);
            exit;
        }

        $db = $this->employeeModel->getDB(); 
        try {
            $db->beginTransaction();

            $userProfileModel = new UserProfileModel();
            $auditModel = new AuditModel();

            // --- Update User Profile Record (user_profiles) ---
            $profileData = [
                'ssnit_number' => $validator->get('ssnit_number', 'string', null),
                'tin_number' => $validator->get('tin_number', 'string', null),
                'id_card_type' => $validator->get('id_card_type'),
                'id_card_number' => $validator->get('id_card_number', 'string', null),
            ];
            $userProfileModel->updateProfile($userId, $profileData); 
            
            $auditModel->log($this->tenantId, 'Employee statutory data updated for user with ID: ' . $userId, ['user_id' => $userId]);

            $db->commit();
            
            echo json_encode(['success' => true, 'message' => "Statutory details updated successfully."]);
            exit;

        } catch (Throwable $e) { 
            if ($db->inTransaction()) { $db->rollBack(); }
            Log::critical("Statutory Data Update Failed for User {$userId}: " . $e->getMessage());
            $errorMsg = (strpos($e->getMessage(), 'Integrity constraint violation') !== false)
                ? 'Error: TIN, SSNIT, or ID Card number may already be in use.'
                : 'Error updating statutory data: A critical system error occurred.';
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $errorMsg]);
            exit;
        }
    }
    
// ------------------------------------------------------------------

    /**
     * Handles the PUT request to update the employee's **Bank Information**.
     */
    public function updateBankData(int $userId): void
    {
        header('Content-Type: application/json');
        
        if (!$this->employeeModel->isEmployeeInTenant($userId, $this->tenantId)) {
            ErrorResponder::respond(404, "The specified employee was not found.");
            return;
        }

        $validator = new Validator($_POST);
        $validator->validate([
            'payment_method'        => 'required|in:Bank Transfer,Cash,Mobile Money',
            'bank_name'             => 'optional|max:100',
            'bank_branch'           => 'optional|max:100', // Added
            'bank_account_name'     => 'optional|max:100', // Added
            'bank_account_number'   => 'optional|max:50',
            'is_payroll_eligible'   => 'required|bool',
        ]);
        
        if ($validator->fails()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Bank data update failed: " . implode('<br>', $validator->errors())]);
            exit;
        }

        $db = $this->employeeModel->getDB(); 
        try {
            $db->beginTransaction();

            $auditModel = new AuditModel();
            
            // --- Update Employee Record (employees) ---
            $employeeData = [
                'payment_method' => $validator->get('payment_method'),
                'bank_name' => $validator->get('bank_name', 'string', null),
                'bank_branch' => $validator->get('bank_branch', 'string', null), // Added
                'bank_account_name' => $validator->get('bank_account_name', 'string', null), // Added
                'bank_account_number' => $validator->get('bank_account_number', 'string', null),
                'is_payroll_eligible' => $validator->get('is_payroll_eligible', 'int'), // Bool should be treated as int in DB
            ];
            
            $this->employeeModel->updateEmployeeRecord($userId, $employeeData);
            
            $auditModel->log($this->tenantId, 'Employee bank data updated for user with ID: ' . $userId, ['user_id' => $userId]);

            $db->commit();
            
            echo json_encode(['success' => true, 'message' => "Bank/Payment details updated successfully."]);
            exit;

        } catch (Throwable $e) { 
            if ($db->inTransaction()) { $db->rollBack(); }
            Log::critical("Bank Data Update Failed for User {$userId}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => "Error updating bank data: A critical system error occurred."]);
            exit;
        }
    }
    
// ------------------------------------------------------------------

    /**
     * Handles the PUT request to update the employee's **Emergency Contact Information**.
     */
    public function updateEmergencyContactData(int $userId): void
    {
        header('Content-Type: application/json');
        
        if (!$this->employeeModel->isEmployeeInTenant($userId, $this->tenantId)) {
            ErrorResponder::respond(404, "The specified employee was not found.");
            return;
        }

        $validator = new Validator($_POST);
        $validator->validate([
            'emergency_contact_name'    => 'required|max:255',
            'emergency_contact_phone'   => 'required|max:50',
        ]);
        
        if ($validator->fails()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Emergency contact update failed: " . implode('<br>', $validator->errors())]);
            exit;
        }

        $db = $this->employeeModel->getDB(); 
        try {
            $db->beginTransaction();

            $userProfileModel = new UserProfileModel();
            $auditModel = new AuditModel();

            // --- Update User Profile Record (user_profiles) ---
            $profileData = [
                'emergency_contact_name' => $validator->get('emergency_contact_name'),
                'emergency_contact_phone' => $validator->get('emergency_contact_phone'),
            ];
            $userProfileModel->updateProfile($userId, $profileData); 
            
            $auditModel->log($this->tenantId, 'Employee emergency contact data updated for user with ID: ' . $userId, ['user_id' => $userId]);

            $db->commit();
            
            echo json_encode(['success' => true, 'message' => "Emergency contact details updated successfully."]);
            exit;

        } catch (Throwable $e) { 
            if ($db->inTransaction()) { $db->rollBack(); }
            Log::critical("Emergency Contact Update Failed for User {$userId}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => "Error updating emergency contact: A critical system error occurred."]);
            exit;
        }
    }
    
// ------------------------------------------------------------------
    
    /**
     * Handles updating the monthly base salary and logs the change to employment_history.
     */
    public function updateSalary(int $userId): void
    {
        header('Content-Type: application/json');
        $this->checkPermission(self::PERM_EDIT_SALARY);
        
        if (!$this->employeeModel->isEmployeeInTenant($userId, $this->tenantId)) {
            ErrorResponder::respond(404, "The specified employee was not found.");
            return;
        }
        
        $validator = new Validator($_POST);
        $validator->validate([
            'new_salary'        => 'required|numeric|min:1',
            'effective_date'    => 'required|date',
            'notes'             => 'optional|max:500',
        ]);
        
        if ($validator->fails()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Salary update failed: " . implode('<br>', $validator->errors())]);
            exit;
        }
        
        $db = $this->employeeModel->getDB(); 
        try {
            $db->beginTransaction();

            $auditModel = new AuditModel();
            // Lazy load the necessary model for history logging
            $historyModel = new EmploymentHistoryModel(); 

            // 1. Fetch current salary for history logging
            $employee = $this->employeeModel->getEmployeeProfile($userId); 
            $oldSalary = $employee['current_salary_ghs'] ?? 0.00;
            $newSalary = $validator->get('new_salary', 'float');
            
            // 2. Update employee's current salary in the 'employees' table
            $this->employeeModel->updateEmployeeRecord($userId, ['current_salary_ghs' => $newSalary]);
            
            // 3. Log to employment_history table (Assuming create method exists)
            $historyModel->create([
                'user_id' => $userId,
                'tenant_id' => $this->tenantId,
                'effective_date' => $validator->get('effective_date'),
                'record_type' => 'Salary Change',
                'old_salary' => $oldSalary,
                'new_salary' => $newSalary,
                'notes' => $validator->get('notes', 'string', null),
            ]);
            
            $auditModel->log($this->tenantId, 'Employee salary updated for user with ID: ' . $userId, [
                'user_id' => $userId, 
                'old' => $oldSalary, 
                'new' => $newSalary
            ]);

            $db->commit();
            
            echo json_encode(['success' => true, 'message' => "Salary updated successfully! The change has been logged."]);
            exit;

        } catch (Throwable $e) { 
            if ($db->inTransaction()) { $db->rollBack(); }
            Log::critical("Salary Update Failed for User {$userId}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => "Error updating salary: A critical system error occurred."]);
            exit;
        }
    }
    
    /**
     * Handles the PUT request to update the employee's **Role and Permissions** (is_active, role_id).
     */
    public function updateRoleAndPermissions(int $userId): void
    {
        header('Content-Type: application/json');
        $this->checkPermission(self::PERM_EDIT_ROLES);
        
        if (!$this->employeeModel->isEmployeeInTenant($userId, $this->tenantId)) {
            ErrorResponder::respond(404, "The specified employee was not found.");
            return;
        }

        $validator = new Validator($_POST);
        $validator->validate([
            'role_id'       => 'required|int|min:1',
            'is_active'     => 'required|bool', 
        ]);
        
        if ($validator->fails()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Role update failed: " . implode('<br>', $validator->errors())]);
            exit;
        }

        $db = $this->employeeModel->getDB(); 
        try {
            $db->beginTransaction();

            $userModel = new UserModel();
            $auditModel = new AuditModel();
            
            // --- Update User Record (users) ---
            $userData = [
                'role_id' => $validator->get('role_id', 'int'),
                'is_active' => $validator->get('is_active', 'int'), 
            ];
            
            $userModel->updateUser($userId, $userData);
            
            $auditModel->log($this->tenantId, 'Employee role updated for user with ID: ' . $userId, ['user_id' => $userId, 'new_role_id' => $userData['role_id']]);

            $db->commit();
            
            echo json_encode(['success' => true, 'message' => "Role and account status updated successfully."]);
            exit;

        } catch (Throwable $e) { 
            if ($db->inTransaction()) { $db->rollBack(); }
            Log::critical("Role Update Failed for User {$userId}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => "Error updating role: A critical system error occurred."]);
            exit;
        }
    }

    /**
     * Handles the POST request to reset an employee's individual permissions to their role's defaults.
     */
    public function resetPermissionsToDefaults(int $userId): void
    {
        header('Content-Type: application/json');
        $this->checkPermission('tenant:configure_roles'); // Same permission as updating individual permissions
        
        if (!$this->employeeModel->isEmployeeInTenant($userId, $this->tenantId)) {
            ErrorResponder::respond(404, "The specified employee was not found.");
            return;
        }

        // Validate CSRF token from POST data
        if (!isset($_POST['csrf_token']) || !CsrfToken::validate($_POST['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'CSRF token mismatch. Please refresh and try again.']);
            exit;
        }

        $db = $this->employeeModel->getDB(); 
        try {
            $db->beginTransaction();

            $auditModel = new AuditModel();
            
            // Delete all existing individual permissions for this user
            $this->userPermissionModel->deletePermissionsForUser($userId);
            
            $auditModel->log($this->tenantId, 'Employee individual permissions reset to role defaults for user with ID: ' . $userId, ['user_id' => $userId]);

            $db->commit();
            
            echo json_encode(['success' => true, 'message' => "Individual permissions reset to role defaults successfully."]);
            exit;

        } catch (Throwable $e) { 
            if ($db->inTransaction()) { $db->rollBack(); }
            Log::critical("Reset Individual Permissions Failed for User {$userId}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => "Error resetting individual permissions: A critical system error occurred."]);
            exit;
        }
    }

    /**
     * Handles the POST request to toggle an employee's individual permission.
     * This method adds, updates, or deletes an individual permission override.
     */
    public function toggleIndividualPermission(int $userId, int $permissionId): void
    {
        header('Content-Type: application/json');
        $this->checkPermission('tenant:configure_roles');
        
        if (!$this->employeeModel->isEmployeeInTenant($userId, $this->tenantId)) {
            ErrorResponder::respond(404, "The specified employee was not found.");
            return;
        }

        // Validate CSRF token from POST data
        if (!isset($_POST['csrf_token']) || !CsrfToken::validate($_POST['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'CSRF token mismatch. Please refresh and try again.']);
            exit;
        }

        $validator = new Validator($_POST);
        $validator->validate([
            'is_allowed' => 'required|bool',
            'is_role_default' => 'required|bool', // Indicates if the current state matches the role default
        ]);

        if ($validator->fails()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Invalid input: " . implode('<br>', $validator->errors())]);
            exit;
        }

        $isAllowed = (bool)$validator->get('is_allowed');
        $isRoleDefault = (bool)$validator->get('is_role_default');

        $db = $this->employeeModel->getDB(); 
        try {
            $db->beginTransaction();

            $auditModel = new AuditModel();
            $action = '';
            $logMessage = '';

            // Get the employee's current role permissions to determine if the change is an override or a revert
            $employee = $this->employeeModel->getEmployeeProfile($userId);
            $rolePermissions = $this->roleModel->getPermissionsForRole($employee['role_id']);
            $rolePermissionIds = array_column($rolePermissions, 'id');
            $isCurrentlyAllowedByRole = in_array($permissionId, $rolePermissionIds);

            // Case 1: Setting to role default (delete override)
            if ($isAllowed === $isCurrentlyAllowedByRole) {
                $this->userPermissionModel->deletePermissionForUser($userId, $permissionId);
                $action = 'reverted to role default';
                $logMessage = "Employee permission (ID: {$permissionId}) reverted to role default for user ID: {$userId}.";
            } 
            // Case 2: Setting an explicit override (add/update override)
            else {
                $this->userPermissionModel->addPermissionToUser($userId, $permissionId, $isAllowed);
                $action = $isAllowed ? 'granted' : 'explicitly denied';
                $logMessage = "Employee permission (ID: {$permissionId}) {$action} for user ID: {$userId}.";
            }
            
            $auditModel->log($this->tenantId, $logMessage, ['user_id' => $userId, 'permission_id' => $permissionId, 'is_allowed' => $isAllowed, 'action' => $action]);

            $db->commit();
            
            echo json_encode(['success' => true, 'message' => "Permission {$action} successfully."]);
            exit;

        } catch (Throwable $e) { 
            if ($db->inTransaction()) { $db->rollBack(); }
            Log::critical("Toggle Individual Permission Failed for User {$userId}, Permission {$permissionId}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => "Error toggling individual permission: A critical system error occurred."]);
            exit;
        }
    }

    /**
     * Display the employee self-service portal for the currently logged-in user.
     */
    public function myAccountIndex(): void
    {
        try {
            // The permission 'self:view_profile' is checked by the middleware.
            // Get the currently authenticated user's ID
            $currentUserId = Auth::userId(); // Corrected: Use static method

            // Fetch the employee profile for the current user
            $employee = $this->employeeModel->getEmployeeProfile($currentUserId);

            if (!$employee) {
                // If no employee profile exists, show a form to create one for the current user
                Log::info("Authenticated user {$currentUserId} does not have an employee profile. Redirecting to create form.");
                
                // Fetch user data to pre-populate the form
                $userModel = new UserModel();
                $currentUser = $userModel->find($currentUserId);

                if (!$currentUser) {
                    Log::critical("Authenticated user {$currentUserId} not found in users table.");
                    ErrorResponder::respond(500, "Critical error: Your user account could not be retrieved.");
                    return;
                }

                $departments = $this->departmentModel->all();
                $positions = $this->positionModel->all();

                $this->view('employee/my_account/create_employee_profile', [
                    'title' => 'Create My Employee Profile',
                    'currentUser' => $currentUser, // Pass user data to pre-populate
                    'departments' => $departments,
                    'positions' => $positions,
                ]);
                return; // Important: stop execution after rendering the form
            }

            // If employee profile exists, render the self-service portal view
            $payslips = $this->payslipModel->getPayslipsByUserId($currentUserId, $this->tenantId);
            $assignedPayrollElements = $this->employeePayrollDetailsModel->getDetailsForEmployee($currentUserId, $this->tenantId);

            $this->view('employee/my_account/index', [
                'title' => 'My Account',
                'employee' => $employee,
                'payslips' => $payslips,
                'assignedPayrollElements' => $assignedPayrollElements,
            ]);

        } catch (Throwable $e) {
            // Also correct this log message to use Auth::userId()
            Log::error("Failed to load My Account page for User " . Auth::userId() . ": " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load your account details due to a system error.");
        }
    }

    /**
     * DELETE the employee record (Soft Delete/Deactivate).
     */
    public function delete(int $userId): void
    {
        $this->checkPermission(self::PERM_DELETE); 
        
        if (!$this->employeeModel->isEmployeeInTenant($userId, $this->tenantId)) {
            ErrorResponder::respond(404, "The specified employee was not found in your directory.");
            return;
        }

        $db = $this->employeeModel->getDB();
        try {
            $db->beginTransaction();

            $userModel = new UserModel();
            $auditModel = new AuditModel();

            // Soft Delete: Set is_active=0 in the users table
            $userModel->updateUser($userId, ['is_active' => 0]); 
            
            $auditModel->log($this->tenantId, 'EMPLOYEE_DEACTIVATED', ['user_id' => $userId, 'details' => 'Employee deactivated by manager.']);

            $db->commit();
            
            $_SESSION['flash_success'] = "Employee has been successfully deactivated";
            $this->redirect("/employees");
            
        } catch (Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            Log::critical("Employee Deactivation/Delete Failed for User {$userId}: " . $e->getMessage());
            $_SESSION['flash_error'] = "Error deleting employee: A critical system error occurred.";
            $this->redirect("/employees");
        }
    }
    
// ------------------------------------------------------------------
// API ENDPOINTS 
// ------------------------------------------------------------------

    /**
     * API endpoint to fetch positions based on the selected department ID.
     */
    public function getPositionsByDepartment(): void
    {
        if (!$this->auth->check()) {
            header('Content-Type: application/json');
            http_response_code(401); 
            echo json_encode(['error' => 'Authentication required.']);
            exit; 
        }

        try {
            $validator = new Validator($_GET); 
            $validator->validate(['department_id' => 'required|int']);

            if ($validator->fails()) {
                Log::error("API Error: Invalid department ID received.", ['input' => $_GET]);
                header('Content-Type: application/json');
                http_response_code(400); 
                echo json_encode(['error' => 'Invalid or missing department ID parameter.']);
                exit;
            }
            
            $departmentId = $validator->get('department_id', 'int'); 
            $positions = $this->positionModel->all(['department_id' => $departmentId]); 

            header('Content-Type: application/json');
            echo json_encode(['positions' => $positions]);
            
            exit; 
        } catch (Throwable $e) {
            Log::error("API Error: Failed to fetch positions by department for Tenant {$this->tenantId}.", [
                'department_id' => $departmentId ?? 'N/A',
                'error' => $e->getMessage()
            ]);

            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error fetching position data.']);
            exit;
        }
    }



    public function updateProfileImage(int $userId): void
    {
        $this->checkPermission('employee:update');

        if (!$this->employeeModel->isEmployeeInTenant($userId, $this->tenantId)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'The specified employee was not found.']);
            return;
        }

        try {
            $userProfileModel = new UserProfileModel();
            $newImageUrl = (new FileUploader())->upload($_FILES['profile_image'], 'assets/images/profiles', ['jpg', 'jpeg', 'png'], 2 * 1024 * 1024);
            $userProfileModel->updateProfileImage($userId, $newImageUrl);

            echo json_encode(['success' => true, 'message' => 'Profile image updated successfully.', 'imageUrl' => $newImageUrl]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Profile image update failed: ' . $e->getMessage()]);
        }
    }

    

    public function uploadStaffFile(int $userId): void
    {
        $this->checkPermission('employee:update');

        if (!$this->employeeModel->isEmployeeInTenant($userId, $this->tenantId)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'The specified employee was not found.']);
            return;
        }

        try {
            $staffFileModel = new StaffFileModel();
            $filePath = (new FileUploader())->upload($_FILES['staff_file'], 'assets/files/staff', ['pdf', 'doc', 'docx', 'jpg', 'png'], 5 * 1024 * 1024);

            $fileId = $staffFileModel->createFileRecord([
                'user_id' => $userId,
                'file_name' => $_FILES['staff_file']['name'],
                'file_path' => $filePath,
                'file_type' => $_POST['file_type'],
                'file_description' => $_POST['file_description'],
            ]);

            $file = $staffFileModel->find($fileId);

            echo json_encode(['success' => true, 'message' => 'Staff file uploaded successfully.', 'file' => $file]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Staff file upload failed: ' . $e->getMessage()]);
        }
    }

    public function deleteStaffFile(int $userId, int $fileId): void
    {
        $this->checkPermission('employee:update');

        if (!$this->employeeModel->isEmployeeInTenant($userId, $this->tenantId)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'The specified employee was not found.']);
            return;
        }

        try {
            $staffFileModel = new StaffFileModel();
            $success = $staffFileModel->deleteFile($fileId, $this->tenantId);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Staff file deleted successfully.']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Failed to delete staff file.']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the file.']);
        }
    }

    public function assignPayrollElement(int $userId): void
    {
        $this->checkPermission('employee:assign_payroll_elements');

        if (!$this->employeeModel->isEmployeeInTenant($userId, $this->tenantId)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'The specified employee was not found.']);
            return;
        }

        $validator = new Validator($_POST);
        $validator->validate([
            'payroll_element_id' => 'required|int|min:1',
            'assigned_amount' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
            'end_date' => 'optional|date',
        ]);

        if ($validator->fails()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Validation failed: ' . implode(', ', $validator->errors())]);
            return;
        }

        try {
            $data = [
                'user_id' => $userId,
                'tenant_id' => $this->tenantId,
                'payroll_element_id' => $validator->get('payroll_element_id', 'int'),
                'assigned_amount' => $validator->get('assigned_amount', 'float'),
                'effective_date' => $validator->get('effective_date'),
                'end_date' => $validator->get('end_date', 'string', null),
            ];

            $success = $this->employeePayrollDetailsModel->create($data);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Payroll element assigned successfully.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to assign payroll element.']);
            }
        } catch (\Throwable $e) {
            Log::error("Failed to assign payroll element to User {$userId} for Tenant {$this->tenantId}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'An error occurred while assigning the payroll element.']);
        }
    }

    public function unassignPayrollElement(int $userId, int $payrollElementId): void
    {
        $this->checkPermission('employee:assign_payroll_elements');

        if (!$this->employeeModel->isEmployeeInTenant($userId, $this->tenantId)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'The specified employee was not found.']);
            return;
        }

        try {
            $success = $this->employeePayrollDetailsModel->deleteByEmployeeAndElement($userId, $payrollElementId, $this->tenantId);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Payroll element unassigned successfully.']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Failed to unassign payroll element or element not found.']);
            }
        } catch (\Throwable $e) {
            Log::error("Failed to unassign payroll element {$payrollElementId} from User {$userId} for Tenant {$this->tenantId}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'An error occurred while unassigning the payroll element.']);
        }
    }

    /**
     * Handles the creation of an employee profile for the currently logged-in user (e.g., a tenant admin).
     * This method is called from the /my-account/create-employee-profile route.
     */
    public function storeMyEmployeeProfile(): void
    {
        // Ensure the user is authenticated and has permission to view their own profile
        // The middleware already handles 'self:view_profile'
        $currentUserId = Auth::userId();
        $tenantId = Auth::tenantId();

        Log::debug("storeMyEmployeeProfile called for User ID: {$currentUserId}, Tenant ID: {$tenantId}");
        Log::debug("POST Data: ", $_POST);

        // 1. Validation and Sanitization
        $validator = new Validator($_POST);
        $validator->validate([
            'user_id' => 'required|int|equals:' . $currentUserId, // Ensure form submission is for the logged-in user
            'date_of_birth' => 'required|date',
            'gender' => 'required|min:1',
            'marital_status' => 'required|min:1',
            'nationality' => 'required|min:2',
            'home_address' => 'optional|max:500',
            'employee_id' => 'required|min:3',
            'hire_date' => 'required|date',
            'employment_type' => 'required|min:1',
            'current_position_id' => 'required|int',
            'monthly_base_salary' => 'required|numeric',
            'payment_method' => 'required|min:1',
            'bank_name' => 'optional|max:100',
            'bank_branch' => 'optional|max:100',
            'bank_account_name' => 'optional|max:100',
            'bank_account_number' => 'optional|max:50',
            'ssnit_number' => 'optional|max:50',
            'tin_number' => 'optional|max:50',
            'id_card_type' => 'optional|min:1',
            'id_card_number' => 'optional|max:100',
            'emergency_contact_name' => 'required|min:3',
            'emergency_contact_phone' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            $errors = implode('<br>', $validator->errors());
            Log::error("Validation failed in storeMyEmployeeProfile: {$errors}", ['post_data' => $_POST]);
            $_SESSION['flash_error'] = "Profile creation failed due to invalid input: <br>{$errors}";
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/my-account'); // Redirect back to my-account, which will show the form again
            return;
        }

        Log::debug("Validation passed in storeMyEmployeeProfile.");

        // --- START Uniqueness Checks ---
        $ssnit = $validator->get('ssnit_number', 'string', null);
        $tin = $validator->get('tin_number', 'string', null);
        $customErrors = [];

        // Check SSNIT uniqueness (only if provided)
        if (!empty($ssnit) && $this->employeeModel->isComplianceNumberInUse($ssnit, 'ssnit_number')) {
            $customErrors[] = "The **SSNIT number** '{$ssnit}' is already registered to another user.";
        }

        // Check TIN uniqueness (only if provided)
        if (!empty($tin) && $this->employeeModel->isComplianceNumberInUse($tin, 'tin_number')) {
            $customErrors[] = "The **TIN number** '{$tin}' is already registered to another user.";
        }

        // Check Employee ID uniqueness within the current tenant
        $employeeId = $validator->get('employee_id');
        if ($this->employeeModel->isEmployeeIdInUse($employeeId)) {
            $customErrors[] = "The **Employee ID** '{$employeeId}' is already assigned within this tenant.";
        }

        if (!empty($customErrors)) {
            $errors = implode('<br>', $customErrors);
            Log::error("Uniqueness checks failed in storeMyEmployeeProfile: {$errors}", ['post_data' => $_POST]);
            $_SESSION['flash_error'] = "Profile creation failed due to duplicate data: <br>{$errors}";
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/my-account');
            return;
        }
        Log::debug("Uniqueness checks passed in storeMyEmployeeProfile.");

        // 2. Start Transaction
        $db = $this->employeeModel->getDB();
        try {
            $db->beginTransaction();
            Log::debug("Transaction started in storeMyEmployeeProfile.");

            $userProfileModel = new UserProfileModel();
            $auditModel = new AuditModel();
            $userModel = new UserModel();

            // Fetch user's first and last name for audit log
            $currentUser = $userModel->find($currentUserId);
            $userFirstName = $currentUser['first_name'] ?? 'Unknown';
            $userLastName = $currentUser['last_name'] ?? 'User';

            // 3. Prepare Profile Data
            $profileData = [
                'user_id' => $currentUserId, // Required for createProfile
                'date_of_birth' => $validator->get('date_of_birth'),
                'gender' => $validator->get('gender'),
                'marital_status' => $validator->get('marital_status'),
                'nationality' => $validator->get('nationality'),
                'home_address' => $validator->get('home_address', 'string', null),
                'ssnit_number' => $ssnit,
                'tin_number' => $tin,
                'id_card_type' => $validator->get('id_card_type', 'string', null),
                'id_card_number' => $validator->get('id_card_number', 'string', null),
                'emergency_contact_name' => $validator->get('emergency_contact_name'),
                'emergency_contact_phone' => $validator->get('emergency_contact_phone'),
            ];

            // 3.1. Check if user profile exists and either update or create
            if ($userProfileModel->profileExists($currentUserId)) {
                Log::debug("User profile exists for User ID: {$currentUserId}. Attempting to update profile.");
                if (!$userProfileModel->updateProfile($currentUserId, $profileData)) {
                    throw new \Exception("Failed to update user profile.");
                }
                Log::debug("User profile updated successfully for User ID: {$currentUserId}.");
            } else {
                Log::debug("User profile does NOT exist for User ID: {$currentUserId}. Attempting to create profile.");
                if (!$userProfileModel->createProfile($profileData)) {
                    throw new \Exception("Failed to create user profile.");
                }
                Log::debug("User profile created successfully for User ID: {$currentUserId}.");
            }

            // 4. Create Employee Record (employees)
            // This is a new record for an existing user.
            $employeeData = [
                'user_id' => $currentUserId,
                'tenant_id' => $tenantId,
                'employee_id' => $employeeId,
                'current_position_id' => $validator->get('current_position_id', 'int'),
                'hire_date' => $validator->get('hire_date'),
                'employment_type' => $validator->get('employment_type'),
                'current_salary_ghs' => $validator->get('monthly_base_salary', 'float'),
                'payment_method' => $validator->get('payment_method'),
                'bank_name' => $validator->get('bank_name', 'string', null),
                'bank_branch' => $validator->get('bank_branch', 'string', null),
                'bank_account_name' => $validator->get('bank_account_name', 'string', null),
                'bank_account_number' => $validator->get('bank_account_number', 'string', null),
                'is_payroll_eligible' => 1, // Default to eligible
            ];
            Log::debug("Attempting to create employee record for User ID: {$currentUserId}", ['employee_data' => $employeeData]);
            if (!$this->employeeModel->createEmployeeRecord($employeeData)) {
                throw new \Exception("Failed to create employee record.");
            }
            Log::debug("Employee record created successfully for User ID: {$currentUserId}.");

            // 5. Audit Logging
            $auditModel->log(
                $tenantId,
                'Employee Profile Created for User: ' . $userFirstName . ' ' . $userLastName,
                ['user_id' => $currentUserId, 'employee_id' => $employeeId]
            );
            Log::debug("Audit log created for employee profile creation.");

            // 6. Commit Transaction
            $db->commit();
            Log::debug("Transaction committed successfully in storeMyEmployeeProfile.");

            // 7. Success Handling
            $_SESSION['flash_success'] = "Your employee profile has been created successfully!";
            $this->redirect('/my-account'); // Redirect to the now-populated my-account page

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Log::critical("Employee Profile Creation Transaction Failed for User {$currentUserId}: " . $e->getMessage(), [
                'user_id' => $currentUserId,
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString() // Add full trace for better debugging
            ]);

            $_SESSION['flash_error'] = "Error creating your employee profile: A critical system error occurred. Please try again.";
            $this->redirect('/my-account');
        }
    }}