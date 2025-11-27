<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class LeaveBalanceModel extends Model
{
    public function __construct()
    {
        parent::__construct('leave_balances');
    }

    /**
     * Retrieves the leave balance for a specific user and leave type.
     *
     * @param int $userId
     * @param int $leaveTypeId
     * @return array|null The leave balance record, or null if not found.
     */
    public function getBalance(int $userId, int $leaveTypeId): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id AND leave_type_id = :leave_type_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':leave_type_id' => $leaveTypeId,
            ]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            Log::error("Failed to retrieve leave balance for user {$userId}, type {$leaveTypeId}. Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieves all leave balances for a specific user.
     *
     * @param int $userId
     * @param int $tenantId
     * @return array An array of leave balance records.
     */
    public function getAllBalancesByUser(int $userId, int $tenantId): array
    {
        $sql = "SELECT lb.*, lt.name as leave_type_name
                FROM {$this->table} lb
                JOIN leave_types lt ON lb.leave_type_id = lt.id
                WHERE lb.user_id = :user_id AND lb.tenant_id = :tenant_id
                ORDER BY lt.name ASC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':tenant_id' => $tenantId,
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve all leave balances for user {$userId} (tenant {$tenantId}). Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Creates or updates a leave balance record (UPSERT).
     *
     * @param int $userId
     * @param int $tenantId
     * @param int $leaveTypeId
     * @param float $amount The amount to add/set to the balance.
     * @return bool True on success, false otherwise.
     */
    public function updateBalance(int $userId, int $tenantId, int $leaveTypeId, float $amount): bool
    {
        $sql = "INSERT INTO {$this->table} (user_id, tenant_id, leave_type_id, balance)
                VALUES (:user_id, :tenant_id, :leave_type_id, :balance)
                ON DUPLICATE KEY UPDATE balance = balance + :balance_add_amount, last_updated = NOW()";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':tenant_id' => $tenantId,
                ':leave_type_id' => $leaveTypeId,
                ':balance' => $amount, // Initial balance for INSERT
                ':balance_add_amount' => $amount, // Amount to add for UPDATE
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Log::error("Failed to update leave balance for user {$userId}, type {$leaveTypeId} (tenant {$tenantId}). Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sets a leave balance to a specific amount.
     *
     * @param int $userId
     * @param int $tenantId
     * @param int $leaveTypeId
     * @param float $amount The amount to set the balance to.
     * @return bool True on success, false otherwise.
     */
    public function setBalance(int $userId, int $tenantId, int $leaveTypeId, float $amount): bool
    {
        $sql = "INSERT INTO {$this->table} (user_id, tenant_id, leave_type_id, balance)
                VALUES (:user_id, :tenant_id, :leave_type_id, :balance)
                ON DUPLICATE KEY UPDATE balance = :balance, last_updated = NOW()";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':tenant_id' => $tenantId,
                ':leave_type_id' => $leaveTypeId,
                ':balance' => $amount,
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Log::error("Failed to set leave balance for user {$userId}, type {$leaveTypeId} (tenant {$tenantId}). Error: " . $e->getMessage());
            return false;
        }
    }
}
