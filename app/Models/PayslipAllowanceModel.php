<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Core\Log;

class PayslipAllowanceModel extends Model
{
    public function __construct()
    {
        parent::__construct('payslip_allowances');
    }

    /**
     * Generic method to create a new record, enforcing tenant scope.
     * @param array $data The data to insert.
     * @return int The ID of the newly created record.
     * @throws \PDOException If the insert operation fails.
     */
    public function create(array $data): int
    {
        // Automatically add tenant_id if not super admin and not a no-scope table
        if (!$this->isSuperAdmin && !$this->noTenantScope && !isset($data['tenant_id'])) {
            $data['tenant_id'] = $this->currentTenantId;
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            return (int)$this->db->lastInsertId();
        } catch (\PDOException $e) {
            Log::error("Database INSERT query failed in Model '{$this->table}'. Error: " . $e->getMessage(), [
                'user_id' => Auth::userId(),
                'tenant_id' => $this->currentTenantId,
                'sql' => $sql,
                'data' => $data
            ]);
            throw $e;
        }
    }
}
