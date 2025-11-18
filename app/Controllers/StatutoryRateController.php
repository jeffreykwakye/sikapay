<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use Jeffrey\Sikapay\Core\Validator;
use Jeffrey\Sikapay\Models\SsnitRateModel;
use Jeffrey\Sikapay\Models\WithholdingTaxRateModel;

class StatutoryRateController extends Controller
{
    private SsnitRateModel $ssnitRateModel;
    private WithholdingTaxRateModel $withholdingTaxRateModel;

    public function __construct()
    {
        parent::__construct();
        // Ensure only Super Admins can access this controller
        $this->checkPermission('super:manage_statutory_rates');

        $this->ssnitRateModel = new SsnitRateModel();
        $this->withholdingTaxRateModel = new WithholdingTaxRateModel();
    }

    /**
     * Display a list of all statutory rates.
     */
    public function index(): void
    {
        try {
            $ssnitRates = $this->ssnitRateModel->all();
            $withholdingTaxRates = $this->withholdingTaxRateModel->all();

            $this->view('superadmin/statutory-rates/index', [
                'title' => 'Manage Statutory Rates',
                'ssnitRates' => $ssnitRates,
                'withholdingTaxRates' => $withholdingTaxRates,
                'success' => $_SESSION['flash_success'] ?? null,
                'error' => $_SESSION['flash_error'] ?? null,
            ]);
            unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        } catch (\Throwable $e) {
            Log::error('Failed to load statutory rates page: ' . $e->getMessage());
            ErrorResponder::respond(500, 'Could not load statutory rates.');
        }
    }

    // --- SSNIT Rate Management ---

    /**
     * Show the form for creating a new SSNIT rate.
     */
    public function createSsnitRate(): void
    {
        try {
            $this->view('superadmin/statutory-rates/create_ssnit', [
                'title' => 'Create New SSNIT Rate',
                'error' => $_SESSION['flash_error'] ?? null,
                'input' => $_SESSION['flash_input'] ?? [],
            ]);
            unset($_SESSION['flash_error'], $_SESSION['flash_input']);
        } catch (\Throwable $e) {
            Log::error('Failed to load create SSNIT rate form: ' . $e->getMessage());
            ErrorResponder::respond(500, 'Could not load the SSNIT rate creation form.');
        }
    }

    /**
     * Store a newly created SSNIT rate.
     */
    public function storeSsnitRate(): void
    {
        $validator = new Validator($_POST);
        $validator->validate([
            'employee_rate' => 'required|numeric|min:0',
            'employer_rate' => 'required|numeric|min:0',
            'max_contribution_limit' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = 'SSNIT rate creation failed: ' . implode('<br>', $validator->errors());
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/statutory-rates/ssnit/create');
            return;
        }

        try {
            $data = [
                'employee_rate' => $validator->get('employee_rate', 'float'),
                'employer_rate' => $validator->get('employer_rate', 'float'),
                'max_contribution_limit' => $validator->get('max_contribution_limit', 'float'),
                'effective_date' => $validator->get('effective_date'),
            ];
            $this->ssnitRateModel->create($data);
            $_SESSION['flash_success'] = 'SSNIT Rate created successfully.';
            $this->redirect('/super/statutory-rates');
        } catch (\Throwable $e) {
            Log::error('Failed to store new SSNIT rate: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error creating SSNIT rate: ' . $e->getMessage();
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/statutory-rates/ssnit/create');
        }
    }

    /**
     * Show the form for editing an SSNIT rate.
     */
    public function editSsnitRate(int $id): void
    {
        try {
            $rate = $this->ssnitRateModel->find($id);
            if (!$rate) {
                ErrorResponder::respond(404, 'SSNIT Rate not found.');
                return;
            }

            $this->view('superadmin/statutory-rates/edit_ssnit', [
                'title' => 'Edit SSNIT Rate',
                'rate' => $rate,
                'error' => $_SESSION['flash_error'] ?? null,
                'input' => $_SESSION['flash_input'] ?? [],
            ]);
            unset($_SESSION['flash_error'], $_SESSION['flash_input']);
        } catch (\Throwable $e) {
            Log::error('Failed to load edit SSNIT rate form for ID ' . $id . ': ' . $e->getMessage());
            ErrorResponder::respond(500, 'Could not load the SSNIT rate edit form.');
        }
    }

    /**
     * Update the specified SSNIT rate.
     */
    public function updateSsnitRate(int $id): void
    {
        $validator = new Validator($_POST);
        $validator->validate([
            'employee_rate' => 'required|numeric|min:0',
            'employer_rate' => 'required|numeric|min:0',
            'max_contribution_limit' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = 'SSNIT rate update failed: ' . implode('<br>', $validator->errors());
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/statutory-rates/ssnit/' . $id . '/edit');
            return;
        }

        try {
            $data = [
                'employee_rate' => $validator->get('employee_rate', 'float'),
                'employer_rate' => $validator->get('employer_rate', 'float'),
                'max_contribution_limit' => $validator->get('max_contribution_limit', 'float'),
                'effective_date' => $validator->get('effective_date'),
            ];
            $this->ssnitRateModel->update($id, $data);
            $_SESSION['flash_success'] = 'SSNIT Rate updated successfully.';
            $this->redirect('/super/statutory-rates');
        } catch (\Throwable $e) {
            Log::error('Failed to update SSNIT rate ID ' . $id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error updating SSNIT rate: ' . $e->getMessage();
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/statutory-rates/ssnit/' . $id . '/edit');
        }
    }

    /**
     * Delete an SSNIT rate.
     */
    public function deleteSsnitRate(int $id): void
    {
        try {
            $this->ssnitRateModel->delete($id);
            $_SESSION['flash_success'] = 'SSNIT Rate deleted successfully.';
            $this->redirect('/super/statutory-rates');
        } catch (\Throwable $e) {
            Log::error('Failed to delete SSNIT rate ID ' . $id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error deleting SSNIT rate: ' . $e->getMessage();
            $this->redirect('/super/statutory-rates');
        }
    }

    // --- Withholding Tax Rate Management ---

    /**
     * Show the form for creating a new Withholding Tax rate.
     */
    public function createWithholdingTaxRate(): void
    {
        try {
            $this->view('superadmin/statutory-rates/create_wht', [
                'title' => 'Create New Withholding Tax Rate',
                'error' => $_SESSION['flash_error'] ?? null,
                'input' => $_SESSION['flash_input'] ?? [],
            ]);
            unset($_SESSION['flash_error'], $_SESSION['flash_input']);
        } catch (\Throwable $e) {
            Log::error('Failed to load create Withholding Tax rate form: ' . $e->getMessage());
            ErrorResponder::respond(500, 'Could not load the Withholding Tax rate creation form.');
        }
    }

    /**
     * Store a newly created Withholding Tax rate.
     */
    public function storeWithholdingTaxRate(): void
    {
        $validator = new Validator($_POST);
        $validator->validate([
            'rate' => 'required|numeric|min:0',
            'employment_type' => 'required|string|max:50',
            'description' => 'optional|string|max:255',
            'effective_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = 'Withholding Tax rate creation failed: ' . implode('<br>', $validator->errors());
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/statutory-rates/wht/create');
            return;
        }

        try {
            $data = [
                'rate' => $validator->get('rate', 'float'),
                'employment_type' => $validator->get('employment_type'),
                'description' => $validator->get('description'),
                'effective_date' => $validator->get('effective_date'),
            ];
            $this->withholdingTaxRateModel->create($data);
            $_SESSION['flash_success'] = 'Withholding Tax Rate created successfully.';
            $this->redirect('/super/statutory-rates');
        } catch (\Throwable $e) {
            Log::error('Failed to store new Withholding Tax rate: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error creating Withholding Tax rate: ' . $e->getMessage();
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/statutory-rates/wht/create');
        }
    }

    /**
     * Show the form for editing a Withholding Tax rate.
     */
    public function editWithholdingTaxRate(int $id): void
    {
        try {
            $rate = $this->withholdingTaxRateModel->find($id);
            if (!$rate) {
                ErrorResponder::respond(404, 'Withholding Tax Rate not found.');
                return;
            }

            $this->view('superadmin/statutory-rates/edit_wht', [
                'title' => 'Edit Withholding Tax Rate',
                'rate' => $rate,
                'error' => $_SESSION['flash_error'] ?? null,
                'input' => $_SESSION['flash_input'] ?? [],
            ]);
            unset($_SESSION['flash_error'], $_SESSION['flash_input']);
        } catch (\Throwable $e) {
            Log::error('Failed to load edit Withholding Tax rate form for ID ' . $id . ': ' . $e->getMessage());
            ErrorResponder::respond(500, 'Could not load the Withholding Tax rate edit form.');
        }
    }

    /**
     * Update the specified Withholding Tax rate.
     */
    public function updateWithholdingTaxRate(int $id): void
    {
        $validator = new Validator($_POST);
        $validator->validate([
            'rate' => 'required|numeric|min:0',
            'employment_type' => 'required|string|max:50',
            'description' => 'optional|string|max:255',
            'effective_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = 'Withholding Tax rate update failed: ' . implode('<br>', $validator->errors());
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/statutory-rates/wht/' . $id . '/edit');
            return;
        }

        try {
            $data = [
                'rate' => $validator->get('rate', 'float'),
                'employment_type' => $validator->get('employment_type'),
                'description' => $validator->get('description'),
                'effective_date' => $validator->get('effective_date'),
            ];
            $this->withholdingTaxRateModel->update($id, $data);
            $_SESSION['flash_success'] = 'Withholding Tax Rate updated successfully.';
            $this->redirect('/super/statutory-rates');
        } catch (\Throwable $e) {
            Log::error('Failed to update Withholding Tax rate ID ' . $id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error updating Withholding Tax rate: ' . $e->getMessage();
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/statutory-rates/wht/' . $id . '/edit');
        }
    }

    /**
     * Delete a Withholding Tax rate.
     */
    public function deleteWithholdingTaxRate(int $id): void
    {
        try {
            $this->withholdingTaxRateModel->delete($id);
            $_SESSION['flash_success'] = 'Withholding Tax Rate deleted successfully.';
            $this->redirect('/super/statutory-rates');
        } catch (\Throwable $e) {
            Log::error('Failed to delete Withholding Tax rate ID ' . $id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error deleting Withholding Tax rate: ' . $e->getMessage();
            $this->redirect('/super/statutory-rates');
        }
    }
}
