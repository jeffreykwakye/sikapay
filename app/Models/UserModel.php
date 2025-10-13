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
}