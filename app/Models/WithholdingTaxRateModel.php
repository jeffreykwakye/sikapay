<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class WithholdingTaxRateModel extends Model
{
    protected bool $noTenantScope = true; // Withholding Tax rates are system-wide

    public function __construct()
    {
        parent::__construct('withholding_tax_rates');
    }

    /**
     * Creates a new Withholding Tax rate record.
     * @param array $data
     * @return int The ID of the newly created rate, or 0 on failure.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO withholding_tax_rates (rate, employment_type, description, effective_date) 
                VALUES (:rate, :employment_type, :description, :effective_date)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':rate' => $data['rate'],
                ':employment_type' => $data['employment_type'],
                ':description' => $data['description'],
                ':effective_date' => $data['effective_date'],
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            Log::error("Failed to create Withholding Tax rate: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Updates an existing Withholding Tax rate record.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE withholding_tax_rates SET rate = :rate, employment_type = :employment_type, 
                description = :description, effective_date = :effective_date 
                WHERE id = :id";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':rate' => $data['rate'],
                ':employment_type' => $data['employment_type'],
                ':description' => $data['description'],
                ':effective_date' => $data['effective_date'],
                ':id' => $id,
            ]);
        } catch (PDOException $e) {
            Log::error("Failed to update Withholding Tax rate ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Deletes a Withholding Tax rate record.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM withholding_tax_rates WHERE id = :id";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            Log::error("Failed to delete Withholding Tax rate ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieves the current effective withholding tax rate.
     *
     * @param string $date The date for which to get the effective rate (defaults to today).
     * @return array|null The rate record, or null if no rate is effective for the given date.
     */
    public function getCurrentEffectiveRate(string $employmentType, string $date = 'now'): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE effective_date <= :date 
                AND employment_type = :employment_type
                ORDER BY effective_date DESC 
                LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':date' => date('Y-m-d', strtotime($date)),
                ':employment_type' => $employmentType
            ]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            Log::error("Failed to retrieve current effective withholding tax rate for date '{$date}' and employment type '{$employmentType}'. Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieves all of the most recent effective withholding tax rates, one for each employment type.
     *
     * @param string $date The date for which to get the effective rates (defaults to today).
     * @return array An array of rate records.
     */
    public function getAllCurrentEffectiveRates(string $date = 'now'): array
    {
        // This query finds the latest effective_date for each employment_type on or before the given date
        $sql = "SELECT wht1.*
                FROM {$this->table} wht1
                INNER JOIN (
                    SELECT employment_type, MAX(effective_date) AS max_date
                    FROM {$this->table}
                    WHERE effective_date <= :date
                    GROUP BY employment_type
                ) wht2 ON wht1.employment_type = wht2.employment_type AND wht1.effective_date = wht2.max_date";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':date' => date('Y-m-d', strtotime($date))]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            Log::error("Failed to retrieve all current effective withholding tax rates for date '{$date}'. Error: " . $e->getMessage());
            return [];
        }
    }
}
