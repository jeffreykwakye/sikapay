<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

use Jeffrey\Sikapay\Core\Database;
use Jeffrey\Sikapay\Core\Auth; 
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use \PDO;
use \PDOException;

abstract class Model
{
    protected \PDO $db;
    protected string $table;
    
    // Multi-tenancy context
    protected ?int $currentTenantId;
    protected bool $isSuperAdmin;
    
    // Flag to bypass tenant scoping for system-level tables (e.g., plans, roles)
    protected bool $noTenantScope = false; 

    public function __construct(string $table)
    {
        // 1. Check for Database Connection (Initialization Check)
        try {
            $this->db = Database::getInstance() 
                ?? throw new \Exception("Database connection required for Model service.");
        } catch (\Exception $e) {
            // Critical failure to get a DB connection
            Log::critical("Model Initialization Failed: Cannot get PDO instance for table '{$table}'.", ['error' => $e->getMessage()]);
            ErrorResponder::respond(500, "A critical system error occurred during data access initialization.");
        }
            
        $this->table = $table;
        
        // 2. Retrieve the current user's context from the Auth service
        $this->isSuperAdmin = Auth::isSuperAdmin();
        $this->currentTenantId = Auth::tenantId();

        // 3. Security Check (Non-admin must have a Tenant ID)
        // This check should be bypassed for models explicitly marked as noTenantScope
        if (!$this->noTenantScope && !$this->isSuperAdmin && $this->currentTenantId === 0) {
            // Log security violation and halt
            Log::critical("SECURITY VIOLATION: Non-admin user (ID: " . Auth::userId() . ") accessing Model '{$table}' without a Tenant ID.", [
                'user_id' => Auth::userId(), 
                'tenant_id' => $this->currentTenantId
            ]);
            // Use 403 Forbidden for internal access control failure
            ErrorResponder::respond(403, "Access to this data is unauthorized. Missing tenant context.");
        }
    }

    /**
     * Retrieves the basic WHERE clause required for multi-tenancy isolation.
     * @return string The SQL WHERE clause fragment.
     */
    protected function getTenantScope(array $where = []): string
    {
        // 1. Bypass scoping for system tables or Super Admins
        if ($this->noTenantScope || $this->isSuperAdmin) {
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
     * Provides access to the PDO connection for external transaction management.
     * @return \PDO
     */
    public function getDB(): \PDO
    {
        return $this->db;
    }
    
    /**
     * Generic method to find a single record by its ID, enforcing tenant scope.
     */
    public function find(int $id): mixed
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = :id";
            
            // Add the tenant scope (Super Admin sees all, Tenant sees only their own)
            if (!$this->isSuperAdmin && !$this->noTenantScope) {
                $sql .= " AND tenant_id = {$this->currentTenantId}";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // Log database query failure in the find method
            Log::error("Database FIND query failed in Model '{$this->table}' for ID {$id}. Error: " . $e->getMessage(), [
                'user_id' => Auth::userId(), 
                'tenant_id' => $this->currentTenantId,
                'sql' => $sql // Log the generated SQL for debugging
            ]);
            // Re-throw the exception so the calling controller/service can handle it
            throw $e;
        }
    }

    /**
     * Generic method to retrieve all records, enforcing tenant scope.
     */
    public function all(array $where = []): array
    {
        // $this->getTenantScope handles adding "WHERE" if needed
        $sql = "SELECT * FROM {$this->table} " . $this->getTenantScope($where);
        
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log database query failure in the all method
            Log::error("Database ALL query failed in Model '{$this->table}'. Error: " . $e->getMessage(), [
                'user_id' => Auth::userId(), 
                'tenant_id' => $this->currentTenantId,
                'sql' => $sql // Log the generated SQL for debugging
            ]);
            // Re-throw the exception so the calling controller/service can handle it
            throw $e;
        }
    }
}