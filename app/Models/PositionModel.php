<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log; 
use Jeffrey\Sikapay\Core\Auth;
use \PDO;
use \PDOException;

class PositionModel extends Model
{
    public function __construct()
    {
        parent::__construct('positions');
    }

    // ----------------------------------------------------------------
    // READ
    // ----------------------------------------------------------------

    /**
     * Retrieves all positions for the current tenant, joining with department name.
     */
    public function getAllByTenant(): array
    {
        try {
            $whereClause = "";
            
            // Explicitly build the tenant scope using the alias 'p' for the positions table.
            if (!$this->isSuperAdmin && $this->currentTenantId !== null) {
                // Safely ensure the WHERE clause is aliased
                $whereClause = "WHERE p.tenant_id = {$this->currentTenantId}";
            }

            $sql = "SELECT 
                        p.id, p.title, p.department_id, p.created_at,
                        d.name AS department_name
                    FROM {$this->table} p
                    LEFT JOIN departments d ON p.department_id = d.id
                    {$whereClause} 
                    ORDER BY p.title ASC";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            Log::error("DB Read Error in PositionModel::getAllByTenant. Error: " . $e->getMessage(), [
                'user_id' => Auth::userId(), 
                'tenant_id' => $this->currentTenantId,
                'sql' => $sql
            ]);
            throw $e;
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $sql = "SELECT id, title, department_id FROM {$this->table} WHERE id = :id";
            
            if (!$this->isSuperAdmin && !$this->noTenantScope) {
                 $sql .= " AND tenant_id = :tenant_id";
            }

            $stmt = $this->db->prepare($sql);
            $params = [':id' => $id];
            
            if (!$this->isSuperAdmin && !$this->noTenantScope) {
                $params[':tenant_id'] = $this->currentTenantId;
            }
            
            $stmt->execute($params);
            $position = $stmt->fetch(PDO::FETCH_ASSOC);

            return $position ?: null;
        } catch (PDOException $e) {
            Log::error("DB Read Error in PositionModel::getById. Error: " . $e->getMessage(), [
                'user_id' => Auth::userId(), 
                'tenant_id' => $this->currentTenantId,
            ]);
            throw $e;
        }
    }

    // ----------------------------------------------------------------
    // CREATE
    // ----------------------------------------------------------------

    public function create(string $title, ?int $departmentId): int
    {
        if (!$this->currentTenantId) {
             throw new \Exception("Cannot create position: Missing tenant context.");
        }
        
        $sql = "INSERT INTO {$this->table} (tenant_id, department_id, title) 
                VALUES (:tenant_id, :department_id, :title)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $this->currentTenantId,
                ':department_id' => $departmentId,
                ':title' => $title,
            ]);
            
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            Log::error("DB Write Error in PositionModel::create. Error: " . $e->getMessage(), [
                'user_id' => Auth::userId(), 
                'tenant_id' => $this->currentTenantId,
                'title' => $title,
                'department_id' => $departmentId
            ]);
            throw $e;
        }
    }

    // ----------------------------------------------------------------
    // UPDATE
    // ----------------------------------------------------------------

    public function update(int $id, string $title, ?int $departmentId): bool
    {
        if (!$this->currentTenantId && !$this->isSuperAdmin) {
            throw new \Exception("Cannot update position: Missing tenant context.");
        }

        $sql = "UPDATE {$this->table} SET title = :title, department_id = :department_id 
                WHERE id = :id";
        
        if (!$this->isSuperAdmin) {
            $sql .= " AND tenant_id = :tenant_id";
        }
        
        $params = [
            ':title' => $title,
            ':department_id' => $departmentId,
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
            Log::error("DB Update Error in PositionModel::update (ID: {$id}). Error: " . $e->getMessage(), [
                'user_id' => Auth::userId(), 
                'tenant_id' => $this->currentTenantId,
                'params' => $params
            ]);
            throw $e;
        }
    }

    // ----------------------------------------------------------------
    // DEPENDENCY CHECK (Contents remain the same)
    // ----------------------------------------------------------------

    public function hasAssociatedEmployees(int $positionId): bool
    {
        if (!$this->currentTenantId) {
             throw new \Exception("Missing tenant context for employee check.");
        }

        $sql = "SELECT COUNT(*) FROM employees 
                WHERE current_position_id = :position_id AND tenant_id = :tenant_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':position_id' => $positionId, 
                ':tenant_id' => $this->currentTenantId
            ]);
            
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            Log::error("DB Check Error in PositionModel::hasAssociatedEmployees (ID: {$positionId}). Error: " . $e->getMessage(), [
                'user_id' => Auth::userId(), 
                'tenant_id' => $this->currentTenantId,
            ]);
            throw $e;
        }
    }

    // ----------------------------------------------------------------
    // DELETE (EXPLICITLY ADDED & SCOPED)
    // ----------------------------------------------------------------

    /**
     * Deletes a position by ID, scoped by the current tenant.
     * This method is required because the base Model::delete is missing.
     */
    public function delete(int $id): bool
    {
        if (!$this->currentTenantId && !$this->isSuperAdmin) {
             throw new \Exception("Cannot delete position: Missing tenant context.");
        }
        
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $params = [':id' => $id];

        // Apply tenant scope for non-Super Admins
        if (!$this->isSuperAdmin) {
            $sql .= " AND tenant_id = :tenant_id";
            $params[':tenant_id'] = $this->currentTenantId;
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Log::error("DB Delete Error in PositionModel::delete (ID: {$id}). Error: " . $e->getMessage(), [
                'user_id' => Auth::userId(), 
                'tenant_id' => $this->currentTenantId,
            ]);
            throw $e;
        }
    }
}