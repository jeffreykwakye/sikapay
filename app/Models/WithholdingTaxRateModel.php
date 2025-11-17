<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class WithholdingTaxRateModel extends Model
{
    public function __construct()
    {
        parent::__construct('withholding_tax_rates');
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
