<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;

class RoleModel extends Model
{
    // Flag to bypass tenant scoping (Roles do not have a tenant_id column)
    protected bool $noTenantScope = true; 

    public function __construct()
    {
        parent::__construct('roles');
    }
    
    /**
     * Finds a role ID by its name.
     */
    public function findIdByName(string $name): ?int
    {
        $sql = "SELECT id FROM {$this->table} WHERE name = :name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':name' => $name]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ? (int)$result['id'] : null;
    }
}