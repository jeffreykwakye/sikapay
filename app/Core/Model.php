<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

use PDO;
use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Core\Database;
use Jeffrey\Sikapay\Core\ErrorResponder;

abstract class Model
{
    protected PDO $db;
    protected string $table; // Must be defined by child classes, e.g., 'employees'

    public function __construct()
    {
        $this->db = Database::getInstance() ?? throw new \Exception("Database connection required for Model access.");
    }

    
    /**
     * CRITICAL: Enforces tenancy by adding 'WHERE tenant_id = :__tenant_id' 
     * unless the user is the Super Admin or the table is global.
     */
    protected function enforceTenancy(string &$sql, array &$params): bool
    {
        // 1. Super Admin Exemption (Flexible check using role name)
        if (Auth::isSuperAdmin()) {
            return true;
        }

        $tenantId = Auth::tenantId();

        if ($tenantId === null) {
            if (Auth::check()) {
                ErrorResponder::respond(403, "Critical: User session is authenticated but missing Tenant ID.");
                return false;
            }
            return true; 
        }

        // 2. Define Tables Exempt from Tenancy Check (Global tables)
        $nonTenantTables = [
            'tenants', 'users', 'roles', 'migrations', 'audit_logs', 
            'password_resets', 'plans', 'features', 'plan_features'
        ];
        
        if (!in_array($this->table, $nonTenantTables)) {
            
            // 3. Apply the Tenancy WHERE clause dynamically
            if (stripos($sql, ' WHERE ') !== false) {
                $sql = str_ireplace(' WHERE ', ' WHERE tenant_id = :__tenant_id AND ', $sql);
            } else {
                $sql .= " WHERE tenant_id = :__tenant_id";
            }
            
            // Add the tenant ID to the parameters array
            $params[':__tenant_id'] = $tenantId;
        }

        return true;
    }
    
    
    /**
     * Generic method to find a record by ID, ensuring tenancy is enforced.
     */
    public function find(int $id): ?object
    {
        if (empty($this->table)) {
             throw new \Exception("Model table property not set.");
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $params = [':id' => $id];
        
        // Enforce tenancy before preparing the statement
        $this->enforceTenancy($sql, $params);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}