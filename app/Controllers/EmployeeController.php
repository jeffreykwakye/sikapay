<?php 
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Models\EmployeeModel;
use Jeffrey\Sikapay\Models\DepartmentModel;
use Jeffrey\Sikapay\Models\PositionModel;
use Jeffrey\Sikapay\Models\UserModel; 
use Jeffrey\Sikapay\Models\UserProfileModel; 
use Jeffrey\Sikapay\Models\AuditModel; 
// 🚨 NEW: Import RoleModel
use Jeffrey\Sikapay\Models\RoleModel; 
use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Services\SubscriptionService;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder; 
// 🚨 NEW: Import Validator
use Jeffrey\Sikapay\Core\Validator;
use \Throwable;


class EmployeeController extends Controller
{
    private EmployeeModel $employeeModel;
    private DepartmentModel $departmentModel;
    private PositionModel $positionModel;
    private SubscriptionService $subscriptionService;
    
    // 🚨 NEW: RoleModel property
    private RoleModel $roleModel;

    protected UserModel $userModel;
    protected UserProfileModel $userProfileModel;
    protected AuditModel $auditModel; 

    
    public function __construct()
    {
        parent::__construct();
        
        try {
            // 1. Instantiate models and services
            $this->employeeModel = new EmployeeModel();
            $this->departmentModel = new DepartmentModel();
            $this->positionModel = new PositionModel();
            
            // 🚨 NEW: Instantiate RoleModel
            $this->roleModel = new RoleModel();
            
            $this->userModel = new UserModel();
            $this->userProfileModel = new UserProfileModel();
            $this->auditModel = new AuditModel();

            $this->subscriptionService = new SubscriptionService();
        } catch (Throwable $e) {
            // If models/services fail to instantiate (e.g., DB connection issue)
            Log::critical("EmployeeController failed to initialize models/services: " . $e->getMessage());
            
            // Halt execution with a 500 error page
            ErrorResponder::respond(500, "A critical system error occurred during controller initialization.");
        }
    }

// ------------------------------------------------------------------
// VIEW METHODS (index, create, show, edit) - Unchanged/Clean
// ------------------------------------------------------------------

    /**
     * Display the list of employees for the current tenant.
     */
    public function index(): void
    {
        try {
            // NOTE: Add security check here if not in middleware
            // $this->checkPermission('employee:view');
            $employees = $this->employeeModel->getAllEmployees();
            
            $this->view('employee/index', [
                'title' => 'Employee Directory',
                'employees' => $employees,
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to load employee directory for Tenant {$this->tenantId}: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load the employee directory due to a system error.");
        }
    }

    
    /**
     * Display the form to create a new employee.
     */
    public function create(): void
    {
        try {
            // NOTE: Add security check here if not in middleware
            // $this->checkPermission('employee:create');
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
            // $this->checkPermission('employee:view_self'); // If applicable
            $employee = $this->employeeModel->getEmployeeProfile($userId); 

            if (!$employee) {
                http_response_code(404);
                $this->view('error/404', ['title' => 'Employee Not Found']);
                return;
            }

            $this->view('employee/show', [
                'title' => 'Employee Profile: ' . $employee['first_name'] . ' ' . $employee['last_name'],
                'employee' => $employee,
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
            // $this->checkPermission('employee:edit');
            // 1. Fetch the existing employee profile data
            $employee = $this->employeeModel->getEmployeeProfile($userId); 

            if (!$employee) {
                http_response_code(404);
                $this->view('error/404', ['title' => 'Employee Not Found']);
                return;
            }
            
            // 2. Fetch ancillary data needed for dropdowns
            $departments = $this->departmentModel->all();
            $currentDepartmentId = $employee['department_id'] ?? 0;
            $positions = $currentDepartmentId > 0 
                ? $this->positionModel->all(['department_id = ' . $currentDepartmentId]) 
                : []; 
                
            $this->view('employee/edit', [
                'title' => 'Edit Employee: ' . $employee['first_name'] . ' ' . $employee['last_name'],
                'employee' => $employee,
                'departments' => $departments,
                'positions' => $positions,
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to load employee profile (edit) for User {$userId}: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load the employee edit form due to a system error.");
        }
    }

// ------------------------------------------------------------------
// DML/ACTION METHODS - HARDENED
// ------------------------------------------------------------------

    /**
     * Handles the POST request to save a new employee (Quick Create).
     */
    public function store(): void
    {
        // 🚨 1. HARDENED: Validation and Sanitization
        $validator = new Validator($_POST);
        
        $validator->validate([
            'first_name' => 'required|min:2',
            'last_name' => 'required|min:2',
            'email' => 'required|email',
            'employee_id' => 'required|min:3', 
            'hire_date' => 'required|date', 
            'position_id' => 'required|int', 
            'gender' => 'required|min:1', 
            'monthly_base_salary' => 'required|numeric', 
            'phone' => 'optional|min:8',
        ]);

        if ($validator->fails()) {
            $errors = implode('<br>', $validator->errors());
            $_SESSION['flash_error'] = "Employee creation failed due to invalid input: <br>{$errors}";
            $_SESSION['flash_input'] = $validator->all(); 
            $this->redirect('/employees/create');
            return;
        }

        // 2. FEATURE GATING: Check Employee Limit (Logic remains robustly wrapped in try/catch)
        try {
            $limit = $this->subscriptionService->getFeatureLimit($this->tenantId, 'employee_limit');
            $currentCount = $this->employeeModel->getEmployeeCount($this->tenantId); 
            
            if ($currentCount >= $limit) {
                $planName = $this->subscriptionService->getCurrentPlanName($this->tenantId);
                throw new \Exception("Employee creation limit reached. Your current {$planName} Plan allows a maximum of {$limit} employees. Please upgrade your subscription.");
            }
        } catch (Throwable $e) {
            // Catch limit errors OR underlying DB/Service errors during the check
            Log::error("Employee Limit Check Failed for Tenant {$this->tenantId}: " . $e->getMessage());
            $_SESSION['flash_error'] = "Limit Check Error: " . $e->getMessage();
            $_SESSION['flash_input'] = $validator->all(); // Use sanitized input
            $this->redirect('/employees/create');
            return;
        }

        // 3. Start Transaction
        $db = $this->employeeModel->getDB(); 
        $newUserId = null; 

        try {
            $db->beginTransaction();

            // 🚨 FIX: Use RoleModel to get the ID and validate its existence
            $employeeRoleId = $this->roleModel->findIdByName('employee');
            
            if ($employeeRoleId === null) {
                 // Throwing an exception ensures rollback happens in the catch block
                 throw new \Exception("Configuration Error: 'employee' role not found in database. Cannot create user.");
            }
            
            // 4. Create User Record (users) - USE VALIDATOR::GET()
            $userData = [
                'email' => $validator->get('email'),
                'password' => password_hash('temporary_pass_' . time(), PASSWORD_DEFAULT), 
                'first_name' => $validator->get('first_name'),
                'last_name' => $validator->get('last_name'),
                'phone' => $validator->get('phone'),
                'role_id' => $employeeRoleId, 
            ];
            $newUserId = $this->userModel->createUser($this->tenantId, $userData); 
            if (!$newUserId) {
                 throw new \Exception("Failed to retrieve new user ID after creation.");
            }

            // 5. Create User Profile Record (user_profiles) - USE VALIDATOR::GET()
            $profileData = [
                'user_id' => $newUserId,
                'gender' => $validator->get('gender'),
                // date_of_birth is optional in quick create, but if present, grab it.
                'date_of_birth' => $validator->get('date_of_birth', 'string') // Use string type hint for date
            ];
            if (!$this->userProfileModel->createProfile($profileData)) {
                 throw new \Exception("Failed to create user profile.");
            }
            
            // 6. Create Employee Record (employees) - USE VALIDATOR::GET()
            $employeeData = [
                'user_id' => $newUserId,
                'employee_id' => $validator->get('employee_id'),
                'position_id' => $validator->get('position_id', 'int'),
                'hire_date' => $validator->get('hire_date'),
                'monthly_base_salary' => $validator->get('monthly_base_salary', 'float'),
            ];
            if (!$this->employeeModel->createEmployeeRecord($employeeData)) {
                 throw new \Exception("Failed to create employee record.");
            }
            
            // 7. Audit Logging 
            $this->auditModel->log(
                $this->tenantId, 
                'EMPLOYEE_CREATED_QUICK',
                ['employee_id' => $employeeData['employee_id'], 'user_id' => $newUserId]
            );

            // 8. Commit Transaction
            $db->commit();
            
            // 9. Success Handling
            $_SESSION['flash_success'] = "Employee " . $userData['first_name'] . " saved successfully. **Profile details are incomplete.**";
            $this->redirect("/employees/{$newUserId}/edit");

        } catch (Throwable $e) { 
            // 10. Failure: Rollback
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            // Log failure as CRITICAL since this is a failed transaction/data loss event.
            Log::critical("Employee Creation Transaction Failed for Tenant {$this->tenantId}: " . $e->getMessage(), [
                'user_id' => $this->userId, 
                'email' => $validator->get('email') ?? 'N/A', // Use validator for safety
                'line' => $e->getLine()
            ]);
            
            $_SESSION['flash_error'] = "Error creating employee: A critical system error occurred. Please try again. (" . substr($e->getMessage(), 0, 50) . "...)";
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/employees/create');
        }
    }


    /**
     * Handles the PUT request to update an existing employee's profile.
     */
    public function update(int $userId): void
    {
        // 🚨 1. HARDENED: Validation and Sanitization (Logic is robust)
        $validator = new Validator($_POST);
        
        $validator->validate([
            'first_name' => 'required|min:2',
            'last_name' => 'required|min:2',
            'employee_id' => 'required|min:3', 
            'hire_date' => 'required|date', 
            'position_id' => 'required|int', 
            'monthly_base_salary' => 'required|numeric',
            'gender' => 'required|min:1',
            'date_of_birth' => 'required|date',
            'phone' => 'optional|min:8', 
        ]);
        
        if ($validator->fails()) {
            $errors = implode('<br>', $validator->errors());
            $_SESSION['flash_error'] = "Update failed due to invalid input: <br>{$errors}";
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect("/employees/{$userId}/edit");
            return;
        }

        $db = $this->employeeModel->getDB(); 

        try {
            // 2. Start Transaction
            $db->beginTransaction();

            // --- A. Update User Record (users) ---
            $userData = [
                'first_name' => $validator->get('first_name'),
                'last_name' => $validator->get('last_name'),
                'phone' => $validator->get('phone'),
                'other_name' => $validator->get('other_name'),
                // Email field is typically handled in a separate, more secure process
            ];
            $this->userModel->updateUser($userId, $userData); 

            // --- B. Update User Profile Record (user_profiles) ---
            $profileData = [
                'gender' => $validator->get('gender'),
                'date_of_birth' => $validator->get('date_of_birth'),
            ];
            $this->userProfileModel->updateProfile($userId, $profileData); 
            
            // --- C. Update Employee Record (employees) ---
            $employeeData = [
                'employee_id' => $validator->get('employee_id'),
                'position_id' => $validator->get('position_id', 'int'),
                'hire_date' => $validator->get('hire_date'),
                'monthly_base_salary' => $validator->get('monthly_base_salary', 'float'),
            ];
            $this->employeeModel->updateEmployeeRecord($userId, $employeeData);
            
            // 3. Audit Logging 
            $this->auditModel->log(
                $this->tenantId, 
                'EMPLOYEE_PROFILE_UPDATED',
                ['user_id' => $userId]
            );

            // 4. Commit Transaction
            $db->commit();
            
            // 5. Success Handling
            $_SESSION['flash_success'] = "Employee profile updated successfully.";
            $this->redirect("/employees/{$userId}");

        } catch (Throwable $e) { 
            // 6. Failure: Rollback
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            // Log failure as CRITICAL since this is a failed transaction/data loss event.
            Log::critical("Employee Update Transaction Failed for User {$userId}: " . $e->getMessage(), [
                'acting_user_id' => $this->userId, 
                'line' => $e->getLine()
            ]);
            
            $_SESSION['flash_error'] = "Error updating employee: A critical system error occurred. Please try again. (" . substr($e->getMessage(), 0, 50) . "...)";
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect("/employees/{$userId}/edit");
        }
    }
    
// ------------------------------------------------------------------
// API ENDPOINTS - HARDENED
// ------------------------------------------------------------------

    /**
     * API endpoint to fetch positions based on the selected department ID.
     */
    public function getPositionsByDepartment(): void
    {
        // 1. Authentication check is handled by parent::__construct and AuthMiddleware
        if (!$this->auth->check()) {
            header('Content-Type: application/json');
            http_response_code(401); 
            echo json_encode(['error' => 'Authentication required.']);
            exit; 
        }

        try {
            // 🚨 2. HARDENED: Use Validator for query parameters ($_GET)
            $validator = new Validator($_GET); 
            
            $validator->validate([
                'department_id' => 'required|int',
            ]);

            if ($validator->fails()) {
                Log::error("API Error: Invalid department ID received.", ['input' => $_GET]);
                header('Content-Type: application/json');
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Invalid or missing department ID parameter.']);
                exit;
            }
            
            // Safely retrieve and cast the ID
            $departmentId = $validator->get('department_id', 'int'); 

            // 3. Fetch positions for that department, respecting tenant scoping
            $positions = $this->positionModel->all(['department_id = ' . $departmentId]);

            // 4. Return JSON response
            header('Content-Type: application/json');
            echo json_encode(['positions' => $positions]);
            
            exit; 
        } catch (Throwable $e) {
            // Log system failure during API data fetch
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
}