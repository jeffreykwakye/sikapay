<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class SupportMessageModel extends Model
{
    public function __construct()
    {
        parent::__construct('support_messages');
    }

    /**
     * Creates a new support message.
     *
     * @param array $data Associative array with 'tenant_id', 'user_id', 'subject', 'message'.
     * @return int The ID of the newly created message, or 0 on failure.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table} (tenant_id, user_id, subject, message) 
                VALUES (:tenant_id, :user_id, :subject, :message)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $data['tenant_id'],
                ':user_id' => $data['user_id'],
                ':subject' => $data['subject'],
                ':message' => $data['message'],
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            Log::error("Failed to create support message for Tenant {$data['tenant_id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieves all support messages for a specific tenant.
     * Ordered by creation date (newest first).
     *
     * @param int $tenantId The ID of the tenant.
     * @return array An array of support message records.
     */
    public function getMessagesByTenant(int $tenantId): array
    {
        $sql = "SELECT sm.*, u.first_name, u.last_name, u.email 
                FROM {$this->table} sm
                JOIN users u ON sm.user_id = u.id
                WHERE sm.tenant_id = :tenant_id
                ORDER BY sm.created_at DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tenant_id' => $tenantId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve support messages for Tenant {$tenantId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves all support messages from all tenants for the super admin.
     * Ordered by status ('open' first) and then by date.
     *
     * @return array An array of all support message records.
     */
    public function getAllMessages(): array
    {
        $sql = "SELECT 
                    sm.*, 
                    u.first_name, 
                    u.last_name, 
                    u.email,
                    t.name as tenant_name
                FROM {$this->table} sm
                JOIN users u ON sm.user_id = u.id
                JOIN tenants t ON sm.tenant_id = t.id
                ORDER BY 
                    CASE sm.status
                        WHEN 'open' THEN 1
                        WHEN 'reopened' THEN 2
                        WHEN 'in_progress' THEN 3
                        WHEN 'closed' THEN 4
                        ELSE 5
                    END, 
                    sm.created_at DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve all support messages for Super Admin: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves a single support message by its ID.
     *
     * @param int $messageId The ID of the support message.
     * @return array|null The support message record, or null if not found.
     */
    public function find(int $messageId): ?array
    {
        $sql = "SELECT sm.*, u.first_name, u.last_name, u.email 
                FROM {$this->table} sm
                JOIN users u ON sm.user_id = u.id
                WHERE sm.id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $messageId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            Log::error("Failed to find support message by ID {$messageId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Updates the status and/or super admin response of a support message.
     *
     * @param int $messageId The ID of the support message.
     * @param array $data Associative array with fields to update (e.g., 'status', 'super_admin_response').
     * @return bool True on success, false on failure.
     */
    public function updateMessage(int $messageId, array $data): bool
    {
        $setClauses = [];
        $bindParams = [':id' => $messageId];

        foreach ($data as $key => $value) {
            // Only allow specific fields to be updated
            if (in_array($key, ['status', 'super_admin_response'])) {
                $setClauses[] = "{$key} = :{$key}";
                $bindParams[":{$key}"] = $value;
            }
        }

        if (empty($setClauses)) {
            return false; // No valid fields to update
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($bindParams);
        } catch (PDOException $e) {
            Log::error("Failed to update support message ID {$messageId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Appends a new message from the tenant to an existing support message.
     * Also updates the status and updated_at timestamp.
     *
     * @param int $messageId The ID of the support message.
     * @param string $newMessage The new message content to append.
     * @param int $tenantId The tenant ID to ensure security.
     * @param string|null $newStatus Optional: The new status for the ticket.
     * @return bool True on success, false on failure.
     */
    public function appendMessage(int $messageId, string $newMessage, int $tenantId, ?string $newStatus = null): bool
    {
        // Fetch the current message to append to it
        $currentMessage = $this->find($messageId);

        if (!$currentMessage || (int)$currentMessage['tenant_id'] !== $tenantId) {
            Log::warning("Attempt to append message to non-existent or unauthorized ticket ID {$messageId} by Tenant {$tenantId}.");
            return false;
        }

        $appendedContent = $currentMessage['message'] . "\n\n--- Tenant Reply (" . date('Y-m-d H:i:s') . ") ---\n" . $newMessage;

        $setClauses = ['message = :message', 'updated_at = CURRENT_TIMESTAMP'];
        $bindParams = [
            ':message' => $appendedContent,
            ':id' => $messageId,
            ':tenant_id' => $tenantId // Used in WHERE clause for security
        ];

        if ($newStatus !== null) {
            $setClauses[] = 'status = :status';
            $bindParams[':status'] = $newStatus;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE id = :id AND tenant_id = :tenant_id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($bindParams);
        } catch (PDOException $e) {
            Log::error("Failed to append message to ticket ID {$messageId} for Tenant {$tenantId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Counts the number of open or reopened support tickets.
     * This is typically for the Super Admin dashboard/sidebar.
     *
     * @return int The count of open/reopened tickets.
     */
    public function getOpenTicketsCount(): int
    {
        $sql = "SELECT COUNT(id) FROM {$this->table} WHERE status IN ('open', 'reopened')";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            Log::error("Failed to get open tickets count: " . $e->getMessage());
            return 0;
        }
    }
}
