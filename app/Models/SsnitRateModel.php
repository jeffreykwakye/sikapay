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
     * Creates a new SSNIT rate record.
     * @param array $data
     * @return int The ID of the newly created rate, or 0 on failure.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO ssnit_rates (employee_rate, employer_rate, max_contribution_limit, effective_date) 
                VALUES (:employee_rate, :employer_rate, :max_contribution_limit, :effective_date)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':employee_rate' => $data['employee_rate'],
                ':employer_rate' => $data['employer_rate'],
                ':max_contribution_limit' => $data['max_contribution_limit'],
                ':effective_date' => $data['effective_date'],
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            Log::error("Failed to create SSNIT rate: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Updates an existing SSNIT rate record.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE ssnit_rates SET employee_rate = :employee_rate, employer_rate = :employer_rate, 
                max_contribution_limit = :max_contribution_limit, effective_date = :effective_date 
                WHERE id = :id";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':employee_rate' => $data['employee_rate'],
                ':employer_rate' => $data['employer_rate'],
                ':max_contribution_limit' => $data['max_contribution_limit'],
                ':effective_date' => $data['effective_date'],
                ':id' => $id,
            ]);
        } catch (PDOException $e) {
            Log::error("Failed to update SSNIT rate ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Deletes an SSNIT rate record.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM ssnit_rates WHERE id = :id";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            Log::error("Failed to delete SSNIT rate ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieves the current active SSNIT rates based on the effective date.
     *
     * @return array|null The SSNIT rate record, or null if not found.
     */
    public function getCurrentSsnitRate(): ?array
    {
        $sql = "SELECT effective_date, employee_rate, employer_rate, max_contribution_limit FROM {$this->table} 
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
