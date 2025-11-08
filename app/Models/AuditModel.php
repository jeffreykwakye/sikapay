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

    /**
     * Retrieves the most recent audit log entries for a given tenant.
     *
     * @param int $tenantId The ID of the tenant.
     * @param int $limit The maximum number of entries to retrieve.
     * @return array An array of audit log entries.
     */
    public function getRecentActivity(int $tenantId, int $limit = 10): array
    {
        $sql = "SELECT 
                    al.action as log_message, 
                    al.created_at, 
                    u.first_name, 
                    u.last_name
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.tenant_id = :tenant_id
                ORDER BY al.created_at DESC
                LIMIT :limit";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve recent activity for tenant {$tenantId}. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves a comprehensive list of audit log entries for a given tenant.
     *
     * @param int $tenantId The ID of the tenant.
     * @param int $limit The maximum number of entries to retrieve.
     * @return array An array of audit log entries.
     */
    public function getLogsByTenantId(int $tenantId, int $limit = 200): array
    {
        $sql = "SELECT 
                    al.action as log_message, 
                    al.created_at, 
                    u.first_name, 
                    u.last_name
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.tenant_id = :tenant_id
                ORDER BY al.created_at DESC
                LIMIT :limit";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve activity logs for tenant {$tenantId}. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves all audit log entries across all tenants for super admin view.
     *
     * @param int $limit The maximum number of entries to retrieve.
     * @return array An array of audit log entries.
     */
    public function getAllLogs(int $limit = 200): array
    {
        $sql = "SELECT 
                    al.action as log_message, 
                    al.created_at, 
                    u.first_name, 
                    u.last_name,
                    t.name as tenant_name
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN tenants t ON al.tenant_id = t.id
                ORDER BY al.created_at DESC
                LIMIT :limit";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve all activity logs for super admin. Error: " . $e->getMessage());
            return [];
        }
    }
}