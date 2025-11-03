<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class CustomPayrollElementModel extends Model
{
    public function __construct()
    {
        parent::__construct('tenant_payroll_elements');
    }

    /**
     * Retrieves all custom payroll elements (allowances/deductions) for a given tenant.
     *
     * @param int $tenantId
     * @return array An array of custom payroll element records.
     */
    public function getAllByTenant(int $tenantId): array
    {
        $sql = "SELECT 
                    id, name, category, amount_type, default_amount, calculation_base, 
                    is_taxable, is_ssnit_chargeable, is_recurring, description 
                FROM {$this->table} 
                WHERE tenant_id = :tenant_id
                ORDER BY category ASC, name ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $tenantId,
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve custom payroll elements for tenant {$tenantId}. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Creates a new custom payroll element.
     *
     * @param int $tenantId
     * @param array $data Contains the element's properties.
     * @return int The ID of the new element, or 0 on failure.
     */
    public function create(int $tenantId, array $data): int
    {
        $sql = "INSERT INTO {$this->table} (
                    tenant_id, name, category, amount_type, default_amount, calculation_base, 
                    is_taxable, is_ssnit_chargeable, is_recurring, description
                ) VALUES (
                    :tenant_id, :name, :category, :amount_type, :default_amount, :calculation_base, 
                    :is_taxable, :is_ssnit_chargeable, :is_recurring, :description
                )";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $tenantId,
                ':name' => $data['name'],
                ':category' => $data['category'],
                ':amount_type' => $data['amount_type'],
                ':default_amount' => $data['default_amount'],
                ':calculation_base' => $data['calculation_base'],
                ':is_taxable' => $data['is_taxable'],
                ':is_ssnit_chargeable' => $data['is_ssnit_chargeable'],
                ':is_recurring' => $data['is_recurring'],
                ':description' => $data['description'],
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            Log::error("Failed to create custom payroll element '{$data['name']}' for tenant {$tenantId}. Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Updates an existing custom payroll element.
     *
     * @param int $id The ID of the element to update.
     * @param int $tenantId
     * @param array $data Contains the element's properties.
     * @return bool True on success, false otherwise.
     */
    public function update(int $id, int $tenantId, array $data): bool
    {
        $sql = "UPDATE {$this->table} SET 
                    name = :name, 
                    category = :category, 
                    amount_type = :amount_type, 
                    default_amount = :default_amount, 
                    calculation_base = :calculation_base, 
                    is_taxable = :is_taxable, 
                    is_ssnit_chargeable = :is_ssnit_chargeable, 
                    is_recurring = :is_recurring, 
                    description = :description,
                    updated_at = NOW()
                WHERE id = :id AND tenant_id = :tenant_id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':tenant_id' => $tenantId,
                ':name' => $data['name'],
                ':category' => $data['category'],
                ':amount_type' => $data['amount_type'],
                ':default_amount' => $data['default_amount'],
                ':calculation_base' => $data['calculation_base'],
                ':is_taxable' => $data['is_taxable'],
                ':is_ssnit_chargeable' => $data['is_ssnit_chargeable'],
                ':is_recurring' => $data['is_recurring'],
                ':description' => $data['description'],
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Log::error("Failed to update custom payroll element {$id} for tenant {$tenantId}. Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a custom payroll element.
     *
     * @param int $id The ID of the element to delete.
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
            Log::error("Failed to delete custom payroll element {$id} for tenant {$tenantId}. Error: " . $e->getMessage());
            return false;
        }
    }
}
