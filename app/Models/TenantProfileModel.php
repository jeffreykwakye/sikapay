<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Database;
use PDO;

class TenantProfileModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Finds the profile for the given tenant ID.
     * @param int $tenantId
     * @return array|null
     */
    public function findByTenantId(int $tenantId): ?array
    {
        $stmt = $this->db->prepare("SELECT *, ghana_revenue_authority_tin AS tin FROM tenant_profiles WHERE tenant_id = :tenant_id");
        $stmt->execute([':tenant_id' => $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Creates or updates the tenant profile record.
     * Uses UPSERT (INSERT OR REPLACE/DUPLICATE KEY UPDATE) logic.
     * Note: Assuming MySQL for the ON DUPLICATE KEY UPDATE syntax.
     * * @param int $tenantId
     * @param array $data The profile data array.
     * @return bool
     */
    public function save(int $tenantId, array $data): bool
    {
        // Define fields to update
        $fields = [
            'legal_name', 'logo_path', 'phone_number', 
            'support_email', 'physical_address', 'ghana_revenue_authority_tin',
            'bank_name', 'bank_branch', 'bank_address',
            'ssnit_office_name', 'ssnit_office_address',
            'gra_office_name', 'gra_office_address',
            'authorized_signatory_name', 'authorized_signatory_title', 'bank_advice_recipient_name',
            'ssnit_report_recipient_name', 'gra_report_recipient_name'
        ];

        // Prepare parameter placeholders for both INSERT and UPDATE parts
        $params = [':tenant_id' => $tenantId];
        $insertValues = [':tenant_id'];
        $updateAssignments = [];

        foreach ($fields as $field) {
            // Use coalesce for logo_path to allow NULL if not provided
            $value = $data[$field] ?? ($field === 'logo_path' ? null : '');
            $paramKey = ":{$field}";
            
            // Insert part
            $insertValues[] = $paramKey;
            
            // Update part
            $updateAssignments[] = "{$field} = VALUES({$field})";
            
            $params[$paramKey] = $value;
        }

        $insertFields = "tenant_id, " . implode(', ', $fields);
        $insertPlaceholders = implode(', ', $insertValues);
        $updateString = implode(', ', $updateAssignments);

        $sql = "INSERT INTO tenant_profiles ({$insertFields}) 
                VALUES ({$insertPlaceholders})
                ON DUPLICATE KEY UPDATE 
                {$updateString}";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}