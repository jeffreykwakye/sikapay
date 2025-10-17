<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use \Throwable;


class NotificationController extends Controller
{
    // Note: $notificationService, $auth, and $userId are inherited from the Controller base class.

    public function __construct()
    {
        try {
            parent::__construct(); 
            
            // NOTE: The primary login check is often handled by AuthMiddleware 
            // for the route group, but we keep the explicit check here for defense-in-depth 
            // and to ensure $this->userId is reliably set.
            if (!$this->auth->check()) {
                $this->redirect('/login');
            }
        } catch (Throwable $e) {
            // 🚨 CRITICAL: Failed initialization (e.g., Base Controller or inherited service failed)
            Log::critical("NotificationController failed to initialize: " . $e->getMessage());
            ErrorResponder::respond(500, "A critical system error occurred during initialization.");
        }
    }

    /**
     * Displays the notification index page.
     */
    public function index(): void
    {
        try {
            $userId = $this->userId;

            // 1. Mark all unread items as read upon viewing the page (Async operation)
            // Hardened service logs its own errors and returns true/false. We log failure here.
            if (!$this->notificationService->markAllAsRead($userId)) {
                 Log::error("Failed to bulk mark notifications as read for User {$userId}.");
            }
            
            // 2. Fetch the updated list of notifications (now all 'read')
            $notifications = $this->notificationService->getNotifications($userId);
            
            // Handle service failure (if getNotifications returns null, as per Service hardening)
            if ($notifications === null) {
                // If the service failed to fetch data, we log it (already done by service)
                // and display a friendly message instead of a crash.
                $_SESSION['flash_error'] = 'Could not load your notifications due to a temporary error.';
                $notifications = []; // Pass an empty array to prevent view crash
            }
            
            $this->view('notifications/index', [
                'title' => 'My Notifications',
                'notifications' => $notifications,
            ]);

        } catch (Throwable $e) {
            // Catch any unexpected system error during controller execution
            Log::critical("Notification index page failed for User {$this->userId}: " . $e->getMessage());
            ErrorResponder::respond(500, "We could not load your notifications due to a system error.");
        }
    }
    
    /**
     * Marks a specific notification as read and redirects back.
     */
    public function markRead(): void
    {
        $notificationId = 0;

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
                $this->redirect('/notifications');
                return;
            }

            $notificationId = (int)$_POST['id'];
            
            // Use the inherited $this->notificationService
            $success = $this->notificationService->markNotificationAsRead($notificationId, $this->userId);
            
            if (!$success) {
                // Service failed (logged by the service), so we notify the user.
                $_SESSION['flash_error'] = 'Could not mark notification as read.';
            } else {
                 $_SESSION['flash_success'] = 'Notification marked as read.';
            }
            
            $this->redirect('/notifications');

        } catch (Throwable $e) {
            // Now safe to use $notificationId, which defaults to 0 if an exception 
            // happens before it is assigned a value (unlikely, but safe).
            Log::critical("Failed to execute markRead for User {$this->userId} (ID: {$notificationId}): " . $e->getMessage());
            
            $_SESSION['flash_error'] = 'A critical error occurred while updating the notification status.';
            $this->redirect('/notifications');
        }
    }
}