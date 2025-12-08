<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log;     // Used to log technical errors
use Jeffrey\Sikapay\Core\Auth;    // Used to get the current user's ID for auditing
use \PDOException;

/**
 * Manages data access for the 'employment_history' table, 
 * which logs key changes like salary, position, and transfers.
 */
class EmploymentHistoryModel extends Model
{
    protected string $table = 'employment_history'; 

    public function __construct()
    {
        parent::__construct($this->table); 
    }

    /**
     * Creates a new employment history record.
     * * @param array $data Expected keys: user_id, effective_date, record_type, old_salary, new_salary, notes.
     * @return bool True on success.
     * @throws \InvalidArgumentException If required user_id or tenant_id is missing.
     * @throws PDOException The exception is re-thrown for transaction rollback handling in the Controller.
     */
    public function create(array $data): bool
    {
        if (!isset($data['user_id']) || !isset($data['tenant_id'])) {
            throw new \InvalidArgumentException("User ID and Tenant ID are required for employment history record.");
        }
        
        $sql = "INSERT INTO {$this->table} (
            user_id, 
            tenant_id, 
            effective_date, 
            record_type, 
            old_salary, 
            new_salary, 
            notes
        ) VALUES (
            :user_id, 
            :tenant_id, 
            :effective_date, 
            :record_type, 
            :old_salary, 
            :new_salary, 
            :notes
        )";

        // Parameters array for execution and logging
        $params = [
            ':user_id' => $data['user_id'],
            ':tenant_id' => $data['tenant_id'], 
            ':effective_date' => $data['effective_date'],
            ':record_type' => $data['record_type'],
            ':old_salary' => $data['old_salary'],
            ':new_salary' => $data['new_salary'],
            ':notes' => $data['notes'] ?? null,
        ];

        try {
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute($params);

            return $success;
            
        } catch (PDOException $e) {
            // Log the full technical error details before re-throwing
            Log::error("DB Create Error in EmploymentHistoryModel::create. Error: " . $e->getMessage(), [
                'user_id' => Auth::userId(), 
                'tenant_id' => $this->currentTenantId,
                'sql_params' => $params
            ]);
            
            // Re-throw the exception: Re-throw the exception for transaction rollback handling in the Controller.
            throw $e;
        }
    }


}