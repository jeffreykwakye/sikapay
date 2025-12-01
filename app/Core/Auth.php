<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

use Jeffrey\Sikapay\Core\Database;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Models\LoginAttemptModel;

class Auth 
{
    private static ?Auth $instance = null;
    private \PDO $db;
    private LoginAttemptModel $loginAttemptModel;
    private const SUPER_ADMIN_ROLE_NAME = 'super_admin';
    private const ORIGINAL_SESSION_KEY = 'original_super_admin_session';

    // Make constructor private for Singleton pattern
    private function __construct() 
    {
        try {
            $this->db = Database::getInstance() 
                ?? throw new \Exception("Database connection required for Auth service.");
            $this->loginAttemptModel = new LoginAttemptModel();
        } catch (\Exception $e) {
            // Critical DB setup failure
            Log::critical("Auth Service DB Initialization Failure: " . $e->getMessage());
            // Re-throw or die since this is a critical system error
            throw new \Exception("Auth Service cannot initialize: Database unavailable.");
        }
            
        // Ensure session is started through the SessionManager
        SessionManager::start();
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
        if ($this->loginAttemptModel->isLockedOut($email)) {
            Log::warning("Login attempt for locked-out account: {$email}");
            return false;
        }

        try {
            $stmt = $this->db->prepare("SELECT u.id, u.tenant_id, u.role_id, u.password 
                                         FROM users u 
                                         WHERE u.email = :email AND u.is_active = TRUE");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                $this->loginAttemptModel->recordFailedAttempt($email, $_SERVER['REMOTE_ADDR'] ?? 'N/A');
                return false;
            }

            // --- Authentication successful: Clear attempts and set session data ---
            $this->loginAttemptModel->clearAttempts($email);
            $this->loadUserSession($user);
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

    /**
     * Allows a Super Admin to impersonate another user.
     * Stores the Super Admin's session and loads the impersonated user's session.
     * @param int $impersonateUserId The ID of the user to impersonate.
     * @return bool True on success, false if user not found or already impersonating.
     */
    public function startImpersonation(int $impersonateUserId): bool
    {
        if (!self::isSuperAdmin()) {
            Log::warning("Non-super admin attempted to start impersonation.");
            return false; // Only Super Admins can impersonate
        }

        if (self::isImpersonating()) {
            Log::warning("Super Admin " . self::userId() . " attempted to start impersonation while already impersonating.");
            return false; // Already impersonating, stop current first
        }

        try {
            $stmt = $this->db->prepare("SELECT id, tenant_id, role_id FROM users WHERE id = :id AND is_active = TRUE");
            $stmt->execute([':id' => $impersonateUserId]);
            $userToImpersonate = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$userToImpersonate) {
                Log::error("Attempted to impersonate non-existent or inactive user ID: {$impersonateUserId}");
                return false;
            }

            // Store current Super Admin session details
            $_SESSION[self::ORIGINAL_SESSION_KEY] = [
                'user_id' => $_SESSION['user_id'] ?? null,
                'tenant_id' => $_SESSION['tenant_id'] ?? null,
                'role_id' => $_SESSION['role_id'] ?? null,
                'role_name' => $_SESSION['role_name'] ?? null,
                'is_super_admin' => $_SESSION['is_super_admin'] ?? false,
            ];

            // Load the impersonated user's session
            $this->loadUserSession($userToImpersonate);

            Log::info("Super Admin " . $_SESSION[self::ORIGINAL_SESSION_KEY]['user_id'] . " started impersonating user " . $impersonateUserId);
            return true;
        } catch (\PDOException $e) {
            Log::error("Database error during impersonation start for user ID {$impersonateUserId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Stops impersonation and restores the original Super Admin's session.
     */
    public function stopImpersonation(): bool
    {
        if (!self::isImpersonating()) {
            Log::warning("Attempted to stop impersonation when not impersonating.");
            return false;
        }

        // Restore original Super Admin session details
        $originalSession = $_SESSION[self::ORIGINAL_SESSION_KEY];
        
        // Clear current session data
        session_unset();
        session_destroy();
        SessionManager::start(); // Restart session

        // Restore Super Admin's session variables
        $_SESSION['user_id'] = $originalSession['user_id'];
        $_SESSION['tenant_id'] = $originalSession['tenant_id'];
        $_SESSION['role_id'] = $originalSession['role_id'];
        $_SESSION['role_name'] = $originalSession['role_name'];
        $_SESSION['is_super_admin'] = $originalSession['is_super_admin'];

        // Remove the original session key
        unset($_SESSION[self::ORIGINAL_SESSION_KEY]);

        Log::info("Impersonation stopped. Restored Super Admin " . $_SESSION['user_id'] . " session.");
        return true;
    }

    /**
     * Checks if the current user is an impersonated user.
     */
    public static function isImpersonating(): bool
    {
        return isset($_SESSION[self::ORIGINAL_SESSION_KEY]);
    }

    /**
     * If currently impersonating, returns the ID of the original Super Admin.
     * Otherwise, returns 0.
     */
    public static function getImpersonatorId(): int
    {
        return self::isImpersonating() ? (int)$_SESSION[self::ORIGINAL_SESSION_KEY]['user_id'] : 0;
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
    
    /**
     * Loads user-specific data into the session.
     * This method is called after successful login or during impersonation.
     */
    private function loadUserSession(array $userData): void
    {
        $roleId = (int)$userData['role_id'];
        $roleName = $this->getRoleNameById($roleId);

        $_SESSION['user_id'] = (int)$userData['id'];
        $_SESSION['tenant_id'] = (int)$userData['tenant_id'];
        $_SESSION['role_id'] = $roleId;
        $_SESSION['role_name'] = $roleName;
        $_SESSION['is_super_admin'] = ($roleName === self::SUPER_ADMIN_ROLE_NAME);
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