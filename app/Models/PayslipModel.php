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
        // Ensure generated_at is set if not provided
        if (!isset($data['generated_at'])) {
            $data['generated_at'] = date('Y-m-d H:i:s');
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            Log::error("Failed to create payslip record for user {$data['user_id']} in period {$data['payroll_period_id']}. Error: " . $e->getMessage(), [
                'sql' => $sql,
                'data' => $data
            ]);
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

    /**
     * Retrieves all payslips for a specific payroll period and tenant.
     *
     * @param int $payrollPeriodId
     * @param int $tenantId
     * @return array An array of payslip records.
     */
    public function getPayslipsByPeriod(int $payrollPeriodId, int $tenantId): array
    {
        $sql = "SELECT 
                    p.id, p.user_id, p.gross_pay, p.net_pay, p.paye_amount, p.ssnit_employee_amount, p.ssnit_employer_amount, p.payslip_path, p.total_taxable_income,
                    u.first_name, u.last_name, u.email, e.employee_id,
                    up.tin_number
                FROM {$this->table} p
                JOIN users u ON p.user_id = u.id
                JOIN employees e ON p.user_id = e.user_id
                LEFT JOIN user_profiles up ON p.user_id = up.user_id
                WHERE p.payroll_period_id = :payroll_period_id AND p.tenant_id = :tenant_id
                ORDER BY u.last_name, u.first_name";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':payroll_period_id' => $payrollPeriodId,
                ':tenant_id' => $tenantId,
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve payslips for period {$payrollPeriodId} (tenant {$tenantId}). Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves aggregated payroll data for a specific period.
     *
     * @param int $payrollPeriodId
     * @param int $tenantId
     * @return array An array of aggregated data.
     */
    public function getAggregatedPayslipData(int $payrollPeriodId, int $tenantId): array
    {
        $sql = "SELECT 
                    SUM(gross_pay) as total_gross_pay,
                    SUM(net_pay) as total_net_pay,
                    SUM(paye_amount) as total_paye,
                    SUM(ssnit_employer_amount) as total_employer_ssnit
                FROM {$this->table}
                WHERE payroll_period_id = :payroll_period_id AND tenant_id = :tenant_id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':payroll_period_id' => $payrollPeriodId,
                ':tenant_id' => $tenantId,
            ]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: [];
        } catch (PDOException $e) {
            Log::error("Failed to retrieve aggregated payslip data for period {$payrollPeriodId} (tenant {$tenantId}). Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves the total payroll cost for the last specified number of months.
     *
     * @param int $tenantId The ID of the tenant.
     * @param int $months The number of months of history to retrieve.
     * @return array An array of payroll history data.
     */
    public function getPayrollHistory(int $tenantId, int $months = 6): array
    {
        $sql = "SELECT 
                    pp.period_name as month,
                    SUM(p.gross_pay) as total_gross,
                    SUM(p.net_pay) as total_net,
                    SUM(p.paye_amount) as total_paye
                FROM payslips p
                JOIN payroll_periods pp ON p.payroll_period_id = pp.id
                WHERE p.tenant_id = :tenant_id
                AND pp.is_closed = TRUE
                AND pp.start_date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY pp.id, pp.period_name, pp.start_date
                ORDER BY pp.start_date ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_INT);
            $stmt->bindValue(':months', $months, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve payroll history for tenant {$tenantId}. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves aggregated payroll statistics for a specific period, grouped by department.
     *
     * @param int $tenantId The ID of the tenant.
     * @param int $payrollPeriodId The ID of the payroll period.
     * @return array An array of aggregated data, keyed by department ID.
     */
    public function getAggregatedPayrollStatsByDepartment(int $tenantId, int $payrollPeriodId): array
    {
        $sql = "SELECT 
                    pos.department_id,
                    SUM(ps.gross_pay) as total_gross_pay,
                    SUM(ps.net_pay) as total_net_pay,
                    SUM(ps.paye_amount) as total_paye
                FROM payslips ps
                JOIN employees e ON ps.user_id = e.user_id
                JOIN positions pos ON e.current_position_id = pos.id
                WHERE ps.tenant_id = :tenant_id AND ps.payroll_period_id = :payroll_period_id
                GROUP BY pos.department_id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $tenantId,
                ':payroll_period_id' => $payrollPeriodId
            ]);
            
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Re-key the array by department_id for easier lookup
            $keyedResults = [];
            foreach ($results as $row) {
                $keyedResults[$row['department_id']] = $row;
            }
            
            return $keyedResults;
        } catch (PDOException $e) {
            Log::error("Failed to retrieve aggregated payroll stats by department for period {$payrollPeriodId} (tenant {$tenantId}). Error: " . $e->getMessage());
            return [];
        }
    }

    public function getBankAdviceDataByPeriod(int $payrollPeriodId, int $tenantId): array
    {
        $sql = "SELECT 
                    u.first_name, 
                    u.last_name,
                    e.bank_name,
                    e.bank_branch,
                    e.bank_account_number,
                    e.bank_account_name,
                    p.net_pay
                FROM {$this->table} p
                JOIN users u ON p.user_id = u.id
                JOIN employees e ON p.user_id = e.user_id
                WHERE p.payroll_period_id = :payroll_period_id AND p.tenant_id = :tenant_id
                ORDER BY u.last_name, u.first_name";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':payroll_period_id' => $payrollPeriodId,
                ':tenant_id' => $tenantId,
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve bank advice data for period {$payrollPeriodId} (tenant {$tenantId}). Error: " . $e->getMessage());
            return [];
        }
    }

    public function getSsnitReportDataByPeriod(int $payrollPeriodId, int $tenantId): array
    {
        $sql = "SELECT 
                    u.first_name, 
                    u.last_name,
                    up.ssnit_number,
                    e.current_salary_ghs as basic_salary,
                    p.ssnit_employee_amount,
                    p.ssnit_employer_amount
                FROM {$this->table} p
                JOIN users u ON p.user_id = u.id
                JOIN employees e ON p.user_id = e.user_id
                LEFT JOIN user_profiles up ON p.user_id = up.user_id
                WHERE p.payroll_period_id = :payroll_period_id 
                AND p.tenant_id = :tenant_id
                ORDER BY u.last_name, u.first_name";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':payroll_period_id' => $payrollPeriodId,
                ':tenant_id' => $tenantId,
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve SSNIT report data for period {$payrollPeriodId} (tenant {$tenantId}). Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves all payslips for a specific user across all payroll periods.
     *
     * @param int $userId The ID of the user.
     * @param int $tenantId The ID of the tenant.
     * @return array An array of payslip records.
     */
    public function getPayslipsByUserId(int $userId, int $tenantId): array
    {
        $sql = "SELECT 
                    p.id, p.gross_pay, p.net_pay, p.payslip_path, p.generated_at,
                    pp.period_name, pp.start_date, pp.end_date
                FROM {$this->table} p
                JOIN payroll_periods pp ON p.payroll_period_id = pp.id
                WHERE p.user_id = :user_id AND p.tenant_id = :tenant_id
                ORDER BY pp.start_date DESC, p.generated_at DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':tenant_id' => $tenantId,
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve payslips for user {$userId} (tenant {$tenantId}). Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves the payroll history for a specific department over the last specified number of months.
     *
     * @param int $departmentId The ID of the department.
     * @param int $months The number of months of history to retrieve.
     * @return array An array of payroll history data for the department.
     */
    public function getPayrollHistoryForDepartment(int $departmentId, int $months = 6): array
    {
        $sql = "SELECT 
                    pp.period_name as month,
                    SUM(p.gross_pay) as total_gross,
                    SUM(p.net_pay) as total_net,
                    SUM(p.paye_amount) as total_paye
                FROM payslips p
                JOIN payroll_periods pp ON p.payroll_period_id = pp.id
                JOIN employees e ON p.user_id = e.user_id
                JOIN positions pos ON e.current_position_id = pos.id
                WHERE p.tenant_id = :tenant_id
                  AND pos.department_id = :department_id
                  AND pp.is_closed = TRUE
                  AND pp.start_date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY pp.id, pp.period_name, pp.start_date
                ORDER BY pp.start_date ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $this->currentTenantId,
                ':department_id' => $departmentId,
                ':months' => $months,
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve payroll history for department {$departmentId} in tenant {$this->currentTenantId}. Error: " . $e->getMessage());
            return [];
        }
    }
}
