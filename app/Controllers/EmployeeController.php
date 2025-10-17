<?php 
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Models\EmployeeModel;
use Jeffrey\Sikapay\Models\DepartmentModel;
use Jeffrey\Sikapay\Models\PositionModel;
use Jeffrey\Sikapay\Models\UserModel; 
use Jeffrey\Sikapay\Models\UserProfileModel; 
use Jeffrey\Sikapay\Models\AuditModel; 
use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Services\SubscriptionService;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder; 
use \Throwable;


class EmployeeController extends Controller
{
    private EmployeeModel $employeeModel;
    private DepartmentModel $departmentModel;
    private PositionModel $positionModel;
    private SubscriptionService $subscriptionService;

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

    /**
     * Display the list of employees for the current tenant.
     */
    public function index(): void
    {
        try {
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
     * Handles the POST request to save a new employee (Quick Create).
     */
    public function store(): void
    {
        // ... (1. Basic Validation - remains unchanged) ...
        if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email']) || 
            empty($_POST['employee_id']) || empty($_POST['hire_date']) || empty($_POST['position_id']) || empty($_POST['gender']) || empty($_POST['monthly_base_salary'])) {
            
            $_SESSION['flash_error'] = 'Required fields (Name, Email, ID, Hire Date, Position, Gender, Salary) are missing.';
            $_SESSION['flash_input'] = $_POST;
            $this->redirect('/employees/create');
            return;
        }

        // 2. FEATURE GATING: Check Employee Limit
        try {
            $limit = $this->subscriptionService->getFeatureLimit($this->tenantId, 'employee_limit');
            // NOTE: Assumes $this->employeeModel->getEmployeeCount() is implemented and hardened
            $currentCount = $this->employeeModel->getEmployeeCount($this->tenantId); 
            
            if ($currentCount >= $limit) {
                $planName = $this->subscriptionService->getCurrentPlanName($this->tenantId);
                // Use a standard Exception for business logic failure
                throw new \Exception("Employee creation limit reached. Your current {$planName} Plan allows a maximum of {$limit} employees. Please upgrade your subscription.");
            }
        } catch (Throwable $e) {
            // Catch limit errors OR underlying DB/Service errors during the check
            Log::error("Employee Limit Check Failed for Tenant {$this->tenantId}: " . $e->getMessage());
            $_SESSION['flash_error'] = "Limit Check Error: " . $e->getMessage();
            $_SESSION['flash_input'] = $_POST;
            $this->redirect('/employees/create');
            return;
        }

        // 3. Start Transaction
        $db = $this->employeeModel->getDB(); 
        $newUserId = null; 

        try {
            $db->beginTransaction();

            // 4. Create User Record (users) - uses hardened $this->userModel
            $userData = [/* ... data ... */];
            $newUserId = $this->userModel->createUser($this->tenantId, $userData); 
            if (!$newUserId) {
                // If createUser failed but didn't throw an exception (e.g., returned 0), we throw here.
                throw new \Exception("Failed to retrieve new user ID after creation.");
            }

            // 5. Create User Profile Record (user_profiles) - uses hardened $this->userProfileModel
            $profileData = [/* ... data ... */];
            if (!$this->userProfileModel->createProfile($profileData)) {
                throw new \Exception("Failed to create user profile.");
            }
            
            // 6. Create Employee Record (employees) - assumes $this->employeeModel is hardened
            $employeeData = [/* ... data ... */];
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
            // Use Throwable to catch DB connection/Query failures
            // 10. Failure: Rollback
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            // Log failure as CRITICAL since this is a failed transaction/data loss event.
            Log::critical("Employee Creation Transaction Failed for Tenant {$this->tenantId}: " . $e->getMessage(), [
                'user_id' => $this->userId, 
                'email' => $_POST['email'] ?? 'N/A',
                'line' => $e->getLine()
            ]);
            
            $_SESSION['flash_error'] = "Error creating employee: A critical system error occurred. Please try again. (" . substr($e->getMessage(), 0, 50) . "...)";
            $_SESSION['flash_input'] = $_POST;
            $this->redirect('/employees/create');
        }
    }


    /**
     * API endpoint to fetch positions based on the selected department ID.
     */
    public function getPositionsByDepartment(): void
    {
        // 1. Authentication check is handled by parent::__construct and AuthMiddleware
        if (!$this->auth->check()) {
            // Must return JSON 401 response for API calls
            header('Content-Type: application/json');
            http_response_code(401); 
            echo json_encode(['error' => 'Authentication required.']);
            exit; 
        }

        try {
            // 2. Get the department ID from the request
            $departmentId = (int)($_GET['department_id'] ?? 0);

            if ($departmentId <= 0) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['error' => 'Invalid department ID.']);
                exit;
            }

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


    /**
     * Display a single employee's profile.
     */
    public function show(int $userId): void
    {
        try {
            $employee = $this->employeeModel->getEmployeeProfile($userId); 

            if (!$employee) {
                // If not found (or tenant scope failed the read)
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


    /**
     * Handles the PUT request to update an existing employee's profile.
     */
    public function update(int $userId): void
    {
        // 1. Basic Security and Validation (remains unchanged)
        if (empty($_POST['first_name']) || empty($_POST['last_name']) || 
            empty($_POST['employee_id']) || empty($_POST['hire_date']) || 
            empty($_POST['position_id']) || empty($_POST['monthly_base_salary']) ||
            empty($_POST['gender']) || empty($_POST['date_of_birth'])) {
            
            $_SESSION['flash_error'] = 'Required fields (Name, ID, Hire Date, Position, Salary, Gender, DOB) are missing.';
            $_SESSION['flash_input'] = $_POST;
            $this->redirect("/employees/{$userId}/edit");
            return;
        }

        $db = $this->employeeModel->getDB(); 

        try {
            // 2. Start Transaction
            $db->beginTransaction();

            // --- A. Update User Record (users) ---
            $userData = [/* ... data ... */];
            // NOTE: We rely on the hardened UserModel to throw or log failures.
            $this->userModel->updateUser($userId, $userData); 

            // --- B. Update User Profile Record (user_profiles) ---
            $profileData = [/* ... data ... */];
            $this->userProfileModel->updateProfile($userId, $profileData); 
            
            // --- C. Update Employee Record (employees) ---
            $employeeData = [/* ... data ... */];
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
            // Use Throwable to catch all transaction failures
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
            $_SESSION['flash_input'] = $_POST;
            $this->redirect("/employees/{$userId}/edit");
        }
    }
}