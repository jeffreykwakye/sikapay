<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\Auth;
use \PDOException;

class TenantModel extends Model
{
    // NOTE: This model should implicitly have $noTenantScope = true 
    // since it manages tenants themselves, but we rely on the direct queries for now.

    public function __construct()
    {
        parent::__construct('tenants');
    }
    
    
    /**
     * Creates a new tenant record and returns the new tenant's ID.
     * @return int The ID of the newly created tenant, or 0 on failure.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO tenants 
                (name, subdomain, subscription_status, payroll_approval_flow, plan_id) 
                VALUES (:name, :subdomain, :subscription_status, :payroll_approval_flow, :plan_id)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':name' => $data['name'],
                ':subdomain' => $data['subdomain'],
                ':subscription_status' => $data['subscription_status'] ?? 'trial', 
                ':payroll_approval_flow' => $data['payroll_approval_flow'] ?? 'ACCOUNTANT_FINAL', 
                ':plan_id' => $data['plan_id'],
            ]);

            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            // Log the critical failure to create a new tenant
            Log::critical("TENANT CREATION FAILED. Database error while inserting new tenant.", [
                'name' => $data['name'],
                'subdomain' => $data['subdomain'],
                'db_error' => $e->getMessage()
            ]);
            // Re-throw the exception: Tenant creation MUST succeed or the process fails.
            throw $e;
        }
    }


    /**
     * Retrieves a tenant's name based on their ID.
     */
    public function getNameById(int $tenantId): ?string
    {
        $sql = "SELECT name FROM tenants WHERE id = :tenant_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tenant_id' => $tenantId]);
            
            $name = $stmt->fetchColumn();
            return $name !== false ? $name : null;

        } catch (PDOException $e) {
            // Log failure in read operation for system configuration
            Log::error("Tenant READ failed (getNameById) for Tenant ID {$tenantId}. Error: " . $e->getMessage(), [
                'acting_user_id' => Auth::userId()
            ]);
            // Re-throw the exception as configuration data is likely needed for rendering.
            throw $e;
        }
    }

    /**
     * Counts the total number of tenants in the system, excluding the system tenant (ID 1).
     * @return int
     */
    public function countAllTenants(): int
    {
        // We do not apply tenant scope here. This is a system-wide query.
        $sql = "SELECT COUNT(id) FROM {$this->table} WHERE id != 1";
        try {
            $stmt = $this->db->query($sql);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            Log::error("Failed to count all tenants. Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Counts the number of new tenants created in the last 30 days.
     * @return int
     */
    public function countNewTenantsLast30Days(): int
    {
        $sql = "SELECT COUNT(id) FROM {$this->table} WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        try {
            $stmt = $this->db->query($sql);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            Log::error("Failed to count new tenants in last 30 days. Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Retrieves all tenants created within the last X days.
     * @param int $days The number of days to look back.
     * @return array
     */
    public function getNewTenants(int $days = 30): array
    {
        $sql = "SELECT id, name, subdomain, subscription_status, created_at 
                FROM {$this->table} 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                ORDER BY created_at DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':days' => $days]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve new tenants. Error: " . $e->getMessage());
            return [];
        }
    }
}