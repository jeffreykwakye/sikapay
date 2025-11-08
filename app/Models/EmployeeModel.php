<?php 
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class EmployeeModel extends Model
{
    public function __construct()
    {
        parent::__construct('employees');
    }

    /**
     * Verifies that a user ID belongs to a specific tenant ID.
     * Checks the USERS table for the definitive tenant_id for the given user.
     *
     * @param int $userId The ID of the employee (user) to check.
     * @param int $tenantId The ID of the tenant the employee MUST belong to.
     * @return bool True if the employee/user exists and belongs to the tenant, false otherwise.
     */
    public function isEmployeeInTenant(int $userId, int $tenantId): bool
    {
        // Check both users (u) and employees (e) to ensure the user exists AND is employed by the correct tenant
        $sql = "SELECT COUNT(e.user_id) 
                FROM {$this->table} e
                JOIN users u ON e.user_id = u.id
                WHERE e.user_id = :user_id AND e.tenant_id = :tenant_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->bindParam(':tenant_id', $tenantId, \PDO::PARAM_INT);
            $stmt->execute();
            
            return (int)$stmt->fetchColumn() === 1;
        } catch (PDOException $e) {
            Log::error("Security check failed (isEmployeeInTenant). Tenant {$tenantId}, User {$userId}. Error: " . $e->getMessage());
            return false;
        }
    }

    
    
    /**
     * Retrieves all employees for the current tenant.
     */
    public function getAllEmployees(): array
    {
        // Get the "WHERE tenant_id = X" clause from the base Model.
        $rawWhereClause = $this->getTenantScope(); 
        
        // FIX: Qualify the tenant_id in the WHERE clause using the 'e' alias 
        // (for the employees table) to resolve the 'ambiguous column' error.
        $whereClause = str_replace('tenant_id', 'e.tenant_id', $rawWhereClause);
        
        $sql = "SELECT 
                    e.user_id, e.employee_id, e.hire_date, e.employment_type,
                    e.current_salary_ghs, e.bank_name, e.bank_account_number, e.bank_branch, e.bank_account_name, -- Added new bank fields
                    u.first_name, u.last_name, u.email, u.is_active,
                    p.title AS position_title,
                    d.name AS department_name
                FROM employees e
                JOIN users u ON e.user_id = u.id
                LEFT JOIN positions p ON e.current_position_id = p.id
                LEFT JOIN departments d ON p.department_id = d.id
                {$whereClause}
                ORDER BY u.last_name, u.first_name";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log failure in complex read operation
            Log::error("Employee READ failed (getAllEmployees) for Tenant " . Auth::tenantId() . ". Error: " . $e->getMessage(), [
                'sql' => $sql,
                'user_id' => Auth::userId()
            ]);
            // Re-throw the exception for service/controller level handling
            throw $e;
        }
    }
    
    
    /**
     * Creates a new employee record using the user_id as the primary key.
     */
    public function createEmployeeRecord(array $data): bool
    {
        // FIX: Updated SQL to include new columns: bank_branch and bank_account_name
        $sql = "INSERT INTO {$this->table} 
                (user_id, tenant_id, employee_id, hire_date, current_position_id, 
                employment_type, current_salary_ghs, payment_method, bank_name, bank_account_number, bank_branch, 
                bank_account_name, is_payroll_eligible) 
                VALUES 
                (:user_id, :tenant_id, :employee_id, :hire_date, :current_position_id, 
                :employment_type, :current_salary_ghs, :payment_method, :bank_name, :bank_account_number, :bank_branch, 
                :bank_account_name, :is_payroll_eligible)";
        
       $defaults = [
            'is_payroll_eligible' => 1
        ];
        $finalData = array_merge($defaults, $data);
        
        try {
            $stmt = $this->db->prepare($sql);
            // Note: Assuming all required keys including the new bank fields are in $data or defaults
            return $stmt->execute($finalData);
        } catch (PDOException $e) {
            // Log failure in create operation
            Log::error("Employee CREATE failed for User " . ($data['user_id'] ?? 'unknown') . ". Error: " . $e->getMessage(), [
                'data' => $data,
                'user_id' => Auth::userId()
            ]);
            throw $e;
        }
    }

    /**
     * Updates an existing employee record by user ID.
     * @param int $userId The ID of the user whose employee record to update.
     * @param array $data The data to update.
     * @return bool True on success (even if no rows were affected).
     */
    public function updateEmployeeRecord(int $userId, array $data): bool
    {
        $setClauses = [];
        $bindParams = [':user_id' => $userId];
        
        foreach ($data as $key => $value) {
            $setClauses[] = "{$key} = :{$key}";
            $bindParams[":{$key}"] = $value;
        }
        
        if (empty($setClauses)) {
            return true;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE user_id = :user_id AND tenant_id = " . $this->currentTenantId;
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($bindParams);
        } catch (PDOException $e) {
            // Log failure in update operation
            Log::error("Employee UPDATE failed for target User {$userId}. Error: " . $e->getMessage(), [
                'updated_data_keys' => array_keys($data),
                'acting_user_id' => Auth::userId()
            ]);
            throw $e;
        }
    }

    /**
     * Retrieves a single employee's profile by user ID, joining data from 
     * users, user_profiles, employees, positions, and departments.
     */
    public function getEmployeeProfile(int $userId): ?array
    {
        $tenantScope = $this->getTenantScope();
        
        $scopeCondition = '';
        if (is_string($tenantScope)) {
            // Qualify the tenant_id check with 'u.' since 'users' is the primary table here
            $qualifiedTenantScope = str_replace('tenant_id', 'u.tenant_id', trim($tenantScope));
            // Replace the initial 'WHERE' with 'AND'
            $scopeCondition = str_ireplace('WHERE', 'AND', $qualifiedTenantScope);
        }
        
        $sql = "SELECT 
                    u.*, 
                    up.*,
                    e.employee_id, e.hire_date, e.employment_type, e.current_salary_ghs,
                    e.payment_method, e.bank_name, e.bank_account_number, e.bank_branch, e.bank_account_name, -- Added new bank fields
                    p.id AS position_id, p.title AS position_title,
                    d.id AS department_id, d.name AS department_name
                FROM users u
                JOIN user_profiles up ON u.id = up.user_id
                JOIN employees e ON u.id = e.user_id
                LEFT JOIN positions p ON e.current_position_id = p.id
                LEFT JOIN departments d ON p.department_id = d.id
                WHERE u.id = :user_id {$scopeCondition}";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            // Log failure in complex profile read
            Log::error("Employee Profile READ failed for target User {$userId}. Error: " . $e->getMessage(), [
                'sql' => $sql,
                'acting_user_id' => Auth::userId()
            ]);
            throw $e;
        }
    }

    /**
     * Counts the total number of active employees for a given tenant.
     * Active employees are those without a termination_date.
     *
     * @param int $tenantId The ID of the tenant.
     * @return int The count of active employees.
     */
    public function getEmployeeCount(int $tenantId): int
    {
        $sql = "SELECT COUNT(user_id) FROM employees WHERE tenant_id = :tenant_id AND termination_date IS NULL";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tenant_id' => $tenantId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            // Log failure in simple count operation
            Log::error("Employee COUNT failed for Tenant {$tenantId}. Error: " . $e->getMessage(), [
                'acting_user_id' => Auth::userId()
            ]);
            // Return 0 as a safe default for a count failure
            return 0; 
        }
    }


    /**
     * Checks if a given SSNIT or TIN number is already in use across all tenants.
     * This is used for pre-validation before creating a new user_profile record.
     * * @param string $number The SSNIT or TIN number.
     * @param string $type The column name ('ssnit_number' or 'tin_number').
     * @return bool True if the number is already in use.
     */
    public function isComplianceNumberInUse(string $number, string $type): bool
    {
        if (!in_array($type, ['ssnit_number', 'tin_number'])) {
            // Log security or parameter error
            return true; // Fail safe
        }

        // Search the user_profiles table
        $sql = "SELECT COUNT(user_id) 
                FROM user_profiles 
                WHERE {$type} = :number";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':number', $number, \PDO::PARAM_STR);
            $stmt->execute();
            
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            Log::error("Compliance number check failed ({$type}). Error: " . $e->getMessage());
            // Throwing the exception would crash, so return true to fail-safe and prevent duplicate
            return true; 
        }
    }



    /**
     * Checks if a given employee ID is already in use within the current tenant.
     * This uses the tenant scoping already set up on the model.
     * * @param string $employeeId The employee ID to check.
     * @return bool True if the employee ID is already in use by this tenant.
     */
    public function isEmployeeIdInUse(string $employeeId): bool
    {
        // $this->table is 'employees'
        // $this->currentTenantId is set by the base Model's constructor or logic
        $sql = "SELECT COUNT(user_id) 
                FROM {$this->table} 
                WHERE employee_id = :employee_id 
                AND tenant_id = :tenant_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':employee_id', $employeeId, \PDO::PARAM_STR);
            $stmt->bindParam(':tenant_id', $this->currentTenantId, \PDO::PARAM_INT);
            $stmt->execute();
            
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            Log::error("Employee ID check failed. Error: " . $e->getMessage());
            // Return true to fail-safe and prevent a database crash attempt
            return true; 
        }
    }

    /**
     * Retrieves all active employees for a given tenant who are eligible for payroll.
     *
     * @param int $tenantId
     * @return array An array of employee records.
     */
    public function getAllPayrollEligibleEmployees(int $tenantId): array
    {
        $sql = "SELECT 
                    e.user_id, e.employee_id, e.hire_date, e.employment_type,
                    e.current_salary_ghs, u.first_name, u.last_name, u.email
                FROM employees e
                JOIN users u ON e.user_id = u.id
                WHERE e.tenant_id = :tenant_id AND u.is_active = TRUE AND e.is_payroll_eligible = TRUE
                ORDER BY u.last_name, u.first_name";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tenant_id' => $tenantId]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve payroll eligible employees for Tenant {$tenantId}. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves the most recently hired employees for a given tenant.
     *
     * @param int $tenantId The ID of the tenant.
     * @param int $limit The maximum number of employees to retrieve.
     * @return array An array of employee records.
     */
    public function getRecentEmployees(int $tenantId, int $limit = 5): array
    {
        $sql = "SELECT 
                    e.user_id, e.employee_id, e.hire_date,
                    u.first_name, u.last_name, u.email,
                    d.name as department_name
                FROM employees e
                JOIN users u ON e.user_id = u.id
                LEFT JOIN positions p ON e.current_position_id = p.id
                LEFT JOIN departments d ON p.department_id = d.id
                WHERE e.tenant_id = :tenant_id AND u.is_active = TRUE
                ORDER BY e.hire_date DESC
                LIMIT :limit";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve recent employees for Tenant {$tenantId}. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves employees with upcoming work anniversaries.
     *
     * @param int $tenantId The ID of the tenant.
     * @param int $days The number of days to look ahead for anniversaries.
     * @return array An array of employee records.
     */
    public function getUpcomingAnniversaries(int $tenantId, int $days = 30): array
    {
        $sql = "SELECT 
                    e.user_id, e.hire_date,
                    u.first_name, u.last_name
                FROM employees e
                JOIN users u ON e.user_id = u.id
                WHERE e.tenant_id = :tenant_id AND u.is_active = TRUE
                AND (
                    DATE_ADD(e.hire_date, INTERVAL YEAR(CURDATE()) - YEAR(e.hire_date) YEAR) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                    OR
                    DATE_ADD(e.hire_date, INTERVAL YEAR(CURDATE()) - YEAR(e.hire_date) + 1 YEAR) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                )
                ORDER BY MONTH(e.hire_date), DAY(e.hire_date) ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_INT);
            $stmt->bindValue(':days', $days, \PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve upcoming anniversaries for Tenant {$tenantId}. Error: " . $e->getMessage());
            return [];
        }
    }
}