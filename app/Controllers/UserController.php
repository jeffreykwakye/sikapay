<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Core\Validator;
use Jeffrey\Sikapay\Models\UserModel;
use Jeffrey\Sikapay\Models\AuditModel; // NEW

class UserController extends Controller
{
    protected UserModel $userModel;
    protected AuditModel $auditModel; // NEW

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->auditModel = new AuditModel(); // NEW
    }

    /**
     * Display the change password form or handle the password update.
     */
    public function changePassword(): void
    {
        $this->checkPermission('self:update_profile');

        $currentUserId = Auth::userId();
        $user = $this->userModel->find($currentUserId);

        if (!$user) {
            Log::critical("User ID {$currentUserId} not found when trying to change password.");
            ErrorResponder::respond(404, "User not found.");
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validator = new Validator($_POST);
            $validator->validate([
                'current_password' => 'required',
                'new_password' => 'required|min:8',
                'confirm_new_password' => 'required|same:new_password',
            ]);

            if ($validator->fails()) {
                $_SESSION['flash_error'] = "Password update failed: " . implode('<br>', $validator->errors());
                $_SESSION['flash_input'] = $validator->all();
                $this->redirect('/my-account/change-password');
                return;
            }

            $currentPassword = $validator->get('current_password');
            $newPassword = $validator->get('new_password');

            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                $_SESSION['flash_error'] = "Password update failed: Current password is incorrect.";
                $_SESSION['flash_input'] = $validator->all();
                $this->redirect('/my-account/change-password');
                return;
            }

            // Update password
            try {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $this->userModel->updateUser($currentUserId, ['password' => $hashedPassword]);

                // Log audit event
                $this->auditModel->log(
                    $this->tenantId,
                    'USER_PASSWORD_CHANGED',
                    ['user_id' => $currentUserId]
                );

                $_SESSION['flash_success'] = "Your password has been updated successfully!";
                $this->redirect('/my-account/change-password');

            } catch (\Throwable $e) {
                Log::error("Failed to update password for User {$currentUserId}: " . $e->getMessage());
                $_SESSION['flash_error'] = "A critical error occurred while updating your password. Please try again.";
                $this->redirect('/my-account/change-password');
            }

        } else {
            // GET request: Display form
            $this->view('user/change_password', [
                'title' => 'Change Password',
            ]);
        }
    }
}
