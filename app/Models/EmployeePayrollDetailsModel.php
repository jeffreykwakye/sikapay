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
        $sql = "SELECT 
                    epd.id, epd.assigned_amount, epd.effective_date, epd.end_date,
                    tpe.name, tpe.category, tpe.amount_type, tpe.default_amount, tpe.calculation_base, tpe.is_taxable, tpe.is_ssnit_chargeable, tpe.is_recurring
                FROM {$this->table} epd
                JOIN tenant_payroll_elements tpe ON epd.payroll_element_id = tpe.id
                WHERE epd.user_id = :user_id AND epd.tenant_id = :tenant_id
                ORDER BY epd.effective_date DESC";

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

    /**
     * Creates a new employee payroll detail record.
     *
     * @param array $data Contains user_id, tenant_id, payroll_element_id, assigned_amount, effective_date, end_date.
     * @return bool True on success, false otherwise.
     */
    public function create(array $data): bool
    {
        $sql = "INSERT INTO {$this->table} (
                    user_id, tenant_id, payroll_element_id, assigned_amount, effective_date, end_date
                ) VALUES (
                    :user_id, :tenant_id, :payroll_element_id, :assigned_amount, :effective_date, :end_date
                )";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':user_id' => $data['user_id'],
                ':tenant_id' => $data['tenant_id'],
                ':payroll_element_id' => $data['payroll_element_id'],
                ':assigned_amount' => $data['assigned_amount'],
                ':effective_date' => $data['effective_date'],
                ':end_date' => $data['end_date'] ?? null,
            ]);
        } catch (PDOException $e) {
            Log::error("Failed to create employee payroll detail for user {$data['user_id']} and element {$data['payroll_element_id']}. Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes an employee payroll detail record.
     *
     * @param int $userId
     * @param int $payrollElementId
     * @param int $tenantId
     * @return bool True on success, false otherwise.
     */
    public function deleteByEmployeeAndElement(int $userId, int $payrollElementId, int $tenantId): bool
    {
        $sql = "DELETE FROM {$this->table} 
                WHERE user_id = :user_id AND payroll_element_id = :payroll_element_id AND tenant_id = :tenant_id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':user_id' => $userId,
                ':payroll_element_id' => $payrollElementId,
                ':tenant_id' => $tenantId,
            ]);
        } catch (PDOException $e) {
            Log::error("Failed to delete employee payroll detail for user {$userId} and element {$payrollElementId}. Error: " . $e->getMessage());
            return false;
        }
    }
