<?php 
// app/Models/EmployeeModel.php

declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;

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
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
        
        $stmt = $this->db->prepare($sql);
        
        $defaults = [
            'bank_name' => null, 
            'bank_account_number' => null, 
        ];
        $finalData = array_merge($defaults, $data);
        
        return $stmt->execute($finalData);
    }

    /**
     * Updates an existing employee record by user ID.
     * * @param int $userId The ID of the user whose employee record to update.
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
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($bindParams);
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

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}