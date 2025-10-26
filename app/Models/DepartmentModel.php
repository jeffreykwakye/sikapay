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
}