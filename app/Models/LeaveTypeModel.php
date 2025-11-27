<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class LeaveTypeModel extends Model
{
    public function __construct()
    {
        parent::__construct('leave_types');
    }

    /**
     * Retrieves all leave types for a given tenant.
     *
     * @param int $tenantId
     * @return array An array of leave type records.
     */
    public function getAllByTenant(int $tenantId): array
    {
        $sql = "SELECT id, name, default_days, is_accrued, is_active FROM {$this->table} 
                WHERE tenant_id = :tenant_id
                ORDER BY name ASC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tenant_id' => $tenantId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve leave types for tenant {$tenantId}. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Creates a new leave type for a tenant.
     *
     * @param int $tenantId
     * @param array $data Contains name, default_days, is_accrued, is_active.
     * @return int The ID of the new leave type, or 0 on failure.
     */
    public function create(int $tenantId, array $data): int
    {
        $sql = "INSERT INTO {$this->table} (
                    tenant_id, name, default_days, is_accrued, is_active
                ) VALUES (
                    :tenant_id, :name, :default_days, :is_accrued, :is_active
                )";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $tenantId,
                ':name' => $data['name'],
                ':default_days' => $data['default_days'],
                ':is_accrued' => $data['is_accrued'],
                ':is_active' => $data['is_active'],
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            Log::error("Failed to create leave type '{$data['name']}' for tenant {$tenantId}. Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Updates an existing leave type.
     *
     * @param int $id The ID of the leave type to update.
     * @param int $tenantId
     * @param array $data Contains name, default_days, is_accrued, is_active.
     * @return bool True on success, false otherwise.
     */
    public function update(int $id, int $tenantId, array $data): bool
    {
        $sql = "UPDATE {$this->table} SET 
                    name = :name, 
                    default_days = :default_days, 
                    is_accrued = :is_accrued, 
                    is_active = :is_active,
                    updated_at = NOW()
                WHERE id = :id AND tenant_id = :tenant_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':tenant_id' => $tenantId,
                ':name' => $data['name'],
                ':default_days' => $data['default_days'],
                ':is_accrued' => $data['is_accrued'],
                ':is_active' => $data['is_active'],
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Log::error("Failed to update leave type {$id} for tenant {$tenantId}. Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a leave type.
     *
     * @param int $id The ID of the leave type to delete.
     * @param int $tenantId
     * @return bool True on success, false otherwise.
     */
    public function delete(int $id, int $tenantId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id AND tenant_id = :tenant_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':tenant_id' => $tenantId,
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Log::error("Failed to delete leave type {$id} for tenant {$tenantId}. Error: " . $e->getMessage());
            return false;
        }
    }
}
