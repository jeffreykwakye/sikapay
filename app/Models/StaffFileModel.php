<?php

declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class StaffFileModel extends Model
{
    public function __construct()
    {
        parent::__construct('staff_files');
    }

    /**
     * Retrieves all file records for a specific employee within the current tenant scope.
     *
     * @param int $userId The ID of the employee (user).
     * @return array List of file records.
     */
    public function getFilesByUserId(int $userId): array
    {
        $sql = "SELECT id, file_name, file_type, file_description, uploaded_at, file_path 
                FROM {$this->table} 
                WHERE user_id = :user_id AND tenant_id = :tenant_id
                ORDER BY uploaded_at DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->bindParam(':tenant_id', $this->currentTenantId, \PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Staff file retrieval failed for User {$userId}. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Creates a new staff file record.
     *
     * @param array $data Contains user_id, file_name, file_path, file_type, file_description, etc.
     * @return int|bool The ID of the new record on success, or false on failure.
     */
    public function createFileRecord(array $data): int|bool
    {
        $sql = "INSERT INTO {$this->table} 
                (user_id, tenant_id, file_name, file_path, file_type, file_description, uploaded_by_user_id) 
                VALUES 
                (:user_id, :tenant_id, :file_name, :file_path, :file_type, :file_description, :uploaded_by_user_id)";
        
        // Ensure tenant_id and uploaded_by_user_id are set, assuming they are required.
        $finalData = array_merge([
            'tenant_id' => $this->currentTenantId,
            'uploaded_by_user_id' => Auth::userId(),
            'file_description' => null // Ensure description defaults to null if not provided
        ], $data);

        try {
            $stmt = $this->db->prepare($sql);
            if ($stmt->execute($finalData)) {
                return (int)$this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            Log::error("Staff file creation failed. Error: " . $e->getMessage(), ['data' => $data]);
            return false;
        }
    }
}