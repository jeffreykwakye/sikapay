<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use Jeffrey\Sikapay\Models\LeaveTypeModel;
use Jeffrey\Sikapay\Models\LeaveApplicationModel;
use Jeffrey\Sikapay\Models\LeaveBalanceModel;
use \Throwable;

class LeaveController extends Controller
{
    private LeaveTypeModel $leaveTypeModel;
    private LeaveApplicationModel $leaveApplicationModel;
    private LeaveBalanceModel $leaveBalanceModel;

    public function __construct()
    {
        parent::__construct();
        $this->leaveTypeModel = new LeaveTypeModel();
        $this->leaveApplicationModel = new LeaveApplicationModel();
        $this->leaveBalanceModel = new LeaveBalanceModel();
    }

    public function index(): void
    {
        try {
            $isApprover = $this->auth->hasPermission('leave:approve');
            $viewData = [
                'title' => 'Leave Management',
                'isApprover' => $isApprover,
            ];

            if ($isApprover) {
                // Fetch all pending applications for tenant admins/managers
                $viewData['pendingApplications'] = $this->leaveApplicationModel->getAllByTenant($this->tenantId, 'pending');
            }

            // Fetch data for the current user (both employees and approvers can see their own leave)
            $viewData['myApplications'] = $this->leaveApplicationModel->getAllByUser($this->userId);
            $viewData['myBalances'] = $this->leaveBalanceModel->getAllBalancesByUser($this->userId, $this->tenantId);
            $viewData['leaveTypes'] = $this->leaveTypeModel->getAllByTenant($this->tenantId);

            $this->view('leave/index', $viewData);

        } catch (Throwable $e) {
            Log::error("Failed to load leave management index page. Error: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load the leave management page due to a system error.");
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

    public function applyForLeave(): void
    {
        $this->checkPermission('leave:apply');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/leave');
        }

        $validator = new \Jeffrey\Sikapay\Core\Validator($_POST);
        $validator->validate([
            'leave_type_id' => 'required|int',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'reason' => 'optional|max:500',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = "Error submitting application: " . implode('<br>', $validator->errors());
            $this->redirect('/leave');
            return;
        }

        $startDate = new \DateTime($validator->get('start_date'));
        $endDate = new \DateTime($validator->get('end_date'));
        $today = new \DateTime('today');

        if ($startDate < $today) {
            $_SESSION['flash_error'] = "Start date cannot be in the past.";
            $this->redirect('/leave');
            return;
        }

        if ($endDate < $startDate) {
            $_SESSION['flash_error'] = "End date must be after the start date.";
            $this->redirect('/leave');
            return;
        }

        // Calculate total days excluding weekends
        $totalDays = 0;
        $current = clone $startDate;
        while ($current <= $endDate) {
            $dayOfWeek = (int)$current->format('N');
            if ($dayOfWeek < 6) { // Monday to Friday
                $totalDays++;
            }
            $current->add(new \DateInterval('P1D'));
        }

        $leaveTypeId = $validator->get('leave_type_id', 'int');
        $balance = $this->leaveBalanceModel->getBalance($this->userId, $leaveTypeId);
        $currentBalance = $balance ? (float)$balance['balance'] : 0.0;

        if ($currentBalance < $totalDays) {
            $_SESSION['flash_error'] = "Insufficient leave balance for this request. You have {$currentBalance} days remaining.";
            $this->redirect('/leave');
            return;
        }

        $data = [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'leave_type_id' => $leaveTypeId,
            'start_date' => $validator->get('start_date'),
            'end_date' => $validator->get('end_date'),
            'total_days' => $totalDays,
            'reason' => $validator->get('reason'),
        ];

        try {
            $appId = $this->leaveApplicationModel->create($data);
            if ($appId) {
                $_SESSION['flash_success'] = "Leave application submitted successfully for {$totalDays} days.";
            } else {
                $_SESSION['flash_error'] = "Failed to submit leave application.";
            }
        } catch (Throwable $e) {
            Log::error("Failed to apply for leave for User {$this->userId}: " . $e->getMessage());
            $_SESSION['flash_error'] = "A system error occurred while submitting your application.";
        }

        $this->redirect('/leave');
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

            $this->db->beginTransaction();
            
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
            } else {
                throw new \Exception("Failed to update application status.");
            }

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
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
            } else {
                $_SESSION['flash_error'] = "Failed to reject leave application.";
            }
        } catch (Throwable $e) {
            Log::error("Failed to reject leave application {$id}: " . $e->getMessage());
            $_SESSION['flash_error'] = "A system error occurred while rejecting the application.";
        }

        $this->redirect('/leave');
    }
}
