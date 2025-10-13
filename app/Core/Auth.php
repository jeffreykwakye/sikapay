<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

use PDO;
use Jeffrey\Sikapay\Core\SessionManager;
use Jeffrey\Sikapay\Core\Database;

class Auth
{
    private PDO $db;
    
    // Define the Super Admin Role Name
    private const SUPER_ADMIN_ROLE_NAME = 'super_admin';
    // Static property to cache the role ID after the first lookup
    private static ?int $superAdminRoleId = null; 

    public function __construct()
    {
        $this->db = Database::getInstance() ?? throw new \Exception("Database connection required for Auth services.");
    }

    /**
     * Attempts to log in a user by email and password.
     */
    public function attempt(string $email, string $password): bool
    {
        $stmt = $this->db->prepare("SELECT id, tenant_id, role_id, password FROM users WHERE email = :email AND is_active = 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user->password)) {
            $userRoleId = (int)$user->role_id;
            
            // 1. Subscription Check (Bypass for Super Admin)
            if (!$this->isSuperAdminRole($userRoleId)) {
                $tenantStatus = $this->getTenantStatus((int)$user->tenant_id);
                
                // Only allow active or trial tenants
                if ($tenantStatus !== 'active' && $tenantStatus !== 'trial') {
                    return false; 
                }
            }
            
            // 2. Set session variables
            SessionManager::set('user_id', (int)$user->id);
            SessionManager::set('tenant_id', (int)$user->tenant_id);
            SessionManager::set('role_id', $userRoleId);

            return true;
        }
        return false;
    }



    /**
     * Retrieves the ID of the Super Admin role, caching the result.
     */
    private function getSuperAdminRoleId(): int
    {
        if (self::$superAdminRoleId === null) {
            $stmt = $this->db->prepare("SELECT id FROM roles WHERE name = :name");
            $stmt->execute([':name' => self::SUPER_ADMIN_ROLE_NAME]);
            $roleId = $stmt->fetchColumn();

            if (!$roleId) {
                throw new \Exception("Critical Error: Super Admin role 'super_admin' not found in database.");
            }
            self::$superAdminRoleId = (int)$roleId;
        }
        return self::$superAdminRoleId;
    }


    /**
     * Checks if a given role ID matches the Super Admin role ID.
     */
    private function isSuperAdminRole(int $roleId): bool
    {
        return $roleId === $this->getSuperAdminRoleId();
    }

    // --- Core Check Methods ---
    public static function check(): bool
    {
        return SessionManager::get('user_id') !== null;
    }

    
    /**
     * Checks if the currently logged-in user is a Super Admin.
     */
    public static function isSuperAdmin(): bool
    {
        // Must instantiate Auth to access the database check
        $auth = new self(); 
        $userRoleId = SessionManager::get('role_id');
        
        if ($userRoleId === null) {
            return false;
        }

        return $auth->isSuperAdminRole($userRoleId);
    }
    
    
    // --- Accessor Methods ---
    public static function userId(): ?int
    {
        return SessionManager::get('user_id');
    }

    public static function tenantId(): ?int
    {
        // Root users have a tenant_id set (from seeder) but we must check for the role
        return SessionManager::get('tenant_id');
    }

    
    public static function roleId(): ?int
    {
        return SessionManager::get('role_id');
    }

    /**
     * Logs out the current user.
     */
    public static function logout(): void
    {
        SessionManager::destroy();
    }

    
    /**
     * Gets the tenant's current subscription status from the database.
     */
    private function getTenantStatus(int $tenantId): string
    {
        $stmt = $this->db->prepare("SELECT subscription_status FROM tenants WHERE id = :id");
        $stmt->execute([':id' => $tenantId]);
        return $stmt->fetchColumn() ?: 'suspended';
    }
}