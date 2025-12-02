<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class LeaveApplicationModel extends Model
{
    public function __construct()
    {
        parent::__construct('leave_applications');
    }

    /**
     * Creates a new leave application.
     *
     * @param array $data Contains user_id, tenant_id, leave_type_id, start_date, end_date, total_days, reason.
     * @return int The ID of the new application, or 0 on failure.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table} (
                    user_id, tenant_id, leave_type_id, start_date, end_date, total_days, reason, status
                ) VALUES (
                    :user_id, :tenant_id, :leave_type_id, :start_date, :end_date, :total_days, :reason, :status
                )";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':tenant_id' => $data['tenant_id'],
                ':leave_type_id' => $data['leave_type_id'],
                ':start_date' => $data['start_date'],
                ':end_date' => $data['end_date'],
                ':total_days' => $data['total_days'],
                ':reason' => $data['reason'],
                ':status' => $data['status'] ?? 'pending',
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            Log::error("Failed to create leave application for user {$data['user_id']} (tenant {$data['tenant_id']}). Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Retrieves a single leave application by ID, ensuring tenant and user scope.
     *
     * @param int $id The application ID.
     * @param int $tenantId
     * @param int|null $userId Optional. If provided, ensures the application belongs to this user.
     * @return array|null The leave application record.
     */
    public function findById(int $id, int $tenantId, ?int $userId = null): ?array
    {
        $sql = "SELECT la.*, lt.name as leave_type_name, u.first_name, u.last_name
                FROM {$this->table} la
                JOIN leave_types lt ON la.leave_type_id = lt.id
                JOIN users u ON la.user_id = u.id
                WHERE la.id = :id AND la.tenant_id = :tenant_id";
        
        if ($userId !== null) {
            $sql .= " AND la.user_id = :user_id";
        }

        try {
            $stmt = $this->db->prepare($sql);
            $params = [':id' => $id, ':tenant_id' => $tenantId];
            if ($userId !== null) {
                $params[':user_id'] = $userId;
            }
            $stmt->execute($params);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            Log::error("Failed to find leave application {$id} for tenant {$tenantId}. Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieves all leave applications for a given tenant, with optional status filter.
     *
     * @param int $tenantId
     * @param string|null $status Optional. Filter by status (e.g., 'pending', 'approved').
     * @return array An array of leave application records.
     */
    public function getAllByTenant(int $tenantId, ?string $status = null): array
    {
        $sql = "SELECT la.*, lt.name as leave_type_name, u.first_name, u.last_name
                FROM {$this->table} la
                JOIN leave_types lt ON la.leave_type_id = lt.id
                JOIN users u ON la.user_id = u.id
                WHERE la.tenant_id = :tenant_id";
        
        if ($status !== null) {
            $sql .= " AND la.status = :status";
        }
        $sql .= " ORDER BY la.created_at DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $params = [':tenant_id' => $tenantId];
            if ($status !== null) {
                $params[':status'] = $status;
            }
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve leave applications for tenant {$tenantId} (status: {$status}). Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Counts leave applications for a given tenant, with optional status filter.
     *
     * @param int $tenantId
     * @param string|null $status Optional. Filter by status (e.g., 'pending', 'approved').
     * @return int The number of matching leave applications.
     */
    public function countByTenant(int $tenantId, ?string $status = null): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE tenant_id = :tenant_id";
        
        if ($status !== null) {
            $sql .= " AND status = :status";
        }

        try {
            $stmt = $this->db->prepare($sql);
            $params = [':tenant_id' => $tenantId];
            if ($status !== null) {
                $params[':status'] = $status;
            }
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            Log::error("Failed to count leave applications for tenant {$tenantId} (status: {$status}). Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Retrieves all leave applications for a specific user.
     *
     * @param int $userId
     * @return array An array of leave application records.
     */
    public function getAllByUser(int $userId): array
    {
        $sql = "SELECT la.*, lt.name as leave_type_name
                FROM {$this->table} la
                JOIN leave_types lt ON la.leave_type_id = lt.id
                WHERE la.user_id = :user_id
                ORDER BY la.created_at DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve leave applications for user {$userId}. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Updates the status and approval details of a leave application.
     *
     * @param int $id The application ID.
     * @param int $tenantId
     * @param string $status The new status ('approved', 'rejected', 'cancelled').
     * @param int|null $approvedByUserId The user who approved/rejected it.
     * @return bool True on success, false otherwise.
     */
    public function updateStatus(int $id, int $tenantId, string $status, ?int $approvedByUserId = null): bool
    {
        $sql = "UPDATE {$this->table} SET 
                    status = :status, 
                    approved_by_user_id = :approved_by_user_id,
                    processed_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id AND tenant_id = :tenant_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':tenant_id' => $tenantId,
                ':status' => $status,
                ':approved_by_user_id' => $approvedByUserId,
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Log::error("Failed to update status of leave application {$id} (tenant {$tenantId}). Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves all approved leave applications for a given tenant.
     *
     * @param int $tenantId
     * @return array An array of approved leave application records.
     */
    public function getApprovedByTenant(int $tenantId): array
    {
        $sql = "SELECT la.*, lt.name as leave_type_name, u.first_name, u.last_name
                FROM {$this->table} la
                JOIN leave_types lt ON la.leave_type_id = lt.id
                JOIN users u ON la.user_id = u.id
                WHERE la.tenant_id = :tenant_id AND la.status = 'approved'
                ORDER BY la.start_date DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tenant_id' => $tenantId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve approved leave applications for tenant {$tenantId}. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves staff members who are currently on an approved leave.
     *
     * @param int $tenantId
     * @return array An array of staff members currently on leave.
     */
    public function getOnLeaveStaff(int $tenantId): array
    {
        $sql = "SELECT la.*, lt.name as leave_type_name, u.first_name, u.last_name, u.email
                FROM {$this->table} la
                JOIN leave_types lt ON la.leave_type_id = lt.id
                JOIN users u ON la.user_id = u.id
                WHERE la.tenant_id = :tenant_id 
                AND la.status = 'approved' 
                AND la.start_date <= CURDATE() 
                AND la.end_date >= CURDATE()
                ORDER BY la.end_date ASC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tenant_id' => $tenantId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve staff currently on leave for tenant {$tenantId}. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves staff members who are due to return from leave soon (e.g., within the next 7 days).
     *
     * @param int $tenantId
     * @param int $daysAhead Number of days ahead to check for returning staff.
     * @return array An array of staff members returning from leave soon.
     */
    public function getReturningStaff(int $tenantId, int $daysAhead = 7): array
    {
        $sql = "SELECT la.*, lt.name as leave_type_name, u.first_name, u.last_name, u.email
                FROM {$this->table} la
                JOIN leave_types lt ON la.leave_type_id = lt.id
                JOIN users u ON la.user_id = u.id
                WHERE la.tenant_id = :tenant_id 
                AND la.status = 'approved' 
                AND la.end_date < CURDATE() + INTERVAL :days_ahead DAY
                AND la.end_date >= CURDATE()
                ORDER BY la.end_date ASC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tenant_id' => $tenantId, ':days_ahead' => $daysAhead]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve returning staff for tenant {$tenantId}. Error: " . $e->getMessage());
            return [];
        }
    }
}
