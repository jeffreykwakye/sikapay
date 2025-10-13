<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;

class TenantModel extends Model
{
    public function __construct()
    {
        parent::__construct('tenants');
    }
    
    /**
     * Creates a new tenant record and returns the new tenant's ID.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO tenants 
                (name, subdomain, subscription_status, payroll_approval_flow, plan_id) 
                VALUES (:name, :subdomain, :subscription_status, :payroll_approval_flow, :plan_id)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':subdomain' => $data['subdomain'],
            ':subscription_status' => $data['subscription_status'] ?? 'trial', 
            ':payroll_approval_flow' => $data['payroll_approval_flow'] ?? 'ACCOUNTANT_FINAL', 
            ':plan_id' => $data['plan_id'],
        ]);

        return (int)$this->db->lastInsertId();
    }


    /**
     * Retrieves a tenant's name based on their ID.
     */
    public function getNameById(int $tenantId): ?string
    {
        $sql = "SELECT name FROM tenants WHERE id = :tenant_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':tenant_id' => $tenantId]);
        
        // This is necessary to bypass the default tenant scoping in the base Model
        // if this model inherits from a base Model that enforces scoping.
        // For simplicity, we assume this direct query works.
        
        $name = $stmt->fetchColumn();
        return $name !== false ? $name : null;
    }
}