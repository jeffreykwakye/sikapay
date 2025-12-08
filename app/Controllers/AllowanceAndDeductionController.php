<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Models\CustomPayrollElementModel;
use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;

class AllowanceAndDeductionController extends Controller
{
    private CustomPayrollElementModel $customPayrollElementModel;
    private const PERMISSION_MANAGE = 'config:manage_payroll_elements';

    public function __construct()
    {
        parent::__construct();

        if (!$this->auth->check() || $this->auth->isSuperAdmin()) {
            $this->redirect('/login');
        }

        $this->checkPermission(self::PERMISSION_MANAGE);

        $this->customPayrollElementModel = new CustomPayrollElementModel();
    }

    public function index(): void
    {
        try {
            $elements = $this->customPayrollElementModel->getAllByTenant($this->tenantId);

            $this->view('allowances_and_deductions/index', [
                'elements' => $elements,
                'successMessage' => $_SESSION['flash_success'] ?? null,
                'errorMessage' => $_SESSION['flash_error'] ?? null,
            ]);

            unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        } catch (\Throwable $e) {
            Log::critical("AllowanceAndDeductionController index failed for Tenant {$this->tenantId}. Error: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load payroll elements due to a system error.");
        }
    }

    public function store(): void
    {
        $this->checkActionIsAllowed();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/payroll-elements');
        }

        // CSRF check
        if (!\Jeffrey\Sikapay\Security\CsrfToken::validate($_POST['csrf_token'] ?? '')) {
            \Jeffrey\Sikapay\Security\CsrfToken::destroyToken();
            $_SESSION['flash_error'] = "Security error: Invalid CSRF token. Please try again.";
            $this->redirect('/payroll-elements');
        }

        $validator = new \Jeffrey\Sikapay\Core\Validator($_POST);
        $validator->validate([
            'name' => 'required|min:3|max:100',
            'category' => 'required|in:allowance,deduction',
            'amount_type' => 'required|in:fixed,percentage',
            'default_amount' => 'required|numeric|min:0',
            'calculation_base' => 'optional',
            'is_taxable' => 'optional|bool',
            'is_ssnit_chargeable' => 'optional|bool',
            'is_recurring' => 'optional|bool',
            'description' => 'optional|max:500',
        ]);

        $calculationBase = $validator->get('calculation_base', 'string', null);
        
        $data = [
            'name' => $validator->get('name'),
            'category' => $validator->get('category'),
            'amount_type' => $validator->get('amount_type'),
            'default_amount' => $validator->get('default_amount', 'float'),
            'calculation_base' => empty($calculationBase) ? null : $calculationBase,
            'is_taxable' => (int)$validator->get('is_taxable', 'bool', false),
            'is_ssnit_chargeable' => (int)$validator->get('is_ssnit_chargeable', 'bool', false),
            'is_recurring' => (int)$validator->get('is_recurring', 'bool', false),
            'description' => $validator->get('description', 'string', null),
        ];

        try {
            $newId = $this->customPayrollElementModel->create($this->tenantId, $data);

            if ($newId) {
                $_SESSION['flash_success'] = "Payroll element '{$data['name']}' created successfully.";
            } else {
                $_SESSION['flash_error'] = "Failed to create payroll element.";
            }
        } catch (\Throwable $e) {
            Log::error("Failed to create payroll element for Tenant {$this->tenantId}: " . $e->getMessage());
            $_SESSION['flash_error'] = "A system error occurred while creating the payroll element.";
        }

        $this->redirect('/payroll-elements');
    }

    public function update(string $id): void
    {
        $id = (int)$id;
        $this->checkActionIsAllowed();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/payroll-elements');
        }

        // CSRF check
        if (!\Jeffrey\Sikapay\Security\CsrfToken::validate($_POST['csrf_token'] ?? '')) {
            \Jeffrey\Sikapay\Security\CsrfToken::destroyToken();
            $_SESSION['flash_error'] = "Security error: Invalid CSRF token. Please try again.";
            $this->redirect('/payroll-elements');
        }

        $validator = new \Jeffrey\Sikapay\Core\Validator($_POST);
        $validator->validate([
            'name' => 'required|min:3|max:100',
            'category' => 'required|in:allowance,deduction',
            'amount_type' => 'required|in:fixed,percentage',
            'default_amount' => 'required|numeric|min:0',
            'calculation_base' => 'optional',
            'is_taxable' => 'optional|bool',
            'is_ssnit_chargeable' => 'optional|bool',
            'is_recurring' => 'optional|bool',
            'description' => 'optional|max:500',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = "Error updating payroll element: " . implode('<br>', $validator->errors());
            $this->redirect('/payroll-elements');
            return;
        }

                $calculationBase = $validator->get('calculation_base', 'string', null);

        $data = [
            'name' => $validator->get('name'),
            'category' => $validator->get('category'),
            'amount_type' => $validator->get('amount_type'),
            'default_amount' => $validator->get('default_amount', 'float'),
            'calculation_base' => empty($calculationBase) ? null : $calculationBase,
            'is_taxable' => (int)$validator->get('is_taxable', 'bool', false),
            'is_ssnit_chargeable' => (int)$validator->get('is_ssnit_chargeable', 'bool', false),
            'is_recurring' => (int)$validator->get('is_recurring', 'bool', false),
            'description' => $validator->get('description', 'string', null),
        ];

        try {
            $success = $this->customPayrollElementModel->update($id, $this->tenantId, $data);

            if ($success) {
                $_SESSION['flash_success'] = "Payroll element '{$data['name']}' updated successfully.";
            } else {
                $_SESSION['flash_error'] = "Failed to update payroll element or no changes were made.";
            }
        } catch (\Throwable $e) {
            Log::error("Failed to update payroll element {$id} for Tenant {$this->tenantId}: " . $e->getMessage());
            $_SESSION['flash_error'] = "A system error occurred while updating the payroll element.";
        }

        $this->redirect('/payroll-elements');
    }

    public function delete(string $id): void
    {
        $id = (int)$id;
        $this->checkActionIsAllowed();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/payroll-elements');
        }

        // CSRF check
        if (!\Jeffrey\Sikapay\Security\CsrfToken::validate($_POST['csrf_token'] ?? '')) {
            \Jeffrey\Sikapay\Security\CsrfToken::destroyToken();
            $_SESSION['flash_error'] = "Security error: Invalid CSRF token. Please try again.";
            $this->redirect('/payroll-elements');
        }

        try {
            $success = $this->customPayrollElementModel->delete($id, $this->tenantId);

            if ($success) {
                $_SESSION['flash_success'] = "Payroll element deleted successfully.";
            } else {
                $_SESSION['flash_error'] = "Failed to delete payroll element or element not found.";
            }
        } catch (\Throwable $e) {
            Log::error("Failed to delete payroll element {$id} for Tenant {$this->tenantId}: " . $e->getMessage());
            $_SESSION['flash_error'] = "A system error occurred while deleting the payroll element.";
        }

        $this->redirect('/payroll-elements');
    }
}
