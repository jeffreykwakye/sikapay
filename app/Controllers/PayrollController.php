<?php

declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use Jeffrey\Sikapay\Services\PayrollService;
use Jeffrey\Sikapay\Services\NotificationService;
use Jeffrey\Sikapay\Services\EmailService; // ADDED
use Jeffrey\Sikapay\Models\PayrollPeriodModel;
use Jeffrey\Sikapay\Models\PayslipModel;
use Jeffrey\Sikapay\Models\DepartmentModel; // NEW IMPORT
use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Config\AppConfig;
use \Throwable;

class PayrollController extends Controller
{
    private PayrollService $payrollService;
    private PayrollPeriodModel $payrollPeriodModel;
    protected NotificationService $notificationService;
    private EmailService $emailService;
    private PayslipModel $payslipModel;
    private DepartmentModel $departmentModel; // NEW PROPERTY

    public function __construct()
    {
        parent::__construct();
        if ($this->auth->isSuperAdmin()) {
            ErrorResponder::respond(403, "Super Admins do not manage tenant payrolls directly.");
            return;
        }
        $this->checkPermission('payroll:manage_rules');

        try {
            $this->payrollService = new PayrollService();
            $this->payrollPeriodModel = new PayrollPeriodModel();
            $this->notificationService = new NotificationService();
            $this->emailService = new EmailService();
            $this->payslipModel = new PayslipModel();
            $this->departmentModel = new DepartmentModel(); // NEW INSTANTIATION
        } catch (Throwable $e) {
            Log::critical("PayrollController failed to initialize services/models: " . $e->getMessage());
            ErrorResponder::respond(500, "A critical system error occurred during payroll initialization.");
        }
    }

    public function index(): void
    {
        try {
            $currentPeriod = $this->payrollPeriodModel->getCurrentPeriod($this->tenantId);

            $this->view('payroll/index', [
                'title' => 'Payroll Management',
                'currentPeriod' => $currentPeriod,
                'Auth' => $this->auth // Pass the Auth instance to the view
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to load payroll index for Tenant {$this->tenantId}: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load payroll management page due to a system error.");
        }
    }

    public function createPeriod(): void
    {
        $this->checkActionIsAllowed();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/payroll');
        }

        // CSRF check
        if (!\Jeffrey\Sikapay\Security\CsrfToken::validate($_POST['csrf_token'] ?? '')) {
            \Jeffrey\Sikapay\Security\CsrfToken::destroyToken(); // Invalidate token on failure
            $_SESSION['flash_error'] = "Security error: Invalid CSRF token. Please try again.";
            $this->redirect('/payroll');
        }

        $validator = new \Jeffrey\Sikapay\Core\Validator($_POST);
        $validator->validate([
            'period_name' => 'required|min:3|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'payment_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = "Error creating payroll period: " . implode('<br>', $validator->errors());
            $this->redirect('/payroll');
            return;
        }

        try {
            $periodName = $validator->get('period_name');
            $periodId = $this->payrollPeriodModel->createPeriod(
                $this->tenantId,
                $periodName,
                $validator->get('start_date'),
                $validator->get('end_date'),
                $validator->get('payment_date', 'string', null)
            );

            if ($periodId) {
                $_SESSION['flash_success'] = "Payroll period '{$periodName}' created successfully.";
                // Notify tenant admins about the new payroll period
                $this->notificationService->createNotificationForRole(
                    $this->tenantId,
                    'tenant_admin',
                    'info',
                    'New Payroll Period Created',
                    "A new payroll period '{$periodName}' has been created, running from {$validator->get('start_date')} to {$validator->get('end_date')}."
                );
            } else {
                $_SESSION['flash_error'] = "Failed to create payroll period.";
            }
        } catch (Throwable $e) {
            Log::error("Failed to create payroll period for Tenant {$this->tenantId}: " . $e->getMessage());
            $_SESSION['flash_error'] = "A system error occurred while creating the payroll period.";
        }

        $this->redirect('/payroll');
    }

    public function runPayroll(): void
    {
        $this->checkActionIsAllowed();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/payroll');
        }

        // CSRF check
        if (!\Jeffrey\Sikapay\Security\CsrfToken::validate($_POST['csrf_token'] ?? '')) {
            \Jeffrey\Sikapay\Security\CsrfToken::destroyToken();
            $_SESSION['flash_error'] = "Security error: Invalid CSRF token. Please try again.";
            $this->redirect('/payroll');
        }

        $validator = new \Jeffrey\Sikapay\Core\Validator($_POST);
        $validator->validate([
            'payroll_period_id' => 'required|int',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = "Error running payroll: Invalid payroll period ID.";
            $this->redirect('/payroll');
            return;
        }

        $payrollPeriodId = $validator->get('payroll_period_id', 'int');

        try {
            $payrollPeriod = $this->payrollPeriodModel->find($payrollPeriodId);

            if (!$payrollPeriod || (int)$payrollPeriod['tenant_id'] !== $this->tenantId || (bool)$payrollPeriod['is_closed']) {
                $_SESSION['flash_error'] = "Invalid or closed payroll period selected.";
                $this->redirect('/payroll');
                return;
            }

            // Fetch all active employees for the current tenant
            $employees = $this->payrollService->getEmployeesForPayroll($this->tenantId);

            if (empty($employees)) {
                $_SESSION['flash_warning'] = "No active employees found for this payroll period.";
                $this->redirect('/payroll');
                return;
            }

            $this->payrollService->processPayrollRun($this->tenantId, $payrollPeriod, $employees);

            $_SESSION['flash_success'] = "Payroll for '{$payrollPeriod['period_name']}' successfully processed.";
            // Notify tenant admins about the payroll run completion
            $this->notificationService->createNotificationForRole(
                $this->tenantId,
                'tenant_admin',
                'success',
                'Payroll Run Completed',
                "Payroll for period '{$payrollPeriod['period_name']}' has been successfully processed."
            );

            // Send email to the user who initiated the payroll run
            $currentUserId = Auth::userId();
            $currentUser = $this->userModel->find($currentUserId); // Retrieve full user object

            if ($currentUser && !empty($currentUser['email'])) {
                $subject = "SikaPay: Payroll Run Completed for {$payrollPeriod['period_name']}";
                $appUrl = AppConfig::get('app.url');
                $body = "Dear {$currentUser['first_name']},<br><br>" // Use retrieved user data
                      . "The payroll for the period '{$payrollPeriod['period_name']}' has been successfully processed.<br>"
                      . "You can view the payslip history and reports here: <a href=\"{$appUrl}/payroll/payslips\">Payslip History</a><br><br>"
                      . "Thank you,<br>SikaPay Team";
                $this->emailService->send($currentUser['email'], $subject, $body); // Use retrieved user email
            }

        } catch (Throwable $e) {
            Log::critical("Payroll run failed for Tenant {$this->tenantId}, Period {$payrollPeriodId}: " . $e->getMessage());
            $_SESSION['flash_error'] = "A critical error occurred during payroll processing: " . $e->getMessage();
        }

        $this->redirect('/payroll');
    }



    public function downloadPayslipForAdmin(int $payslipId): void
    {
        $this->checkPermission('payroll:view_all');

        try {
            // Use the service to get payslip data, which should be tenant-scoped by the service/model
            $payslip = $this->payrollService->getPayslipById($payslipId, $this->tenantId);

            if (!$payslip) {
                ErrorResponder::respond(404, "Payslip not found or you do not have permission to view it.");
                return;
            }

            $payslipRelativeFilePath = $payslip['payslip_path'];
            $basePublicPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
            $fullPayslipFilePath = $basePublicPath . DIRECTORY_SEPARATOR . $payslipRelativeFilePath;

            if (file_exists($fullPayslipFilePath)) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . basename($fullPayslipFilePath) . '"');
                readfile($fullPayslipFilePath);
                exit;
            } else {
                Log::warning("Admin trying to download payslip not found on disk.", ['path' => $fullPayslipFilePath]);
                ErrorResponder::respond(404, "Payslip file not found on server.");
            }
        } catch (Throwable $e) {
            Log::error("Failed to download payslip {$payslipId} for Tenant {$this->tenantId} by admin. Error: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not download payslip due to a system error.");
        }
    }

    public function downloadMyPayslip(int $payslipId): void
    {
        try {
            $currentUserId = Auth::userId();
            $payslip = $this->payrollService->getPayslipById($payslipId, $this->tenantId);

            // Security Check: Ensure the payslip exists and belongs to the logged-in user.
            if (!$payslip || (int)$payslip['user_id'] !== $currentUserId) {
                ErrorResponder::respond(403, "You do not have permission to access this payslip.");
                return;
            }

            $payslipRelativeFilePath = $payslip['payslip_path'];
            $basePublicPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
            $fullPayslipFilePath = $basePublicPath . DIRECTORY_SEPARATOR . $payslipRelativeFilePath;

            if (file_exists($fullPayslipFilePath)) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . basename($fullPayslipFilePath) . '"');
                readfile($fullPayslipFilePath);
                exit;
            } else {
                Log::error("User's own payslip file not found on disk.", ['path' => $fullPayslipFilePath, 'user_id' => $currentUserId]);
                ErrorResponder::respond(404, "Your payslip file could not be found. Please contact support.");
            }
        } catch (Throwable $e) {
            Log::critical("Self-service payslip download failed for Payslip {$payslipId}. Error: " . $e->getMessage());
            ErrorResponder::respond(500, "A critical error occurred while trying to download your payslip.");
        }
    }

    public function getPayslipsByPeriod(int $periodId): void
    {
        $this->checkPermission('payroll:view_all');

        try {
            $payslips = $this->payrollService->getPayslipsByPeriod($periodId, $this->tenantId);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'payslips' => $payslips]);
        } catch (Throwable $e) {
            Log::error("Failed to fetch payslips for period {$periodId} (Tenant {$this->tenantId}): " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Could not retrieve payslips.']);
        }
    }

    public function viewPayslips(): void
    {
        $this->checkPermission('payroll:view_all');

        try {
            $payrollPeriods = $this->payrollPeriodModel->getAllPeriods($this->tenantId);

            $this->view('payroll/payslip-history', [
                'title' => 'Payslip History',
                'payrollPeriods' => $payrollPeriods,
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to load payslip list for Tenant {$this->tenantId}: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load payslip history due to a system error.");
        }
    }

    /**
     * Displays payslips for a specific department and payroll period.
     * Accessible via /payroll/payslips/department/{departmentId}/period/{periodId}
     *
     * @param string $departmentId
     * @param string $periodId
     */
    public function viewDepartmentPayslips(string $departmentId, string $periodId): void
    {
        $this->checkPermission('payroll:view_all');

        try {
            $deptId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($departmentId);
            $pId = (int)\Jeffrey\Sikapay\Helpers\Sanitizer::text($periodId);

            $department = $this->departmentModel->find($deptId);
            if (!$department || (int)$department['tenant_id'] !== $this->tenantId) {
                ErrorResponder::respond(404, "Department not found or does not belong to your tenant.");
                return;
            }

            $payrollPeriod = $this->payrollPeriodModel->getPeriodById($pId, Auth::tenantId());
            if (!$payrollPeriod || (int)$payrollPeriod['tenant_id'] !== $this->tenantId) {
                ErrorResponder::respond(404, "Payroll Period not found or does not belong to your tenant.");
                return;
            }

            // Fetch payslips for this department and period
            $payslips = $this->payslipModel->getPayslipsByDepartmentAndPeriod($this->tenantId, $deptId, $pId);

            $this->view('payroll/payslip-history', [
                'title' => 'Payslips for ' . $department['name'] . ' - ' . $payrollPeriod['period_name'],
                'payrollPeriods' => [$payrollPeriod], // Only this period for the dropdown if needed
                'payslips' => $payslips, // Pass filtered payslips
                'selectedDepartmentId' => $deptId,
                'selectedPeriodId' => $pId,
            ]);

        } catch (Throwable $e) {
            Log::error("Failed to load payslips for Department ID {$departmentId}, Period ID {$periodId}: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load departmental payslips due to a system error.");
        }
    }
}


