<?php

declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class PayslipModel extends Model
{
    public function __construct()
    {
        parent::__construct('payslips');
    }

    /**
     * Saves a generated payslip record.
     *
     * @param array $data Payslip data including user_id, tenant_id, period_id, gross_pay, net_pay, etc.
     * @return int The ID of the new payslip record, or 0 on failure.
     */
    public function createPayslip(array $data): int
    {
        $sql = "INSERT INTO {$this->table} (
                    user_id, tenant_id, payroll_period_id, 
                    gross_pay, total_deductions, net_pay, 
                    paye_amount, ssnit_employee_amount, ssnit_employer_amount, 
                    payslip_path, generated_at
                ) VALUES (
                    :user_id, :tenant_id, :payroll_period_id, 
                    :gross_pay, :total_deductions, :net_pay, 
                    :paye_amount, :ssnit_employee_amount, :ssnit_employer_amount, 
                    :payslip_path, NOW()
                )";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':tenant_id' => $data['tenant_id'],
                ':payroll_period_id' => $data['payroll_period_id'],
                ':gross_pay' => $data['gross_pay'],
                ':total_deductions' => $data['total_deductions'],
                ':net_pay' => $data['net_pay'],
                ':paye_amount' => $data['paye_amount'],
                ':ssnit_employee_amount' => $data['ssnit_employee_amount'],
                ':ssnit_employer_amount' => $data['ssnit_employer_amount'],
                ':payslip_path' => $data['payslip_path'] ?? null,
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            Log::error("Failed to create payslip record for user {$data['user_id']} in period {$data['payroll_period_id']}. Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Deletes all payslips for a given payroll period and tenant.
     *
     * @param int $payrollPeriodId
     * @param int $tenantId
     * @return bool True on success, false otherwise.
     */
    public function deletePayslipsForPeriod(int $payrollPeriodId, int $tenantId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE payroll_period_id = :payroll_period_id AND tenant_id = :tenant_id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':payroll_period_id' => $payrollPeriodId,
                ':tenant_id' => $tenantId,
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Log::error("Failed to delete payslips for period {$payrollPeriodId} (tenant {$tenantId}). Error: " . $e->getMessage());
            return false;
        }
    }
}
