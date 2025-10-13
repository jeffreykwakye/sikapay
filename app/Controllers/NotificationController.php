<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;


class NotificationController extends Controller
{
    // Note: No need to define $notificationService here; it's inherited and protected.

    public function __construct()
    {
        parent::__construct(); 
        
        // Ensure user is logged in using the INSTANCE METHOD: $this->auth->check()
        if (!$this->auth->check()) {
            $this->redirect('/login');
        }
    }

    /**
     * Shows the list of all notifications for the logged-in user.
     */
    public function index(): void
    {
        // Use the inherited $this->notificationService
        $notifications = $this->notificationService->getNotifications($this->userId);
        
        $this->view('notifications/index', [
            'title' => 'My Notifications',
            'notifications' => $notifications,
        ]);
    }
    
    /**
     * Marks a specific notification as read and redirects back.
     */
    public function markRead(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
            $this->redirect('/notifications');
            return;
        }

        $notificationId = (int)$_POST['id'];
        
        // Use the inherited $this->notificationService
        $success = $this->notificationService->markNotificationAsRead($notificationId, $this->userId);
        
        if (!$success) {
            $_SESSION['flash_error'] = 'Could not mark notification as read.';
        }
        
        $this->redirect('/notifications');
    }
}