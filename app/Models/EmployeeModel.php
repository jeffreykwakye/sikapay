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
     * Retrieves all employees for the current tenant.
     */
    public function getAllEmployees(): array
    {
        // Check if tenant scoping is applicable (it should be for this model)
        $whereClause = $this->getTenantScope(); // Get the "WHERE tenant_id = X" clause
        
        $sql = "SELECT 
                  e.user_id, e.employee_id, e.hire_date, e.employment_type,
                  e.current_salary_ghs,
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
        $sql = "INSERT INTO {$this->table} 
                  (user_id, tenant_id, employee_id, hire_date, current_position_id, employment_type, current_salary_ghs, payment_method, bank_name, bank_account_number) 
                  VALUES 
                  (:user_id, :tenant_id, :employee_id, :hire_date, :current_position_id, :employment_type, :current_salary_ghs, :payment_method, :bank_name, :bank_account_number)";
        
        $defaults = [
            'bank_name' => null, 
            'bank_account_number' => null, 
        ];
        $finalData = array_merge($defaults, $data);
        
        try {
            $stmt = $this->db->prepare($sql);
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
        
        // Note: The primary key for this update is the user_id column
        // We rely on the Model's tenant scope being enforced higher up, or in the base model's logic.
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
            // Replace the initial 'WHERE' with 'AND' if scope is needed
            $scopeCondition = str_ireplace('WHERE', 'AND', trim($tenantScope));
        }
        
        $sql = "SELECT 
                  u.*, 
                  up.*,
                  e.employee_id, e.hire_date, e.employment_type, e.current_salary_ghs,
                  e.payment_method, e.bank_name, e.bank_account_number,
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
}