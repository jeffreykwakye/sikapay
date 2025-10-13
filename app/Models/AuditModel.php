<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Auth;

class AuditModel extends Model
{
    // Audit logs track system-wide events and are not tenant-scoped.
    protected bool $noTenantScope = true; 

    public function __construct()
    {
        parent::__construct('audit_logs');
    }
    
    /**
     * Records an activity in the audit log.
     */
    public function log(int $loggedTenantId, string $action, array $details = []): int
    {
        $actingUserId = Auth::userId(); // ID of the Super Admin performing the action
        
        $sql = "INSERT INTO audit_logs 
                (tenant_id, user_id, action, details) 
                VALUES (:tenant_id, :user_id, :action, :details)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':tenant_id' => $loggedTenantId,
            ':user_id' => $actingUserId,
            ':action' => $action,
            ':details' => json_encode($details),
        ]);

        return (int)$this->db->lastInsertId();
    }
}