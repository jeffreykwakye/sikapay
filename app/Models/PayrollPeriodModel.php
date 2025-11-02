<?php

declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class PayrollPeriodModel extends Model
{
    public function __construct()
    {
        parent::__construct('payroll_periods');
    }

    /**
     * Retrieves the current active payroll period for a tenant.
     *
     * @param int $tenantId
     * @return array|null The current payroll period record, or null if not found.
     */
    public function getCurrentPeriod(int $tenantId): ?array
    {
        $sql = "SELECT id, period_name, start_date, end_date, payment_date, is_closed FROM {$this->table} 
                WHERE tenant_id = :tenant_id AND is_closed = FALSE
                ORDER BY start_date DESC
                LIMIT 1";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $tenantId,
            ]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            Log::error("Failed to retrieve current payroll period for tenant {$tenantId}. Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Creates a new payroll period.
     *
     * @param int $tenantId
     * @param string $periodName
     * @param string $startDate
     * @param string $endDate
     * @param string|null $paymentDate
     * @return int The ID of the new payroll period, or 0 on failure.
     */
    public function createPeriod(int $tenantId, string $periodName, string $startDate, string $endDate, ?string $paymentDate): int
    {
        $sql = "INSERT INTO {$this->table} (
                    tenant_id, period_name, start_date, end_date, payment_date
                ) VALUES (
                    :tenant_id, :period_name, :start_date, :end_date, :payment_date
                )";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $tenantId,
                ':period_name' => $periodName,
                ':start_date' => $startDate,
                ':end_date' => $endDate,
                ':payment_date' => $paymentDate,
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            Log::error("Failed to create payroll period '{$periodName}' for tenant {$tenantId}. Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Marks a payroll period as closed.
     *
     * @param int $periodId
     * @param int $tenantId
     * @return bool True on success, false otherwise.
     */
    public function markPeriodAsClosed(int $periodId, int $tenantId): bool
    {
        $sql = "UPDATE {$this->table} SET is_closed = TRUE, updated_at = NOW() 
                WHERE id = :id AND tenant_id = :tenant_id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $periodId,
                ':tenant_id' => $tenantId,
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Log::error("Failed to mark payroll period {$periodId} as closed for tenant {$tenantId}. Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves all payroll periods for a tenant.
     *
     * @param int $tenantId
     * @return array An array of payroll period records.
     */
    public function getAllPeriods(int $tenantId): array
    {
        $sql = "SELECT id, period_name, start_date, end_date, payment_date, is_closed FROM {$this->table} 
                WHERE tenant_id = :tenant_id
                ORDER BY start_date DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $tenantId,
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve all payroll periods for tenant {$tenantId}. Error: " . $e->getMessage());
            return [];
        }
    }

    public function getLatestClosedPeriod(int $tenantId): ?array
    {
        $sql = "SELECT id, period_name, start_date, end_date, payment_date, is_closed FROM {$this->table} 
                WHERE tenant_id = :tenant_id AND is_closed = TRUE
                ORDER BY end_date DESC
                LIMIT 1";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $tenantId,
            ]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            Log::error("Failed to retrieve latest closed payroll period for tenant {$tenantId}. Error: " . $e->getMessage());
            return null;
        }
    }
}
