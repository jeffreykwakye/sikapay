<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use Jeffrey\Sikapay\Models\LeaveTypeModel;
use Jeffrey\Sikapay\Models\LeaveApplicationModel;
use Jeffrey\Sikapay\Models\LeaveBalanceModel;
use Jeffrey\Sikapay\Services\EmailService;
use Jeffrey\Sikapay\Services\NotificationService;
use Jeffrey\Sikapay\Models\UserModel;
use Jeffrey\Sikapay\Models\AuditModel;
use \Throwable;

class LeaveController extends Controller
{
    private LeaveTypeModel $leaveTypeModel;
    private LeaveApplicationModel $leaveApplicationModel;
    private LeaveBalanceModel $leaveBalanceModel;
    protected NotificationService $notificationService;
    private EmailService $emailService;
    protected UserModel $userModel;
    protected AuditModel $auditModel;

    public function __construct()
    {
        parent::__construct();
        $this->leaveTypeModel = new LeaveTypeModel();
        $this->leaveApplicationModel = new LeaveApplicationModel();
        $this->leaveBalanceModel = new LeaveBalanceModel();
        $this->notificationService = new NotificationService();
        $this->emailService = new EmailService();
        $this->userModel = new UserModel();
        $this->auditModel = new AuditModel();
    }

    public function index(): void
    {
        try {
            $this->view('leave/index', [
                'title' => 'Leave Management Dashboard',
                'isApprover' => $this->auth->hasPermission('leave:approve'),
                'pendingCount' => $this->leaveApplicationModel->countByTenant($this->tenantId, 'pending'),
                'onLeaveCount' => count($this->leaveApplicationModel->getOnLeaveStaff($this->tenantId)),
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to load leave management dashboard. Error: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load the leave management dashboard due to a system error.");
        }
    }

    public function pending(): void
    {
        $this->checkPermission('leave:approve');
        try {
            $pendingApplications = $this->leaveApplicationModel->getAllByTenant($this->tenantId, 'pending');
            $this->view('leave/pending', [
                'title' => 'Pending Leave Applications',
                'pendingApplications' => $pendingApplications,
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to load pending leave applications. Error: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load pending leave applications due to a system error.");
        }
    }

    public function approved(): void
    {
        $this->checkPermission('leave:approve');
        try {
            $approvedApplications = $this->leaveApplicationModel->getApprovedByTenant($this->tenantId);
            $this->view('leave/approved', [
                'title' => 'Approved Leave Applications',
                'approvedApplications' => $approvedApplications,
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to load approved leave applications. Error: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load approved leave applications due to a system error.");
        }
    }

    public function onLeave(): void
    {
        $this->checkPermission('leave:approve');
        try {
            $onLeaveStaff = $this->leaveApplicationModel->getOnLeaveStaff($this->tenantId);
            $this->view('leave/on_leave', [
                'title' => 'Staff Currently On Leave',
                'onLeaveStaff' => $onLeaveStaff,
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to load on-leave staff list. Error: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load on-leave staff list due to a system error.");
        }
    }

    public function returning(): void
    {
        $this->checkPermission('leave:approve');
        try {
            $returningStaff = $this->leaveApplicationModel->getReturningStaff($this->tenantId);
            $this->view('leave/returning', [
                'title' => 'Staff Returning Soon',
                'returningStaff' => $returningStaff,
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to load returning staff list. Error: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load returning staff list due to a system error.");
        }
    }

    public function manageTypes(): void
    {
        $this->checkPermission('leave:manage_types');
        try {
            $leaveTypes = $this->leaveTypeModel->getAllByTenant($this->tenantId);
            $this->view('leave/types', [
                'title' => 'Manage Leave Types',
                'leaveTypes' => $leaveTypes,
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to load leave types management page. Error: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load the leave types page.");
        }
    }

    public function createType(): void
    {
        $this->checkPermission('leave:manage_types');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/leave/types');
        }

        $validator = new \Jeffrey\Sikapay\Core\Validator($_POST);
        $validator->validate([
            'name' => 'required|min:3|max:100',
            'default_days' => 'required|numeric|min:0',
            'is_accrued' => 'optional|bool',
            'is_active' => 'optional|bool',
            'is_paid' => 'optional|bool', // NEW
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = "Error creating leave type: " . implode('<br>', $validator->errors());
            $this->redirect('/leave/types');
            return;
        }

        $data = [
            'name' => $validator->get('name'),
            'default_days' => $validator->get('default_days', 'int'),
            'is_accrued' => $validator->get('is_accrued', 'bool', false),
            'is_active' => $validator->get('is_active', 'bool', false),
            'is_paid' => $validator->get('is_paid', 'bool', false), // NEW
        ];

        try {
            $newId = $this->leaveTypeModel->create($this->tenantId, $data);
            if ($newId) {
                $_SESSION['flash_success'] = "Leave type '{$data['name']}' created successfully.";
            } else {
                $_SESSION['flash_error'] = "Failed to create leave type.";
            }
        } catch (Throwable $e) {
            Log::error("Failed to create leave type for Tenant {$this->tenantId}: " . $e->getMessage());
            $_SESSION['flash_error'] = "A system error occurred while creating the leave type.";
        }

        $this->redirect('/leave/types');
    }

    public function updateType(string $id): void
    {
        $id = (int)$id;
        $this->checkPermission('leave:manage_types');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/leave/types');
        }

        $validator = new \Jeffrey\Sikapay\Core\Validator($_POST);
        $validator->validate([
            'name' => 'required|min:3|max:100',
            'default_days' => 'required|numeric|min:0',
            'is_accrued' => 'optional|bool',
            'is_active' => 'optional|bool',
            'is_paid' => 'optional|bool', // NEW
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = "Error updating leave type: " . implode('<br>', $validator->errors());
            $this->redirect('/leave/types');
            return;
        }

        $data = [
            'name' => $validator->get('name'),
            'default_days' => $validator->get('default_days', 'int'),
            'is_accrued' => $validator->get('is_accrued', 'bool', false),
            'is_active' => $validator->get('is_active', 'bool', false),
            'is_paid' => $validator->get('is_paid', 'bool', false), // NEW
        ];

        try {
            $success = $this->leaveTypeModel->update($id, $this->tenantId, $data);
            if ($success) {
                $_SESSION['flash_success'] = "Leave type '{$data['name']}' updated successfully.";
            } else {
                $_SESSION['flash_error'] = "Failed to update leave type or no changes were made.";
            }
        } catch (Throwable $e) {
            Log::error("Failed to update leave type {$id} for Tenant {$this->tenantId}: " . $e->getMessage());
            $_SESSION['flash_error'] = "A system error occurred while updating the leave type.";
        }

        $this->redirect('/leave/types');
    }

    public function deleteType(string $id): void
    {
        $id = (int)$id;
        $this->checkPermission('leave:manage_types');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/leave/types');
        }

        try {
            // Note: Add a check here if leave type is in use by applications before deleting.
            // For now, we will proceed with deletion.
            $success = $this->leaveTypeModel->delete($id, $this->tenantId);
            if ($success) {
                $_SESSION['flash_success'] = "Leave type deleted successfully.";
            } else {
                $_SESSION['flash_error'] = "Failed to delete leave type. It might be in use.";
            }
        } catch (Throwable $e) {
            Log::error("Failed to delete leave type {$id} for Tenant {$this->tenantId}: " . $e->getMessage());
            $_SESSION['flash_error'] = "A system error occurred while deleting the leave type.";
        }

        $this->redirect('/leave/types');
    }

    public function approveLeave(string $id): void
    {
        $id = (int)$id;
        $this->checkPermission('leave:approve');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/leave');
        }

        try {
            $application = $this->leaveApplicationModel->findById($id, $this->tenantId);

            if (!$application || $application['status'] !== 'pending') {
                $_SESSION['flash_error'] = "Invalid or already processed leave application.";
                $this->redirect('/leave');
                return;
            }

            $db = $this->leaveApplicationModel->getDB();
            $db->beginTransaction();
            
            $success = $this->leaveApplicationModel->updateStatus($id, $this->tenantId, 'approved', $this->userId);
            if ($success) {
                // Deduct from balance
                $this->leaveBalanceModel->updateBalance(
                    (int)$application['user_id'], 
                    $this->tenantId, 
                    (int)$application['leave_type_id'], 
                    -(float)$application['total_days']
                );
                $_SESSION['flash_success'] = "Leave application approved.";

                // --- START NOTIFICATION LOGIC ---
                try {
                    $applicant = $this->userModel->find((int)$application['user_id']);
                    if ($applicant) {
                        $inAppTitle = "Your leave application has been approved.";
                        $link = '/my-account';
                        $emailBody = "Hello {$applicant['first_name']},<br><br>Your leave application for <b>{$application['leave_type_name']}</b> from " . date('M j, Y', strtotime($application['start_date'])) . " to " . date('M j, Y', strtotime($application['end_date'])) . " has been approved.<br><br>You can view your leave details in your SikaPay account.";

                        $this->notificationService->notifyUser(
                            $this->tenantId, // Correct: Use the current controller's tenant ID
                            (int)$applicant['id'],
                            'leave_approved',
                            $inAppTitle,
                            $link,
                            $emailBody
                        );
                    }
                } catch (\Throwable $e) {
                    Log::error("Failed to send leave approval notifications for application {$id}: " . $e->getMessage());
                }
                // --- END NOTIFICATION LOGIC ---

            } else {
                throw new \Exception("Failed to update application status.");
            }

            $db->commit();
        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            Log::error("Failed to approve leave application {$id}: " . $e->getMessage());
            $_SESSION['flash_error'] = "A system error occurred while approving the application.";
        }

        $this->redirect('/leave');
    }

    public function rejectLeave(string $id): void
    {
        $id = (int)$id;
        $this->checkPermission('leave:approve');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/leave');
        }
        
        try {
            $application = $this->leaveApplicationModel->findById($id, $this->tenantId);

            if (!$application || $application['status'] !== 'pending') {
                $_SESSION['flash_error'] = "Invalid or already processed leave application.";
                $this->redirect('/leave');
                return;
            }

            $success = $this->leaveApplicationModel->updateStatus($id, $this->tenantId, 'rejected', $this->userId);
            if ($success) {
                $_SESSION['flash_success'] = "Leave application rejected.";

                // --- START NOTIFICATION LOGIC ---
                try {
                    $applicant = $this->userModel->find((int)$application['user_id']);
                    if ($applicant) {
                        $inAppTitle = "Your leave application has been rejected.";
                        $link = '/my-account';
                        $emailBody = "Hello {$applicant['first_name']},<br><br>Your leave application for <b>{$application['leave_type_name']}</b> from " . date('M j, Y', strtotime($application['start_date'])) . " to " . date('M j, Y', strtotime($application['end_date'])) . " has been rejected.<br><br>Please contact your manager for more details.";

                        $this->notificationService->notifyUser(
                            $this->tenantId, // Correct: Use the current controller's tenant ID
                            (int)$applicant['id'],
                            'leave_rejected',
                            $inAppTitle,
                            $link,
                            $emailBody
                        );
                    }
                } catch (\Throwable $e) {
                    Log::error("Failed to send leave rejection notifications for application {$id}: " . $e->getMessage());
                }
                // --- END NOTIFICATION LOGIC ---
                
            } else {
                $_SESSION['flash_error'] = "Failed to reject leave application.";
            }
        } catch (Throwable $e) {
            Log::error("Failed to reject leave application {$id}: " . $e->getMessage());
            $_SESSION['flash_error'] = "A system error occurred while rejecting the application.";
        }

        $this->redirect('/leave');
    }

    /**
     * API endpoint to get details for a single leave application.
     * Returns JSON data for a specific leave application by ID.
     */
    public function getLeaveApplicationDetails(string $id): void
    {
        $id = (int)$id;
        header('Content-Type: application/json');

        try {
            $application = $this->leaveApplicationModel->findById($id, $this->tenantId);

            if (!$application) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Leave application not found or you do not have permission to view it.']);
                exit;
            }

            // Fetch employee details
            $employee = $this->userModel->find((int)$application['user_id']);
            $employeeName = $employee ? $employee['first_name'] . ' ' . $employee['last_name'] : 'Unknown Employee';

            // Fetch remaining leave balance for this leave type for the employee
            $leaveBalance = $this->leaveBalanceModel->getBalance((int)$application['user_id'], $this->tenantId, (int)$application['leave_type_id']);
            
            $data = [
                'id' => $application['id'],
                'employee_name' => $employeeName,
                'leave_type_name' => $application['leave_type_name'],
                'start_date' => date('M d, Y', strtotime($application['start_date'])),
                'end_date' => date('M d, Y', strtotime($application['end_date'])),
                'total_days' => (float)$application['total_days'],
                'reason' => $application['reason'],
                'submitted_date' => date('M d, Y H:i', strtotime($application['created_at'])),
                'status' => $application['status'],
                'remaining_balance' => $leaveBalance['balance'] ?? 0, // Default to 0 if no balance record
                'user_id' => $application['user_id'], // Include user_id for potential future use or debugging
            ];

            echo json_encode(['success' => true, 'data' => $data]);
            exit;

        } catch (Throwable $e) {
            Log::error("Failed to fetch leave application details for ID {$id}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'An error occurred while fetching leave application details.']);
            exit;
        }
    }

    /**
     * API endpoint to mark a leave application as returned.
     * Changes the status of an approved leave application to 'returned'.
     */
    public function markAsReturned(string $id): void
    {
        $id = (int)$id;
        $this->checkPermission('leave:approve'); // Same permission as approving leave
        header('Content-Type: application/json');

        try {
            $application = $this->leaveApplicationModel->findById($id, $this->tenantId);

            if (!$application) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Leave application not found.']);
                exit;
            }

            // Only allow marking as returned if status is 'approved'
            // Consider also if end_date has passed or is today for stricter logic
            if ($application['status'] !== 'approved') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cannot mark leave as returned. Application is not in an approved state.']);
                exit;
            }

            $success = $this->leaveApplicationModel->updateStatus($id, $this->tenantId, 'returned', $this->userId);

            if ($success) {
                // Log the action
                $this->auditModel->log(
                    $this->tenantId, 
                    'Leave Application Marked as Returned: ' . $application['leave_type_name'],
                    ['leave_application_id' => $id, 'user_id' => $application['user_id']]
                );

                echo json_encode(['success' => true, 'message' => 'Leave application marked as returned.']);
                exit;
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to mark leave application as returned.']);
                exit;
            }

        } catch (Throwable $e) {
            Log::error("Failed to mark leave application ID {$id} as returned: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'An error occurred while marking leave application as returned.']);
            exit;
        }
    }
}
