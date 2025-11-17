<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Services;

use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Models\NotificationModel;
use Jeffrey\Sikapay\Models\UserModel;
use Jeffrey\Sikapay\Services\EmailService; // ADDED
use \Throwable;

class NotificationService
{
    private NotificationModel $notificationModel;
    private UserModel $userModel;
    private EmailService $emailService; // ADDED

    public function __construct()
    {
        try {
            $this->notificationModel = new NotificationModel();
            $this->userModel = new UserModel();
            $this->emailService = new EmailService(); // ADDED
        } catch (Throwable $e) {
            Log::critical("NotificationService failed to instantiate its models/services.", [
                'error' => $e->getMessage(),
                'file' => $e->getFile()
            ]);
            throw $e;
        }
    }

    /**
     * Notifies all users belonging to a specific role within a tenant.
     */
    public function createNotificationForRole(int $tenantId, string $roleName, string $type, string $title, ?string $body = null): void
    {
        try {
            $usersInRole = $this->userModel->getUsersByRole($tenantId, $roleName);
            
            if (!empty($usersInRole)) {
                foreach ($usersInRole as $user) {
                    $this->notifyUser($tenantId, $user['id'], $type, $title, $body);
                }
            }
        } catch (Throwable $e) {
            Log::error("Failed to create notifications for role {$roleName} in Tenant {$tenantId}.", [
                'error' => $e->getMessage(),
                'type' => $type
            ]);
        }
    }

    /**
     * Notifies a single, specific user. This is the primary method for sending alerts.
     */
    public function notifyUser(int $tenantId, int $userId, string $type, string $title, ?string $body = null): void
    {
        if ($userId <= 0) { 
            return;
        }
        
        try {
            // 1. Create in-app notification
            $this->notificationModel->createNotification($tenantId, $userId, $type, $title, $body);

            // 2. Send email notification
            $user = $this->userModel->find($userId);
            if ($user && !empty($user['email'])) {
                $emailBody = $body ?? $title; // Use body if available, otherwise title
                $this->emailService->send($user['email'], $title, $emailBody);
            }

        } catch (Throwable $e) {
            Log::error("Failed to create notification or send email for User {$userId}.", [
                'tenant_id' => $tenantId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }
    


    /**
     * Retrieves all notifications for the current user.
     * @return array|null Null on critical error, allowing caller to detect failure.
     */
    public function getNotifications(int $userId): ?array
    {
        try {
            return $this->notificationModel->getAllForUser($userId);
        } catch (Throwable $e) {
            // Catch unexpected errors.
            Log::error("Failed to fetch notifications for User {$userId}.", [
                'error' => $e->getMessage(),
                'acting_user' => Auth::userId()
            ]);
            // Return null, indicating failure to the controller.
            return null;
        }
    }

    /**
     * Retrieves the most recent notifications for a user (for navbar).
     * @return array An array of notification records.
     */
    public function getRecentNotifications(int $userId, int $limit = 5): array
    {
        try {
            return $this->notificationModel->getRecentNotifications($userId, $limit);
        } catch (Throwable $e) {
            Log::error("Failed to fetch recent notifications for User {$userId}.", [
                'error' => $e->getMessage()
            ]);
            return []; // Return empty array on failure
        }
    }

    /**
     * Gets the count of unread notifications for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        try {
            // The Model returns an array of IDs; count is safer to wrap here.
            return count($this->notificationModel->getUnreadForUser($userId));
        } catch (Throwable $e) {
            // Catch unexpected errors.
            Log::error("Failed to count unread notifications for User {$userId}.", [
                'error' => $e->getMessage(),
                'acting_user' => Auth::userId()
            ]);
            // Return 0 as a safe fallback for the UI count badge.
            return 0;
        }
    }
    
    /**
     * Marks a specific notification as read.
     */
    public function markNotificationAsRead(int $notificationId, int $userId): bool
    {
        try {
            return $this->notificationModel->markAsRead($notificationId, $userId);
        } catch (Throwable $e) {
            // Catch unexpected errors.
            Log::error("Failed to mark notification ID {$notificationId} as read.", [
                'error' => $e->getMessage(),
                'target_user' => $userId
            ]);
            // Return false on failure.
            return false;
        }
    }


    /**
     * Marks all unread notifications for a specific user as read.
     */
    public function markAllAsRead(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
    
        try {
            return $this->notificationModel->markAllAsRead($userId);
        } catch (Throwable $e) {
            // Catch unexpected errors.
            Log::error("Failed to mark all notifications as read for User {$userId}.", [
                'error' => $e->getMessage()
            ]);
            // Return false on failure.
            return false;
        }
    }
}