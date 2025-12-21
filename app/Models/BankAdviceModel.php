<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Core\Log;

class BankAdviceModel extends Model
{
    public function __construct()
    {
        parent::__construct('bank_advice');
    }

    public function getAdviceByPeriod(int $payrollPeriodId, int $tenantId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE payroll_period_id = :payroll_period_id 
                AND tenant_id = :tenant_id
                ORDER BY employee_name";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':payroll_period_id' => $payrollPeriodId,
                ':tenant_id' => $tenantId,
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            Log::error("Failed to retrieve Bank Advice for period {$payrollPeriodId} (tenant {$tenantId}). Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves Bank Advice data for a specific payroll period, tenant, and department.
     *
     * @param int $tenantId
     * @param int $departmentId
     * @param int $payrollPeriodId
     * @return array An array of Bank Advice records.
     */
    public function getAdviceByDepartmentAndPeriod(int $tenantId, int $departmentId, int $payrollPeriodId): array
    {
        $sql = "SELECT ba.*
                FROM {$this->table} ba
                JOIN employees e ON ba.user_id = e.user_id
                JOIN positions pos ON e.current_position_id = pos.id
                WHERE ba.payroll_period_id = :payroll_period_id 
                AND ba.tenant_id = :tenant_id
                AND pos.department_id = :department_id
                ORDER BY ba.employee_name";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':payroll_period_id' => $payrollPeriodId,
                ':tenant_id' => $tenantId,
                ':department_id' => $departmentId,
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            Log::error("Failed to retrieve Bank Advice for department {$departmentId}, period {$payrollPeriodId} (tenant {$tenantId}). Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Generic method to create a new record, enforcing tenant scope.
     * @param array $data The data to insert.
     * @return int The ID of the newly created record.
     * @throws \PDOException If the insert operation fails.
     */
    public function create(array $data): int
    {
        // Automatically add tenant_id if not super admin and not a no-scope table
        if (!$this->isSuperAdmin && !$this->noTenantScope && !isset($data['tenant_id'])) {
            $data['tenant_id'] = $this->currentTenantId;
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            return (int)$this->db->lastInsertId();
        } catch (\PDOException $e) {
            Log::error("Database INSERT query failed in Model '{$this->table}'. Error: " . $e->getMessage(), [
                'user_id' => Auth::userId(),
                'tenant_id' => $this->currentTenantId,
                'sql' => $sql,
                'data' => $data
            ]);
            throw $e;
        }
    }
}
