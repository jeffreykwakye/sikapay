<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Models\DepartmentModel;
use Jeffrey\Sikapay\Models\EmployeeModel;
use Jeffrey\Sikapay\Models\PayrollPeriodModel;
use Jeffrey\Sikapay\Models\PayslipModel;
use Jeffrey\Sikapay\Security\CsrfToken;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Helpers\Sanitizer;
use Jeffrey\Sikapay\Core\ErrorResponder;

class DepartmentController extends Controller
{
    private DepartmentModel $departmentModel;
    private EmployeeModel $employeeModel;
    private PayrollPeriodModel $payrollPeriodModel;
    private PayslipModel $payslipModel; // Added this line
    private const PERMISSION_MANAGE = 'config:manage_departments'; 

    public function __construct()
    {
        parent::__construct();
        
        // Basic Authentication and Super Admin check
        if (!$this->auth->check() || $this->auth->isSuperAdmin()) {
            $this->redirect('/login'); 
        }
        
        // This relies on the Router Middleware to check PERMISSION_MANAGE.
        $this->departmentModel = new DepartmentModel();
        $this->employeeModel = new EmployeeModel();
        $this->payrollPeriodModel = new PayrollPeriodModel();
        $this->payslipModel = new PayslipModel(); // Added this line
    }

    // ----------------------------------------------------------------
    // READ
    // ----------------------------------------------------------------

    /**
     * Renders the list of all departments for the tenant, enriched with payroll and employee stats.
     */
    public function index(): void
    {
        try {
            $latestPeriod = $this->payrollPeriodModel->getLatestClosedPeriod($this->tenantId);
            $payrollStats = [];
            if ($latestPeriod) {
                $payrollStats = $this->payslipModel->getAggregatedPayrollStatsByDepartment($this->tenantId, (int)$latestPeriod['id']);
            }

            $employeeCounts = $this->employeeModel->getEmployeeCountByDepartment($this->tenantId);
            $departments = $this->departmentModel->getAllByTenant();

            $departmentData = [];
            foreach ($departments as $dept) {
                $deptId = (int)$dept['id'];
                $departmentData[$deptId] = [
                    'id' => $deptId,
                    'name' => $dept['name'],
                    'employee_count' => $employeeCounts[$deptId] ?? 0,
                    'total_gross_pay' => $payrollStats[$deptId]['total_gross_pay'] ?? 0,
                    'total_net_pay' => $payrollStats[$deptId]['total_net_pay'] ?? 0,
                    'total_paye' => $payrollStats[$deptId]['total_paye'] ?? 0,
                ];
            }

            // Prepare data for charts
            $chartLabels = array_column($departmentData, 'name');
            $grossPayData = array_column($departmentData, 'total_gross_pay');
            $employeeCountData = array_column($departmentData, 'employee_count');

            $this->view('departments/index', [
                'departments' => $departmentData,
                'latestPeriodName' => $latestPeriod['period_name'] ?? 'N/A',
                'chartLabels' => json_encode($chartLabels),
                'grossPayData' => json_encode($grossPayData),
                'employeeCountData' => json_encode($employeeCountData),
                'successMessage' => $_SESSION['flash_success'] ?? null,
                'errorMessage' => $_SESSION['flash_error'] ?? null,
            ]);
            
            // Clear flash messages after display
            unset($_SESSION['flash_success'], $_SESSION['flash_error']);
            
        } catch (\PDOException $e) {
            Log::critical("Department Index failed in Controller. Error: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load the list of departments due to a temporary server error.");
        } catch (\Exception $e) {
             ErrorResponder::respond(403, $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // CREATE
    // ----------------------------------------------------------------

    /**
     * Handles POST request to create a new department. (CREATE)
     */
    public function store(): void
    {
        $this->checkActionIsAllowed();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/departments');
        }

        // CSRF Protection
        if (!CsrfToken::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = "Security error: Invalid CSRF token.";
            $this->redirect('/departments');
        }

        // 1. Data Sanitization and Validation
        $name = Sanitizer::text($_POST['name'] ?? '');
        
        if (empty($name) || strlen($name) > 100) {
            $_SESSION['flash_error'] = "Department name is required and must be under 100 characters.";
            $this->redirect('/departments');
        }

        // 2. Business Logic
        try {
            // Model::create only takes $name now (description removed)
            $newId = $this->departmentModel->create($name);
            
            Log::info("Department created: ID {$newId} by User {$this->userId} in Tenant {$this->tenantId}.");
            $_SESSION['flash_success'] = "Department '{$name}' created successfully!";
            
        } catch (\PDOException $e) {
            // Check for unique constraint violation (Error Code 23000)
            if ($e->getCode() === '23000') {
                 $_SESSION['flash_error'] = "Database error: A department with the name '{$name}' already exists. Department names must be unique.";
            } else {
                 $_SESSION['flash_error'] = "Database error: Could not create department.";
            }
            
        } catch (\Exception $e) {
             $_SESSION['flash_error'] = $e->getMessage();
        }

        $this->redirect('/departments');
    }

    // ----------------------------------------------------------------
    // UPDATE (FIXED)
    // ----------------------------------------------------------------

    /**
     * Handles POST request to update an existing department. (UPDATE)
     * @param string $id The department ID from the URL (passed by router) as a string.
     */
    public function update(string $id): void // Changed type hint to string
    {
        $this->checkActionIsAllowed();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/departments');
        }

        if (!CsrfToken::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = "Security error: Invalid CSRF token.";
            $this->redirect('/departments');
        }
        
        // Sanitize the string, then explicitly cast to integer. This avoids the fatal error.
        $departmentId = (int)Sanitizer::text($id); 

        if ($departmentId === 0) {
             $_SESSION['flash_error'] = "Invalid department ID provided.";
             $this->redirect('/departments');
        }

        // 1. Data Sanitization and Validation
        $name = Sanitizer::text($_POST['name'] ?? '');

        if (empty($name) || strlen($name) > 100) {
            $_SESSION['flash_error'] = "Department name is required and must be under 100 characters.";
            $this->redirect('/departments');
        }

        // 2. Business Logic
        try {
            $success = $this->departmentModel->update($departmentId, $name); // Pass the integer ID

            if ($success) {
                Log::info("Department updated: ID {$departmentId} to '{$name}' by User {$this->userId} in Tenant {$this->tenantId}.");
                $_SESSION['flash_success'] = "Department '{$name}' updated successfully!";
            } else {
                $_SESSION['flash_error'] = "Update failed: Department not found for this tenant or no changes were made.";
            }
        } catch (\PDOException $e) {
            // Check for unique constraint violation (Error Code 23000)
            if ($e->getCode() === '23000') {
                 $_SESSION['flash_error'] = "Update failed: A department with the name '{$name}' already exists. Department names must be unique.";
            } else {
                 Log::critical("DB Error during Department update (ID: {$departmentId}): " . $e->getMessage());
                 $_SESSION['flash_error'] = "Database error: Could not update department due to a server issue.";
            }
        } catch (\Exception $e) {
             $_SESSION['flash_error'] = $e->getMessage();
        }

        $this->redirect('/departments');
    }

    // ----------------------------------------------------------------
    // DELETE
    // ----------------------------------------------------------------

    /**
     * Handles POST request to delete a department. (DELETE)
     * @param string $id The department ID from the URL (passed by router) as a string.
     */
    public function delete(string $id): void 
    {
        $this->checkActionIsAllowed();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/departments');
        }

        if (!CsrfToken::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = "Security error: Invalid CSRF token.";
            $this->redirect('/departments');
        }
        
        // Sanitize the string, then explicitly cast to integer.
        $departmentId = (int)Sanitizer::text($id); 
        
        if ($departmentId === 0) {
             $_SESSION['flash_error'] = "Invalid department ID provided for deletion.";
             $this->redirect('/departments');
        }
        
        try {
            // 1. Critical Pre-Deletion Check (Now uses the fixed Model method)
            if ($this->departmentModel->hasAssociatedEmployees($departmentId)) {
                $_SESSION['flash_error'] = "Cannot delete: This department has active employees assigned. Please reassign their position to a position in another department first.";
                $this->redirect('/departments');
            }

            // 2. Business Logic
            $success = $this->departmentModel->delete($departmentId);

            if ($success) {
                Log::info("Department deleted: ID {$departmentId} by User {$this->userId} in Tenant {$this->tenantId}.");
                $_SESSION['flash_success'] = "Department deleted successfully!";
            } else {
                $_SESSION['flash_error'] = "Deletion failed: Department not found or already deleted.";
            }
        } catch (\PDOException $e) {
            Log::critical("DB Error during Department delete (ID: {$departmentId}): " . $e->getMessage());
            $_SESSION['flash_error'] = "Database error: Could not delete department.";
            
        } catch (\Exception $e) {
             $_SESSION['flash_error'] = $e->getMessage();
        }

        $this->redirect('/departments');
    }

    /**
     * Renders the dashboard for a specific department.
     * @param string $id The department ID.
     */
    public function dashboard(string $id): void
    {
        try {
            $departmentId = (int)Sanitizer::text($id);
            $department = $this->departmentModel->find($departmentId);

            if (!$department) {
                ErrorResponder::respond(404, "Department not found.");
                return;
            }

            // Fetch data for the dashboard widgets
            $staff = $this->employeeModel->getEmployeesByDepartmentId($departmentId);
            $payrollHistory = $this->payslipModel->getPayrollHistoryForDepartment($departmentId, 6);
            $periods = $this->payrollPeriodModel->getAllPeriods($this->tenantId);

            $this->view('departments/dashboard', [
                'title' => 'Dashboard for ' . $department['name'],
                'department' => $department,
                'staff' => $staff,
                'payrollHistory' => $payrollHistory,
                'periods' => $periods,
            ]);

        } catch (\Throwable $e) {
            Log::error("Failed to load department dashboard for ID {$id}. Error: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load the department dashboard.");
        }
    }
}