<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

use Jeffrey\Sikapay\Core\Database;
use Jeffrey\Sikapay\Core\Auth; 

abstract class Model
{
    protected \PDO $db;
    protected string $table;
    
    // Multi-tenancy context
    protected ?int $currentTenantId;
    protected bool $isSuperAdmin;

    public function __construct(string $table)
    {
        $this->db = Database::getInstance() 
            ?? throw new \Exception("Database connection required for Model service.");
            
        $this->table = $table;
        
        // Retrieve the current user's context from the Auth service
        $this->isSuperAdmin = Auth::isSuperAdmin();
        $this->currentTenantId = Auth::tenantId();

        // Ensure a non-admin user has a tenant ID (security check)
        if (!$this->isSuperAdmin && $this->currentTenantId === 0) {
             throw new \Exception("Security Violation: Non-admin user accessing model without a Tenant ID.");
        }
    }

    /**
     * Retrieves the basic WHERE clause required for multi-tenancy isolation.
     * This will be prepended to all standard queries.
     * * @return string The SQL WHERE clause fragment (e.g., "WHERE tenant_id = 1") or an empty string.
     */
    protected function getTenantScope(array $where = []): string
    {
        // 1. Super Admins bypass scoping entirely
        if ($this->isSuperAdmin) {
            return $where ? "WHERE " . implode(' AND ', $where) : "";
        }
        
        // 2. Tenant Users must be strictly scoped
        $scope = "tenant_id = {$this->currentTenantId}";
        
        if (empty($where)) {
            return "WHERE {$scope}";
        }
        
        // 3. Combine user's existing WHERE clauses with the tenant scope
        return "WHERE {$scope} AND " . implode(' AND ', $where);
    }
    
    /**
     * Generic method to find a single record by its ID, enforcing tenant scope.
     * * @param int $id
     * @return mixed|null
     */
    public function find(int $id): mixed
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        
        // Add the tenant scope (Super Admin sees all, Tenant sees only their own)
        if (!$this->isSuperAdmin) {
             // If we're scoped, we need to check both ID and tenant_id
             $sql .= " AND tenant_id = {$this->currentTenantId}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Generic method to retrieve all records, enforcing tenant scope.
     * * @param array $where Additional WHERE conditions.
     * @return array
     */
    public function all(array $where = []): array
    {
        // $this->getTenantScope handles adding "WHERE" if needed
        $sql = "SELECT * FROM {$this->table} " . $this->getTenantScope($where);
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}