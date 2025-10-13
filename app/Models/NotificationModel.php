<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;

class NotificationModel extends Model
{
    public function __construct()
    {
        parent::__construct('notifications');
    }

    /**
     * Inserts a new notification record for a specific user.
     */
    public function createNotification(int $tenantId, int $userId, string $type, string $title, ?string $body = null): int
    {
        $sql = "INSERT INTO notifications 
                (tenant_id, user_id, type, title, body, is_read) 
                VALUES (:tenant_id, :user_id, :type, :title, :body, FALSE)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':tenant_id' => $tenantId,
            ':user_id' => $userId,
            ':type' => $type,
            ':title' => $title,
            ':body' => $body,
        ]);
        
        return (int)$this->db->lastInsertId();
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
                LIMIT 50"; // Limit history for performance
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Marks a notification as read.
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $sql = "UPDATE {$this->table} SET is_read = TRUE WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        
        // Include user_id in WHERE clause for security (user can only update their own notification)
        return $stmt->execute([
            ':id' => $notificationId,
            ':user_id' => $userId
        ]);
    }
}