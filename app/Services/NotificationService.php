<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Services;

use Jeffrey\Sikapay\Models\NotificationModel;
use Jeffrey\Sikapay\Models\UserModel; // Placeholder for future features

class NotificationService
{
    private NotificationModel $notificationModel;
    // private UserModel $userModel; // Keeping this line commented out until needed

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
        // $this->userModel = new UserModel();
    }
    
    /**
     * Notifies a single, specific user. This is the primary method for sending alerts.
     */
    public function notifyUser(int $tenantId, int $userId, string $type, string $title, ?string $body = null): void
    {
        if ($userId > 0) { 
            $this->notificationModel->createNotification($tenantId, $userId, $type, $title, $body);
        }
    }

    /**
     * Retrieves all notifications for the current user.
     */
    public function getNotifications(int $userId): array
    {
        return $this->notificationModel->getAllForUser($userId);
    }

    /**
     * Gets the count of unread notifications for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        return count($this->notificationModel->getUnreadForUser($userId));
    }
    
    /**
     * Marks a specific notification as read.
     */
    public function markNotificationAsRead(int $notificationId, int $userId): bool
    {
        return $this->notificationModel->markAsRead($notificationId, $userId);
    }


    /**
     * Marks all unread notifications for a specific user as read.
     */
    public function markAllAsRead(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
    
        return $this->notificationModel->markAllAsRead($userId);
    }
}