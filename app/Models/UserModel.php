<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;

class UserModel extends Model
{
    public function __construct()
    {
        // Note: The 'users' table is tenant-scoped for tenant admins, 
        // but Super Admins can access it globally. The Model base class handles this.
        parent::__construct('users');
    }

    /**
     * Creates a new user record.
     */
    public function createUser(int $tenantId, array $data): int
    {
        $sql = "INSERT INTO users 
                 (tenant_id, role_id, email, password, first_name, last_name, is_active, other_name, phone) 
                 VALUES (:tenant_id, :role_id, :email, :password, :first_name, :last_name, TRUE, :other_name, :phone)";
        
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
    }

    /**
     * Retrieves the first and last name for a user ID, bypassing tenant scope 
     * since this is used by the Base Controller for the logged-in user.
     */
    public function getNameById(int $userId): array
    {
        if ($userId <= 0) {
            return ['first_name' => null, 'last_name' => null];
        }
        
        // Note: We deliberately query only by ID. The current tenant scope 
        // applied by the Model base class is sufficient to isolate tenant users, 
        // and Super Admins (tenantId 1) will also pass.
        $sql = "SELECT first_name, last_name FROM users WHERE id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: ['first_name' => null, 'last_name' => null];
    }
}