<?php

declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class TaxBandModel extends Model
{
    protected bool $noTenantScope = true; // Tax bands are system-wide

    public function __construct()
    {
        parent::__construct('tax_bands');
    }

    /**
     * Retrieves tax bands for a specific year and type (annual/monthly).
     *
     * @param int $year The tax year.
     * @param bool $isAnnual True for annual bands, false for monthly.
     * @return array An array of tax band records.
     */
    public function getTaxBandsForYear(int $year, bool $isAnnual): array
    {
        // Find the latest tax_year that is less than or equal to the provided year
        $latestTaxYearSql = "SELECT MAX(tax_year) FROM {$this->table} WHERE tax_year <= :year";
        $stmt = $this->db->prepare($latestTaxYearSql);
        $stmt->execute([':year' => $year]);
        $actualTaxYear = (int)$stmt->fetchColumn();

        if ($actualTaxYear === 0) {
            Log::warning("No tax bands found for year <= {$year}.");
            return [];
        }

        $sql = "SELECT band_start, band_end, rate FROM {$this->table} 
                WHERE tax_year = :tax_year AND is_annual = :is_annual
                ORDER BY band_start ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tax_year' => $actualTaxYear,
                ':is_annual' => $isAnnual ? 1 : 0,
            ]);
            $taxBands = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            Log::debug("Fetched tax bands for year {$actualTaxYear} (isAnnual: {$isAnnual}): ", $taxBands);
            return $taxBands;
        } catch (PDOException $e) {
            Log::error("Failed to retrieve tax bands for year {$actualTaxYear} (isAnnual: {$isAnnual}). Error: " . $e->getMessage());
            return [];
        }
    }
}
