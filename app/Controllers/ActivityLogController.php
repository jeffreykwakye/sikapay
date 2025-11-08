<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Models\AuditModel;
use Jeffrey\Sikapay\Helpers\ActivityLogCsvGenerator;
use Jeffrey\Sikapay\Helpers\ActivityLogPdfGenerator;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use \Throwable;

class ActivityLogController extends Controller
{
    private AuditModel $auditModel;

    public function __construct()
    {
        parent::__construct();
        $this->auditModel = new AuditModel();
    }

    /**
     * Displays the tenant's activity log page.
     */
    public function index(): void
    {
        // 1. Check for permission
        $this->checkPermission('tenant:view_audit_logs');

        try {
            $isSuperAdminView = $this->auth->isSuperAdmin();
            $activities = [];

            if ($isSuperAdminView) {
                // For Super Admins, get all logs from all tenants
                $activities = $this->auditModel->getAllLogs(200);
            } else {
                // For Tenant Admins, get logs only for their tenant
                $activities = $this->auditModel->getLogsByTenantId($this->tenantId, 200);
            }

            // 3. Prepare data for the view
            $data = [
                'title' => 'Activity Log',
                'activities' => $activities,
                'isSuperAdminView' => $isSuperAdminView,
            ];

            // 4. Render the view
            $this->view('activity/index', $data);

        } catch (Throwable $e) {
            $this->handleError("Activity Log Load Failed", $e);
        }
    }

    /**
     * Handles exporting the activity log as a CSV file.
     */
    public function exportCsv(): void
    {
        $this->checkPermission('tenant:view_audit_logs');
        try {
            $data = $this->fetchDataForExport();
            $generator = new ActivityLogCsvGenerator($data['activities'], $data['isSuperAdminView']);
            $generator->generate(); // Generate method no longer needs arguments
        } catch (Throwable $e) {
            $this->handleError("Activity Log CSV Export Failed", $e);
        }
    }

    /**
     * Handles exporting the activity log as a PDF file.
     */
    public function exportPdf(): void
    {
        $this->checkPermission('tenant:view_audit_logs');
        try {
            $data = $this->fetchDataForExport();
            $generator = new ActivityLogPdfGenerator($data['activities'], $data['isSuperAdminView']);
            $generator->generate(); // Generate method no longer needs arguments
        } catch (Throwable $e) {
            $this->handleError("Activity Log PDF Export Failed", $e);
        }
    }

    /**
     * Fetches the appropriate activity log data for the current user.
     *
     * @return array An array containing the activities and a flag indicating if it's a super admin view.
     */
    private function fetchDataForExport(): array
    {
        $isSuperAdminView = $this->auth->isSuperAdmin();
        $activities = [];

        if ($isSuperAdminView) {
            // For Super Admins, get all logs from all tenants (no limit)
            $activities = $this->auditModel->getAllLogs(null);
        } else {
            // For Tenant Admins, get logs only for their tenant (no limit)
            $activities = $this->auditModel->getLogsByTenantId($this->tenantId, null);
        }

        return [
            'activities' => $activities,
            'isSuperAdminView' => $isSuperAdminView,
        ];
    }

    /**
     * Handles logging and displays a generic error page.
     * @param string $message The high-level error message.
     * @param Throwable $e The caught exception.
     */
    private function handleError(string $message, Throwable $e): void
    {
        $userId = $this->userId > 0 ? (string)$this->userId : 'N/A';
        
        Log::critical("{$message} for User {$userId}.", [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
        ]);

        ErrorResponder::respond(500, "We could not load the activity log due to a system error.");
    }
}
