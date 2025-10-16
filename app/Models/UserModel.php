<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;

class UserModel extends Model
{
    public function __construct()
    {
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
     * Updates an existing user record.
     * * @param int $userId The ID of the user to update.
     * @param array $data The data to update.
     * @return bool True on success (even if no rows were affected).
     */
    public function updateUser(int $userId, array $data): bool
    {
        // Dynamically build the SET clause
        $setClauses = [];
        $bindParams = [':user_id' => $userId];
        
        foreach ($data as $key => $value) {
            $setClauses[] = "{$key} = :{$key}";
            $bindParams[":{$key}"] = $value;
        }
        
        if (empty($setClauses)) {
            return true; // Nothing to update
        }
        
        $sql = "UPDATE users SET " . implode(', ', $setClauses) . " WHERE id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($bindParams);
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
        
        $sql = "SELECT first_name, last_name FROM users WHERE id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: ['first_name' => null, 'last_name' => null];
    }
}