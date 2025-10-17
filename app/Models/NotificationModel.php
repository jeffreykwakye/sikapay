<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Auth; 
use Jeffrey\Sikapay\Core\Log; 
use \PDOException;

class NotificationModel extends Model
{
    public function __construct()
    {
        parent::__construct('notifications');
    }

    /**
     * Inserts a new notification record for a specific user.
     * @return int The ID of the inserted notification, or 0 on failure.
     */
    public function createNotification(int $tenantId, int $userId, string $type, string $title, ?string $body = null): int
    {
        $sql = "INSERT INTO notifications 
                (tenant_id, user_id, type, title, body, is_read) 
                VALUES (:tenant_id, :user_id, :type, :title, :body, FALSE)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $tenantId,
                ':user_id' => $userId,
                ':type' => $type,
                ':title' => $title,
                ':body' => $body,
            ]);
            
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            // Log failure in notification creation
            Log::error("Notification CREATE failed for User {$userId} (Tenant {$tenantId}). Error: " . $e->getMessage(), [
                'type' => $type,
                'title' => $title
            ]);
            // Return 0, allowing the application flow to continue without a user-visible crash.
            return 0; 
        }
    }

    /**
     * Retrieves all notifications (read and unread) for a specific user.
     */
    public function getAllForUser(int $userId): array
    {
        $sql = "SELECT id, type, title, body, is_read, created_at 
                FROM {$this->table} 
                WHERE user_id = :user_id
                ORDER BY created_at DESC 
                LIMIT 50";
                
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log failure in read operation
            Log::error("Notification READ (getAllForUser) failed for User {$userId}. Error: " . $e->getMessage(), [
                'acting_user_id' => Auth::userId()
            ]);
            // Re-throw the exception: UI elements often depend on data for rendering.
            throw $e;
        }
    }
    
    /**
     * Retrieves unread notifications for a specific user (used for count).
     */
    public function getUnreadForUser(int $userId): array
    {
        $sql = "SELECT id 
                FROM {$this->table} 
                WHERE user_id = :user_id AND is_read = FALSE
                LIMIT 10"; 
                
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log failure in count/read operation
            Log::error("Notification READ (getUnreadForUser) failed for User {$userId}. Error: " . $e->getMessage(), [
                'acting_user_id' => Auth::userId()
            ]);
            // Return empty array as a safe fallback for UI features like notification counts.
            return []; 
        }
    }
    
    /**
     * Marks a notification as read.
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $sql = "UPDATE {$this->table} SET is_read = TRUE, read_at = NOW() WHERE id = :id AND user_id = :user_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':id' => $notificationId,
                ':user_id' => $userId
            ]);
        } catch (PDOException $e) {
            // Log failure in update operation
            Log::error("Notification UPDATE (markAsRead) failed for ID {$notificationId}. Error: " . $e->getMessage(), [
                'target_user_id' => $userId,
                'acting_user_id' => Auth::userId()
            ]);
            return false; // Indicate failure to the caller
        }
    }


    /**
     * Marks all unread notifications for a specific user as read in the database.
     */
    public function markAllAsRead(int $userId): bool
    {
        $sql = "UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE user_id = :user_id AND is_read = FALSE";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':user_id' => $userId]);
        } catch (PDOException $e) {
            // Log failure in bulk update operation
            Log::error("Notification UPDATE (markAllAsRead) failed for User {$userId}. Error: " . $e->getMessage(), [
                'acting_user_id' => Auth::userId()
            ]);
            return false; // Indicate failure to the caller
        }
    }
}