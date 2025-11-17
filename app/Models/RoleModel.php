<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log; 
use Jeffrey\Sikapay\Core\Auth;
use \PDOException;


use \PDO;

class RoleModel extends Model
{
    // Flag to bypass tenant scoping (Roles do not have a tenant_id column)
    protected bool $noTenantScope = true; 

    
    public function __construct()
    {
        // The parent constructor handles connecting to the DB, setting the table,
        // and performing initial security checks.
        parent::__construct('roles');
    }
    
    
    /**
     * Finds a role ID by its name.
     * @param string $name The name of the role (e.g., 'super_admin').
     * @return ?int The role ID, or null if not found.
     */
    public function findIdByName(string $name): ?int
    {
        $sql = "SELECT id FROM {$this->table} WHERE name = :name";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':name' => $name]);
            
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $result ? (int)$result['id'] : null;
            
        } catch (PDOException $e) {
            // Log failure in read operation for a system table
            Log::error("Role READ failed (findIdByName) for name '{$name}'. Error: " . $e->getMessage(), [
                'sql' => $sql,
                'acting_user_id' => Auth::userId()
            ]);
            // Re-throw the exception. A failure to retrieve system data is critical 
            // and should interrupt the flow.
            throw $e;
        }
    }

    /**
     * Get all permissions associated with a specific role.
     *
     * @param int $roleId The ID of the role.
     * @return array An array of permission records (id, key_name) for the role.
     */
    public function getPermissionsForRole(int $roleId): array
    {
        try {
            $sql = "SELECT p.id, p.key_name 
                    FROM role_permissions rp
                    JOIN permissions p ON rp.permission_id = p.id
                    WHERE rp.role_id = :role_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':role_id' => $roleId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to get permissions for role {$roleId}: " . $e->getMessage(), [
                'role_id' => $roleId,
                'sql' => $sql,
                'acting_user_id' => Auth::userId()
            ]);
            throw $e; // Re-throw for higher-level error handling
        }
    }
}