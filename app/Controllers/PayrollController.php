<?php

declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use Jeffrey\Sikapay\Services\PayrollService;
use Jeffrey\Sikapay\Models\PayrollPeriodModel;
use \Throwable;

class PayrollController extends Controller
{
    private PayrollService $payrollService;
    private PayrollPeriodModel $payrollPeriodModel;

    public function __construct()
    {
        parent::__construct();
        // Ensure only tenant admins can access payroll functions
        if ($this->auth->isSuperAdmin()) {
            ErrorResponder::respond(403, "Super Admins do not manage tenant payrolls directly.");
            return;
        }
        $this->checkPermission('payroll:manage_rules'); // Assuming a permission for payroll management

        try {
            $this->payrollService = new PayrollService();
            $this->payrollPeriodModel = new PayrollPeriodModel();
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
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to load payroll index for Tenant {$this->tenantId}: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load payroll management page due to a system error.");
        }
    }

    public function createPeriod(): void
    {
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
            'payment_date' => 'optional|date',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = "Error creating payroll period: " . implode('<br>', $validator->errors());
            $this->redirect('/payroll');
            return;
        }

        try {
            $periodId = $this->payrollPeriodModel->createPeriod(
                $this->tenantId,
                $validator->get('period_name'),
                $validator->get('start_date'),
                $validator->get('end_date'),
                $validator->get('payment_date', 'string', null)
            );

            if ($periodId) {
                $_SESSION['flash_success'] = "Payroll period '{$validator->get('period_name')}' created successfully.";
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
        } catch (Throwable $e) {
            Log::critical("Payroll run failed for Tenant {$this->tenantId}, Period {$payrollPeriodId}: " . $e->getMessage());
            $_SESSION['flash_error'] = "A critical error occurred during payroll processing: " . $e->getMessage();
        }

        $this->redirect('/payroll');
    }
}

