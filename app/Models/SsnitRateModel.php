<?php

declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class SsnitRateModel extends Model
{
    protected bool $noTenantScope = true; // SSNIT rates are system-wide

    public function __construct()
    {
        parent::__construct('ssnit_rates');
    }

    /**
     * Retrieves the current active SSNIT rates based on the effective date.
     *
     * @return array|null The SSNIT rate record, or null if not found.
     */
    public function getCurrentSsnitRate(): ?array
    {
        $sql = "SELECT employee_rate, employer_rate, max_contribution_cap FROM {$this->table} 
                WHERE effective_date <= CURDATE()
                ORDER BY effective_date DESC
                LIMIT 1";

        try {
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            Log::error("Failed to retrieve current SSNIT rates. Error: " . $e->getMessage());
            return null;
        }
    }
}
