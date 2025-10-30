<?php

declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class EmployeePayrollDetailsModel extends Model
{
    public function __construct()
    {
        parent::__construct('employee_payroll_details');
    }

    /**
     * Retrieves all payroll details (allowances, deductions) for a specific employee.
     *
     * @param int $userId The user ID of the employee.
     * @param int $tenantId The ID of the tenant.
     * @return array An array of employee payroll detail records.
     */
    public function getDetailsForEmployee(int $userId, int $tenantId): array
    {
        $sql = "SELECT allowance_type, amount, is_taxable, effective_date, end_date 
                FROM {$this->table} 
                WHERE user_id = :user_id AND tenant_id = :tenant_id
                ORDER BY effective_date DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':tenant_id' => $tenantId,
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve payroll details for user {$userId} (tenant {$tenantId}). Error: " . $e->getMessage());
            return [];
        }
    }
}
