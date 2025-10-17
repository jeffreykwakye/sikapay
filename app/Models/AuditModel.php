<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class AuditModel extends Model
{
    // Audit logs track system-wide events and are not tenant-scoped.
    protected bool $noTenantScope = true; 

    public function __construct()
    {
        // The parent constructor handles connecting to the DB and checks for critical errors.
        parent::__construct('audit_logs');
    }
    
    /**
     * Records an activity in the audit log.
     * @param int $loggedTenantId The tenant ID the action pertains to (can be 0 for super admin actions).
     * @param string $action A short description of the action.
     * @param array $details Contextual data to be stored (JSON encoded).
     * @return int The ID of the inserted log record, or 0 on failure.
     */
    public function log(int $loggedTenantId, string $action, array $details = []): int
    {
        $actingUserId = Auth::userId(); // ID of the user performing the action
        
        $sql = "INSERT INTO audit_logs 
                (tenant_id, user_id, action, details) 
                VALUES (:tenant_id, :user_id, :action, :details)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $loggedTenantId,
                ':user_id' => $actingUserId,
                ':action' => $action,
                ':details' => json_encode($details),
            ]);

            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            
            // Log the critical failure to write to the audit log
            // We log this as CRITICAL because the integrity of the audit trail is compromised.
            Log::critical("AUDIT LOG WRITE FAILURE: Cannot record action. System integrity risk.", [
                'user_id' => $actingUserId,
                'tenant_id' => $loggedTenantId,
                'action' => $action,
                'db_error' => $e->getMessage()
            ]);
            
            // Do NOT throw the exception or halt the application flow. 
            // The user's main action should ideally still complete, but the logging failure must be recorded.
            return 0; // Indicate failure to the caller
        }
    }
}