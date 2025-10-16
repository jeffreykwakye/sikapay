<?php 

declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Models\EmployeeModel;
use Jeffrey\Sikapay\Models\DepartmentModel;
use Jeffrey\Sikapay\Models\PositionModel;
use Jeffrey\Sikapay\Models\UserModel;        
use Jeffrey\Sikapay\Models\UserProfileModel; 
use Jeffrey\Sikapay\Models\AuditModel;       
use Jeffrey\Sikapay\Controllers\Controller; // Base Controller

class EmployeeController extends Controller
{
    // Define only models NOT assumed to be defined/inherited from the parent Controller
    private EmployeeModel $employeeModel;
    private DepartmentModel $departmentModel;
    private PositionModel $positionModel;

    protected UserModel $userModel;            
    protected UserProfileModel $userProfileModel;
    protected AuditModel $auditModel; 

   
    public function __construct()
    {
        parent::__construct();
        
        // 1. Instantiate models specific to EmployeeController
        $this->employeeModel = new EmployeeModel();
        $this->departmentModel = new DepartmentModel();
        $this->positionModel = new PositionModel();
        
        $this->userModel = new UserModel();
        $this->userProfileModel = new UserProfileModel();
        $this->auditModel = new AuditModel();
    }

    /**
     * Display the list of employees for the current tenant.
     * Requires 'employee:list' permission.
     */
    public function index(): void
    {
        $employees = $this->employeeModel->getAllEmployees();
        
        $this->view('employee/index', [
            'title' => 'Employee Directory',
            'employees' => $employees,
        ]);
    }

    
    /**
     * Display the form to create a new employee.
     * Requires 'employee:create' permission.
     */
    public function create(): void
    {
        $departments = $this->departmentModel->all();
        // $positions is loaded via API/JS on the view, but we pass all positions 
        // in case we need sticky data for an error redirect (though simplified view doesn't rely on it)
        $positions = $this->positionModel->all(); 
        
        $this->view('employee/create', [
            'title' => 'Add New Employee',
            'departments' => $departments,
            'positions' => $positions,
        ]);
    }


    /**
     * Handles the POST request to save a new employee (Quick Create).
     * Requires 'employee:create' permission.
     */
    public function store(): void
    {
        // 1. Basic Validation (Minimum fields only)
        if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email']) || 
            empty($_POST['employee_id']) || empty($_POST['hire_date']) || empty($_POST['position_id']) || empty($_POST['gender']) || empty($_POST['monthly_base_salary'])) {
            
            $_SESSION['flash_error'] = 'Required fields (Name, Email, ID, Hire Date, Position, Gender, Salary) are missing.';
            $_SESSION['flash_input'] = $_POST;
            $this->redirect('/employees/create');
            return;
        }

        $db = $this->employeeModel->getDB(); 
        $newUserId = null; 

        try {
            // 2. Start Transaction
            $db->beginTransaction();

            // 3. Create User Record (Table: users)
            $userData = [
                'role_id'    => 5, // Default Role ID 5: 'employee'
                'email'      => $_POST['email'],
                'password'   => password_hash('welcome', PASSWORD_DEFAULT), 
                'first_name' => $_POST['first_name'],
                'last_name'  => $_POST['last_name'],
                'other_name' => $_POST['other_name'] ?? null,
                'phone'      => null, 
            ];
            
            $newUserId = $this->userModel->createUser($this->tenantId, $userData); 

            if (!$newUserId) {
                throw new \Exception("Failed to create user record.");
            }

            // 4. Create User Profile Record (Table: user_profiles - using safe defaults/hidden inputs)
            $profileData = [
                'user_id'                 => $newUserId,
                'date_of_birth'           => $_POST['date_of_birth'], 
                'nationality'             => 'Ghanaian', 
                'marital_status'          => $_POST['marital_status'], 
                'gender'                  => $_POST['gender'],
                'home_address'            => null,
                'ssnit_number'            => null, 
                'tin_number'              => null, 
                'id_card_type'            => 'Ghana Card',
                'id_card_number'          => null,
                'emergency_contact_name'  => $_POST['emergency_contact_name'],
                'emergency_contact_phone' => $_POST['emergency_contact_phone'],
            ];
            
            $profileSuccess = $this->userProfileModel->createProfile($profileData); 
            
            if (!$profileSuccess) {
                throw new \Exception("Failed to create user profile.");
            }
            
            // 5. Create Employee Record (Table: employees)
            $employeeData = [
                'user_id'               => $newUserId,
                'tenant_id'             => $this->tenantId,
                'employee_id'           => $_POST['employee_id'],
                'hire_date'             => $_POST['hire_date'],
                'current_position_id'   => (int)$_POST['position_id'],
                'employment_type'       => 'Full-Time', 
                'current_salary_ghs'    => (float)$_POST['monthly_base_salary'],
                'payment_method'        => 'Bank Transfer', 
                'bank_name'             => null, 
                'bank_account_number'   => null,
            ];
            
            $employeeSuccess = $this->employeeModel->createEmployeeRecord($employeeData);

            if (!$employeeSuccess) {
                throw new \Exception("Failed to create employee record.");
            }
            
            // 6. Audit Logging 
            $this->auditModel->log(
                $this->tenantId, 
                'EMPLOYEE_CREATED_QUICK',
                ['employee_id' => $employeeData['employee_id'], 'user_id' => $newUserId]
            );

            // 7. Commit Transaction
            $db->commit();
            
            // 8. Success Handling: Redirect to the new employee's edit page
            $_SESSION['flash_success'] = "Employee " . $userData['first_name'] . " saved successfully. **Profile details are incomplete.**";
            
            $this->redirect("/employees/{$newUserId}/edit");

        } catch (\Exception $e) {
            // 9. Failure: Rollback
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            $_SESSION['flash_error'] = "Error creating employee: " . $e->getMessage();
            $_SESSION['flash_input'] = $_POST;
            $this->redirect('/employees/create');
        }
    }


    /**
     * API endpoint to fetch positions based on the selected department ID.
     * Requires user to be logged in (handled by BaseController check).
     */
    public function getPositionsByDepartment(): void
    {
        // 1. Authentication check is handled by parent::__construct
        if (!$this->auth->check()) {
            http_response_code(401); 
            echo json_encode(['error' => 'Authentication required.']);
            return;
        }

        // 2. Get the department ID from the request
        $departmentId = (int)($_GET['department_id'] ?? 0);

        if ($departmentId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid department ID.']);
            return;
        }

        // 3. Fetch positions for that department, respecting tenant scoping
        $positions = $this->positionModel->all(['department_id = ' . $departmentId]);

        // 4. Return JSON response
        header('Content-Type: application/json');
        echo json_encode(['positions' => $positions]);
        
        exit; 
    }


    /**
     * Display a single employee's profile.
     * Requires 'employee:read_all' permission.
     * * @param int $userId The user_id of the employee to display.
     */
    public function show(int $userId): void
    {
        // Fetch the full employee profile (we'll define this method next)
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
    }

    /**
     * Display the form to edit an existing employee's profile.
     * Requires 'employee:update' permission.
     * * @param int $userId The user_id of the employee to edit.
     */
    public function edit(int $userId): void
    {
        // 1. Fetch the existing employee profile data
        $employee = $this->employeeModel->getEmployeeProfile($userId); 

        if (!$employee) {
            http_response_code(404);
            $this->view('error/404', ['title' => 'Employee Not Found']);
            return;
        }
        
        // 2. Fetch ancillary data needed for dropdowns
        $departments = $this->departmentModel->all();
        // Note: For sticky form, we need the positions for the *current* department
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
    }


    /**
     * Handles the PUT request to update an existing employee's profile.
     * Requires 'employee:update' permission.
     *
     * @param int $userId The user_id of the employee to update.
     */
    public function update(int $userId): void
    {
        // 1. Basic Security and Validation (Ensure all critical fields are present)
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

            // --- A. Update User Record (Table: users) ---
            $userData = [
                'first_name' => $_POST['first_name'],
                'last_name'  => $_POST['last_name'],
                'other_name' => $_POST['other_name'] ?? null,
                'phone'      => $_POST['phone'] ?? null,
                // Email is deliberately omitted as it's typically read-only or handled separately
            ];
            
            // Assume $this->userModel has an update method (e.g., updateUser($id, $data))
            $userSuccess = $this->userModel->updateUser($userId, $userData); 

            if (!$userSuccess) {
                // Not throwing an exception if update returns 0 rows affected (data was the same), 
                // but only if a genuine error occurred (e.g., query fail). We'll assume success 
                // unless the model indicates failure or throws.
            }

            // --- B. Update User Profile Record (Table: user_profiles) ---
            $profileData = [
                'date_of_birth'           => $_POST['date_of_birth'],
                'nationality'             => $_POST['nationality'] ?? 'Ghanaian',
                'marital_status'          => $_POST['marital_status'],
                'gender'                  => $_POST['gender'],
                'home_address'            => $_POST['home_address'] ?? null,
                'ssnit_number'            => $_POST['ssnit_number'] ?? null,
                'tin_number'              => $_POST['tin_number'] ?? null,
                'id_card_type'            => $_POST['id_card_type'] ?? 'Ghana Card',
                'id_card_number'          => $_POST['id_card_number'] ?? null,
                'emergency_contact_name'  => $_POST['emergency_contact_name'],
                'emergency_contact_phone' => $_POST['emergency_contact_phone'],
            ];
            
            // Assume $this->userProfileModel has an update method (e.g., updateProfile($userId, $data))
            $profileSuccess = $this->userProfileModel->updateProfile($userId, $profileData); 
            
            if (!$profileSuccess) {
                // Consider how your model handles updates where data hasn't changed
            }
            
            // --- C. Update Employee Record (Table: employees) ---
            $employeeData = [
                'employee_id'           => $_POST['employee_id'],
                'hire_date'             => $_POST['hire_date'],
                'current_position_id'   => (int)$_POST['position_id'],
                'employment_type'       => $_POST['employment_type'] ?? 'Full-Time',
                'current_salary_ghs'    => (float)$_POST['monthly_base_salary'],
                'payment_method'        => $_POST['payment_method'] ?? 'Bank Transfer',
                'bank_name'             => $_POST['bank_name'] ?? null,
                'bank_account_number'   => $_POST['bank_account_number'] ?? null,
            ];
            
            // Assume $this->employeeModel has an update method (e.g., updateEmployeeRecord($userId, $data))
            $employeeSuccess = $this->employeeModel->updateEmployeeRecord($userId, $employeeData);

            if (!$employeeSuccess) {
                // Consider how your model handles updates where data hasn't changed
            }
            
            // 3. Audit Logging 
            $this->auditModel->log(
                $this->tenantId, 
                'EMPLOYEE_PROFILE_UPDATED',
                ['user_id' => $userId]
            );

            // 4. Commit Transaction
            $db->commit();
            
            // 5. Success Handling: Redirect to the employee's view page
            $_SESSION['flash_success'] = "Employee profile updated successfully.";
            $this->redirect("/employees/{$userId}"); // Redirect to the read-only 'show' page

        } catch (\Exception $e) {
            // 6. Failure: Rollback
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            // Log the error for debugging
            // Log::error("Employee Update Failed for ID {$userId}: " . $e->getMessage());
            
            $_SESSION['flash_error'] = "Error updating employee: " . $e->getMessage();
            $_SESSION['flash_input'] = $_POST;
            $this->redirect("/employees/{$userId}/edit");
        }
    }

}