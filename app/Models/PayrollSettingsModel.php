<?php

declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class PayrollSettingsModel extends Model
{
    public function __construct()
    {
        parent::__construct('payroll_settings');
    }

    /**
     * Retrieves a specific payroll setting for a tenant.
     *
     * @param int $tenantId
     * @param string $key The setting key.
     * @param mixed $default The default value if the setting is not found.
     * @return mixed The setting value.
     */
    public function getSetting(int $tenantId, string $key, mixed $default = null): mixed
    {
        $sql = "SELECT setting_value FROM {$this->table} 
                WHERE tenant_id = :tenant_id AND setting_key = :setting_key";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $tenantId,
                ':setting_key' => $key,
            ]);
            $value = $stmt->fetchColumn();
            return $value !== false ? $value : $default;
        } catch (PDOException $e) {
            Log::error("Failed to retrieve payroll setting ('{$key}') for tenant {$tenantId}. Error: " . $e->getMessage());
            return $default;
        }
    }
}
