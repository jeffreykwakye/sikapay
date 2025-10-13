<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

// Make sure Model.php and Database.php exist in app/Core/
use Jeffrey\Sikapay\Core\Database;

class Auth 
{
    private \PDO $db;
    private const SUPER_ADMIN_ROLE_NAME = 'super_admin';

    public function __construct()
    {
        // Check for the connection instance. Throw an exception if null.
        // This is necessary because the property is strictly typed as \PDO.
        $this->db = Database::getInstance() 
            ?? throw new \Exception("Database connection required for Auth service.");
            
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Attempts to log in a user by verifying credentials.
     * * @param string $email
     * @param string $password
     * @return bool True on successful login, false otherwise.
     */
    public function login(string $email, string $password): bool
    {
        $stmt = $this->db->prepare("SELECT u.id, u.tenant_id, u.role_id, u.password 
                                    FROM users u 
                                    WHERE u.email = :email AND u.is_active = TRUE");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            return false; // User not found or password mismatch
        }

        // Authentication successful: Set session data
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['tenant_id'] = (int)$user['tenant_id'];
        
        // This is necessary for tenancy separation and filtering
        $_SESSION['is_super_admin'] = $this->isSuperAdminById((int)$user['role_id']);

        // Update last login time (optional but good practice)
        $this->updateLastLogin((int)$user['id']);

        return true;
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
    
    // --- Helper Methods ---

    /**
     * Checks if a given role ID corresponds to the Super Admin role.
     */
    private function isSuperAdminById(int $roleId): bool
    {
        // Must perform a database lookup for the role name using the ID
        $stmt = $this->db->prepare("SELECT name FROM roles WHERE id = :role_id");
        $stmt->execute([':role_id' => $roleId]);
        $roleName = $stmt->fetchColumn();
        
        return $roleName === self::SUPER_ADMIN_ROLE_NAME;
    }

    /**
     * Checks if the current session user is a Super Admin.
     */
    public static function isSuperAdmin(): bool
    {
        return $_SESSION['is_super_admin'] ?? false;
    }


    /**
     * Gets the ID of the currently authenticated user.
     * @return int The user ID, or 0 if no user is logged in.
     */
    public static function userId(): int
    {
        // Assuming the authenticated user's ID is stored under 'user_id' in the session.
        return (int)($_SESSION['user_id'] ?? 0);
    }

    
    /**
     * Retrieves the current user's tenant ID from the session.
     */
    public static function tenantId(): int
    {
        // This defaults to 0 or throws an exception if not set, 
        // depending on how strict you want to be. Defaulting to 1 (System Tenant) 
        // during login is usually safer for internal checks.
        return $_SESSION['tenant_id'] ?? 0;
    }

    private function updateLastLogin(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        $stmt->execute([':id' => $userId]);
    }
}