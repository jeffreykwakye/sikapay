<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

use Jeffrey\Sikapay\Core\Database;
use Jeffrey\Sikapay\Core\Log;


class Auth 
{
    private static ?Auth $instance = null;
    private \PDO $db;
    private const SUPER_ADMIN_ROLE_NAME = 'super_admin';

    // Make constructor private for Singleton pattern
    private function __construct() 
    {
        try {
            $this->db = Database::getInstance() 
                ?? throw new \Exception("Database connection required for Auth service.");
        } catch (\Exception $e) {
            // Critical DB setup failure
            Log::critical("Auth Service DB Initialization Failure: " . $e->getMessage());
            // Re-throw or die since this is a critical system error
            throw new \Exception("Auth Service cannot initialize: Database unavailable.");
        }
            
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    
    /**
     * Retrieves the single instance of the Auth service.
     */
    public static function getInstance(): Auth
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    
    /**
     * Attempts to log in a user by verifying credentials.
     */
    public function login(string $email, string $password): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT u.id, u.tenant_id, u.role_id, u.password 
                                         FROM users u 
                                         WHERE u.email = :email AND u.is_active = TRUE");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                return false;
            }

            // --- Authentication successful: Set session data ---
            
            $roleId = (int)$user['role_id'];
            $roleName = $this->getRoleNameById($roleId); // Fetch role name once

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['tenant_id'] = (int)$user['tenant_id'];
            
            // Store the Role ID and Role Name in session
            $_SESSION['role_id'] = $roleId; 
            $_SESSION['role_name'] = $roleName; 
            
            $_SESSION['is_super_admin'] = ($roleName === self::SUPER_ADMIN_ROLE_NAME); 

            $this->updateLastLogin((int)$user['id']);

            return true;
        } catch (\PDOException $e) {
            // Log a database error during login attempt
            Log::error("Login failed for email '{$email}': Database Error: " . $e->getMessage());
            return false; // Treat database failure as authentication failure
        }
    }



    /**
     * The central authorization gate. Checks User Override -> Role Default -> Super Admin.
     * @param string $permissionKey The key_name of the permission (e.g., 'employee:update').
     * @return bool True if the user is authorized, false otherwise.
     */
    public function hasPermission(string $permissionKey): bool
    {
        $userId = self::userId();
        $roleId = self::getRoleId();
        
        if ($userId === 0) {
            return false; // Not logged in
        }
        
        // 1. Super Admin Bypass
        if (self::isSuperAdmin()) {
            return true;
        }

        try {
            // Find the permission ID for the given key
            $permissionId = $this->getPermissionIdByKey($permissionKey);
            if ($permissionId === 0) {
                // Permission key is unknown, deny by default. Log this as a warning/error
                Log::error("Undefined Permission Key '{$permissionKey}' requested for authorization check.");
                return false;
            }

            // 2. Check for Explicit User Override
            $userOverride = $this->hasUserOverride($userId, $permissionId);
            if ($userOverride !== null) {
                return $userOverride; // Use TRUE or FALSE from override
            }
            
            // 3. Check for Default Role Permission
            return $this->hasRolePermission($roleId, $permissionId);
            
        } catch (\PDOException $e) {
            //  Log a database error during permission check
            Log::critical("Authorization Database Error for User {$userId}, Key '{$permissionKey}': " . $e->getMessage());
            return false; // Fail safe on database error
        }
    }
    
    
    /**
     * Backward compatibility alias for hasPermission().
     * @deprecated Use hasPermission() instead.
     */
    public function can(string $permissionKey): bool
    {
        return $this->hasPermission($permissionKey);
    }

    
    /**
     * Checks if the currently logged-in user is authenticated.
     */
    public function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    
    /**
     * Logs out the current user.
     */
    public function logout(): void
    {
        session_unset();
        session_destroy();
    }
    
    
    // --- RBAC Private Helper Methods ---

    /**
     * Looks up the ID for a given permission key_name.
     */
    private function getPermissionIdByKey(string $permissionKey): int
    {
        $stmt = $this->db->prepare("SELECT id FROM permissions WHERE key_name = :key LIMIT 1");
        $stmt->execute([':key' => $permissionKey]);
        return (int)$stmt->fetchColumn();
    }

    
    /**
     * Retrieves the role name from the database based on the role ID.
     */
    private function getRoleNameById(int $roleId): string
    {
        $stmt = $this->db->prepare("SELECT name FROM roles WHERE id = :role_id");
        $stmt->execute([':role_id' => $roleId]);
        return $stmt->fetchColumn() ?: '';
    }

    
    /**
     * Checks the user_permissions table for an explicit ALLOW or DENY override.
     * Returns TRUE (ALLOW), FALSE (DENY), or null (No Override).
     */
    private function hasUserOverride(int $userId, int $permissionId): ?bool
    {
        $sql = "SELECT is_allowed FROM user_permissions 
                WHERE user_id = :user_id AND permission_id = :perm_id 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql); 
        $stmt->execute([
            ':user_id' => $userId, 
            ':perm_id' => $permissionId
        ]);
        
        $result = $stmt->fetchColumn();
        
        if ($result !== false) {
             return (bool)$result; // Returns TRUE or FALSE based on the database flag
        }
        return null; // No override found
    }

    
    /**
     * Checks the role_permissions table for the role's default access.
     */
    private function hasRolePermission(int $roleId, int $permissionId): bool
    {
        $sql = "SELECT COUNT(*) FROM role_permissions 
                WHERE role_id = :role_id AND permission_id = :perm_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':role_id' => $roleId, 
            ':perm_id' => $permissionId
        ]);
        
        return (int)$stmt->fetchColumn() > 0;
    }

    
    private function updateLastLogin(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        $stmt->execute([':id' => $userId]);
    }
    
    // --- RBAC Static Getter Methods ---

    public static function isSuperAdmin(): bool
    {
        return $_SESSION['is_super_admin'] ?? false;
    }
    
    
    public static function userId(): int
    {
        return (int)($_SESSION['user_id'] ?? 0);
    }
    
    
    public static function tenantId(): int
    {
        return $_SESSION['tenant_id'] ?? 0;
    }
    
    
    public static function getRoleId(): int
    {
        return $_SESSION['role_id'] ?? 0;
    }
    
    
    public static function getRoleName(): string
    {
        return $_SESSION['role_name'] ?? '';
    }
}