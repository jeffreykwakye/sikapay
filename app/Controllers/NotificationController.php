<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use Jeffrey\Sikapay\Core\Validator;
use \Throwable;


class NotificationController extends Controller
{
   
    public function __construct()
    {
        try {
            parent::__construct(); 
            
            // Defense-in-depth check
            if (!$this->auth->check()) {
                $this->redirect('/login');
            }
        } catch (Throwable $e) {
            // CRITICAL: Failed initialization
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
            if (!$this->notificationService->markAllAsRead($userId)) {
                Log::error("Failed to bulk mark notifications as read for User {$userId}.");
            }
            
            // 2. Fetch the updated list of notifications (now all 'read')
            $notifications = $this->notificationService->getNotifications($userId);
            
            // Handle service failure (if getNotifications returns null, as per Service hardening)
            if ($notifications === null) {
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
            // 1. HARDENED: Use Validator for input and POST method check
            $validator = new Validator($_POST);
            
            $validator->validate([
                'id' => 'required|int|min:1', // Must be a positive integer
            ]);

            if ($validator->fails()) {
                $_SESSION['flash_error'] = 'Invalid notification ID received. Action failed.';
                Log::warning("Invalid ID for markRead action.", ['input' => $_POST, 'user_id' => $this->userId]);
                $this->redirect('/notifications');
                return;
            }

            // 2. Safely retrieve the validated integer ID
            $notificationId = $validator->get('id', 'int');
            
            // 3. Execute the service call with the authenticated user ID for scope check
            $success = $this->notificationService->markNotificationAsRead($notificationId, $this->userId);
            
            if (!$success) {
                // Service failed (logged by the service), so we notify the user.
                $_SESSION['flash_error'] = 'Could not mark notification as read (The notification may not exist or does not belong to you).';
            } else {
                $_SESSION['flash_success'] = 'Notification marked as read.';
            }
            
            $this->redirect('/notifications');

        } catch (Throwable $e) {
            // Catch unexpected system errors
            Log::critical("Failed to execute markRead for User {$this->userId} (ID: {$notificationId}): " . $e->getMessage());
            
            $_SESSION['flash_error'] = 'A critical system error occurred while updating the notification status.';
            $this->redirect('/notifications');
        }
    }
}