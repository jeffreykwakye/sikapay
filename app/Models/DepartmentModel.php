<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log; 
use Jeffrey\Sikapay\Core\Auth;
use \PDO;
use \PDOException;

class DepartmentModel extends Model
{
    public function __construct()
    {
        parent::__construct('departments');
    }

    // ----------------------------------------------------------------
    // READ
    // ----------------------------------------------------------------

    public function getAllByTenant(): array
    {
        try {
            $whereClause = $this->getTenantScope([]);
            
            $sql = "SELECT id, name, created_at FROM {$this->table} 
                    {$whereClause} 
                    ORDER BY name ASC";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            Log::error("DB Read Error in DepartmentModel::getAllByTenant. Error: " . $e->getMessage(), [
                'user_id' => Auth::userId(), 
                'tenant_id' => $this->currentTenantId,
                'sql' => $sql
            ]);
            throw $e;
        }
    }

    // ----------------------------------------------------------------
    // CREATE
    // ----------------------------------------------------------------

    public function create(string $name): int
    {
        if (!$this->currentTenantId) {
             throw new \Exception("Cannot create department: Missing tenant context.");
        }
        
        $sql = "INSERT INTO {$this->table} (tenant_id, name) 
                VALUES (:tenant_id, :name)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $this->currentTenantId,
                ':name' => $name,
            ]);
            
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            Log::error("DB Create Error in DepartmentModel::create. Error: " . $e->getMessage(), [
                'user_id' => Auth::userId(), 
                'tenant_id' => $this->currentTenantId,
                'input_name' => $name
            ]);
            throw $e;
        }
    }

    // ----------------------------------------------------------------
    // UPDATE
    // ----------------------------------------------------------------

    public function update(int $id, string $name): bool
    {
        if (!$this->currentTenantId && !$this->isSuperAdmin) {
             throw new \Exception("Cannot update department: Missing tenant context.");
        }

        $sql = "UPDATE {$this->table} SET name = :name 
                WHERE id = :id";
        
        if (!$this->isSuperAdmin) {
            $sql .= " AND tenant_id = :tenant_id";
        }
        
        $params = [
            ':name' => $name,
            ':id' => $id,
        ];

        if (!$this->isSuperAdmin) {
            $params[':tenant_id'] = $this->currentTenantId;
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Log::error("DB Update Error in DepartmentModel::update (ID: {$id}). Error: " . $e->getMessage(), [
                'user_id' => Auth::userId(), 
                'tenant_id' => $this->currentTenantId,
                'sql_params' => $params
            ]);
            throw $e;
        }
    }

    // ----------------------------------------------------------------
    // DELETE
    // ----------------------------------------------------------------

    public function delete(int $id): bool
    {
        if (!$this->currentTenantId && !$this->isSuperAdmin) {
             throw new \Exception("Cannot delete department: Missing tenant context.");
        }

        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        
        if (!$this->isSuperAdmin) {
            $sql .= " AND tenant_id = :tenant_id";
        }

        $params = [':id' => $id];
        
        if (!$this->isSuperAdmin) {
            $params[':tenant_id'] = $this->currentTenantId;
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Log::error("DB Delete Error in DepartmentModel::delete (ID: {$id}). Error: " . $e->getMessage(), [
                'user_id' => Auth::userId(), 
                'tenant_id' => $this->currentTenantId,
                'sql_params' => $params
            ]);
            throw $e;
        }
    }
    
    // ----------------------------------------------------------------
    // DEPENDENCY CHECK
    // ----------------------------------------------------------------

    public function hasAssociatedEmployees(int $departmentId): bool
    {
        if (!$this->currentTenantId) {
             throw new \Exception("Missing tenant context for employee check.");
        }

        // JOIN 'employees' with 'positions' to link the employee to the department.
        // We only count employees whose position's department_id matches the one being deleted.
        $sql = "SELECT COUNT(e.user_id) 
                FROM employees e
                JOIN positions p ON e.current_position_id = p.id
                WHERE p.department_id = :dept_id 
                AND e.tenant_id = :tenant_id"; // Tenant scoping ensures integrity
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':dept_id' => $departmentId, 
                ':tenant_id' => $this->currentTenantId
            ]);
            
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            Log::error("DB Check Error in DepartmentModel::hasAssociatedEmployees (ID: {$departmentId}). Error: " . $e->getMessage(), [
                'user_id' => Auth::userId(), 
                'tenant_id' => $this->currentTenantId,
            ]);
            throw $e;
        }
    }

    /**
     * Counts all departments for the current tenant.
     *
     * @return int The count of departments.
     */
    public function countAllByTenant(): int
    {
        try {
            $whereClause = $this->getTenantScope([]);
            
            $sql = "SELECT COUNT(id) FROM {$this->table} {$whereClause}";
            
            $stmt = $this->db->query($sql);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            Log::error("DB Count Error in DepartmentModel::countAllByTenant. Error: " . $e->getMessage(), [
                'user_id' => Auth::userId(), 
                'tenant_id' => $this->currentTenantId,
                'sql' => $sql
            ]);
            throw $e;
        }
    }

    /**
     * Retrieves the number of employees in each department for a given tenant.
     *
     * @param int $tenantId The ID of the tenant.
     * @return array An array of departments with their employee counts.
     */
    public function getEmployeeCountPerDepartment(int $tenantId): array
    {
        $sql = "SELECT 
                    d.name as department_name,
                    COUNT(e.user_id) as employee_count
                FROM departments d
                LEFT JOIN positions p ON d.id = p.department_id
                LEFT JOIN employees e ON p.id = e.current_position_id AND e.tenant_id = d.tenant_id
                WHERE d.tenant_id = :tenant_id
                GROUP BY d.name
                ORDER BY employee_count DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tenant_id' => $tenantId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve employee count per department for tenant {$tenantId}. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Counts all departments for a specific tenant ID.
     *
     * @param int $tenantId The ID of the tenant.
     * @return int The count of departments.
     */
    public function countAllByTenantId(int $tenantId): int
    {
        $sql = "SELECT COUNT(id) FROM {$this->table} WHERE tenant_id = :tenant_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tenant_id' => $tenantId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            Log::error("DB Count Error in DepartmentModel::countAllByTenantId for tenant {$tenantId}. Error: " . $e->getMessage());
            return 0; 
        }
    }

}