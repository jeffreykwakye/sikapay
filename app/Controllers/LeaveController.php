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
