<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\Auth;
use \PDOException;

class UserModel extends Model
{
    public function __construct()
    {
        parent::__construct('users');
    }

    
    /**
     * Creates a new user record.
     * @return int The ID of the newly created user, or 0 on failure.
     */
    public function createUser(int $tenantId, array $data): int
    {
        $sql = "INSERT INTO users 
                  (tenant_id, role_id, email, password, first_name, last_name, is_active, other_name, phone) 
                  VALUES (:tenant_id, :role_id, :email, :password, :first_name, :last_name, TRUE, :other_name, :phone)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $tenantId,
                ':role_id' => $data['role_id'],
                ':email' => $data['email'],
                ':password' => $data['password'],
                ':first_name' => $data['first_name'],
                ':last_name' => $data['last_name'],
                ':other_name' => $data['other_name'] ?? null,
                ':phone' => $data['phone'] ?? null,
            ]);
            
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            // Log critical failure to create a user account
            Log::critical("USER CREATION FAILED for Tenant {$tenantId}. Email: {$data['email']}. Error: " . $e->getMessage(), [
                'input_keys' => array_keys($data),
                'acting_user_id' => Auth::userId()
            ]);
            // Re-throw the exception: User creation MUST succeed.
            throw $e;
        }
    }
    
    
    /**
     * Updates an existing user record.
     * @param int $userId The ID of the user to update.
     * @param array $data The data to update.
     * @return bool True on success (even if no rows were affected).
     */
    public function updateUser(int $userId, array $data): bool
    {
        $setClauses = [];
        $bindParams = [':user_id' => $userId];
        
        foreach ($data as $key => $value) {
            $setClauses[] = "{$key} = :{$key}";
            $bindParams[":{$key}"] = $value;
        }
        
        if (empty($setClauses)) {
            return true;
        }
        
        // IMPORTANT: Add the tenant scope manually for security on UPDATE
        $sql = "UPDATE users SET " . implode(', ', $setClauses) . " WHERE id = :user_id AND tenant_id = " . $this->currentTenantId;
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($bindParams);
        } catch (PDOException $e) {
            // Log failure in user update operation
            Log::error("USER UPDATE FAILED for target User {$userId} (Tenant " . Auth::tenantId() . "). Error: " . $e->getMessage(), [
                'updated_keys' => array_keys($data),
                'acting_user_id' => Auth::userId()
            ]);
            // Re-throw the exception: Data integrity is compromised.
            throw $e;
        }
    }

    /**
     * Retrieves the first and last name for a user ID.
     * Bypasses tenant scope because it's used by the Base Controller for the logged-in user.
     */
    public function getNameById(int $userId): array
    {
        if ($userId <= 0) {
            return ['first_name' => null, 'last_name' => null];
        }
        
        $sql = "SELECT first_name, last_name FROM users WHERE id = :user_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $result ?: ['first_name' => null, 'last_name' => null];
        } catch (PDOException $e) {
            // Log error in simple read/utility operation
            Log::error("User READ FAILED (getNameById) for User {$userId}. Error: " . $e->getMessage(), [
                'acting_user_id' => Auth::userId()
            ]);
            // Return safe defaults, as this is used by the UI and shouldn't crash the page.
                        return ['first_name' => 'Error', 'last_name' => 'User']; 
                    }
                }
            
    /**
     * Retrieves all users belonging to a specific role within a given tenant.
     * @param int $tenantId The ID of the tenant.
     * @param string $roleName The name of the role.
     * @return array An array of user records (id, email, etc.).
     */
    public function getUsersByRole(int $tenantId, string $roleName): array
    {
        $sql = "SELECT u.id, u.email, u.first_name, u.last_name 
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.tenant_id = :tenant_id AND r.name = :role_name AND u.is_active = TRUE";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $tenantId,
                ':role_name' => $roleName
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve users for role '{$roleName}' in Tenant {$tenantId}. Error: " . $e->getMessage(), [
                'acting_user_id' => Auth::userId()
            ]);
            return [];
        }
    }

    /**
     * Counts all active users belonging to a specific role within a given tenant.
     * @param int $tenantId The ID of the tenant.
     * @param string $roleName The name of the role.
     * @return int The count of users in that role.
     */
    public function countUsersByRole(int $tenantId, string $roleName): int
    {
        $sql = "SELECT COUNT(u.id) 
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.tenant_id = :tenant_id AND r.name = :role_name AND u.is_active = TRUE";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $tenantId,
                ':role_name' => $roleName
            ]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            Log::error("Failed to count users for role '{$roleName}' in Tenant {$tenantId}. Error: " . $e->getMessage(), [
                'acting_user_id' => Auth::userId()
            ]);
            return 0;
        }
    }

    /**
     * Retrieves the tenant administrator user for a given tenant ID.
     * @param int $tenantId The ID of the tenant.
     * @return array|null The tenant admin user record, or null if not found.
     */
    public function findTenantAdminUser(int $tenantId): ?array
    {
        $sql = "SELECT u.id, u.email, u.first_name, u.last_name, u.phone, u.created_at, r.name AS role_name
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.tenant_id = :tenant_id 
                AND r.name = 'tenant_admin'
                AND u.is_active = TRUE
                LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tenant_id' => $tenantId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            Log::error("Failed to retrieve tenant admin user for Tenant {$tenantId}. Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieves all users with the 'super_admin' role.
     * This method bypasses tenant scoping as Super Admins are global.
     *
     * @return array An array of super admin user records (id, email, first_name, last_name).
     */
    public function getSuperAdminUsers(): array
    {
        $sql = "SELECT u.id, u.email, u.first_name, u.last_name
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE r.name = 'super_admin' AND u.is_active = TRUE"; // Assuming 'super_admin' is the role name
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve Super Admin users. Error: " . $e->getMessage());
            return [];
        }
    }
}