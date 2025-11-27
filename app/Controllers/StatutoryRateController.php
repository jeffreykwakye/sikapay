<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Core\Auth;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use Jeffrey\Sikapay\Core\Validator;
use Jeffrey\Sikapay\Models\SsnitRateModel;
use Jeffrey\Sikapay\Models\WithholdingTaxRateModel;
use Jeffrey\Sikapay\Models\TaxBandModel; // NEW

class StatutoryRateController extends Controller
{
    private SsnitRateModel $ssnitRateModel;
    private WithholdingTaxRateModel $withholdingTaxRateModel;
    private TaxBandModel $taxBandModel; // NEW

    public function __construct()
    {
        parent::__construct();
        // Ensure only Super Admins can access this controller
        $this->checkPermission('super:manage_statutory_rates');

        $this->ssnitRateModel = new SsnitRateModel();
        $this->withholdingTaxRateModel = new WithholdingTaxRateModel();
        $this->taxBandModel = new TaxBandModel(); // NEW
    }



    /**
     * Display a list of PAYE Tax Bands for Super Admin to manage.
     */
    public function payeTaxBandsIndex(): void
    {
        try {
            $currentYear = (int)date('Y');
            $selectedYear = (int)($_GET['year'] ?? $currentYear);

            $annualTaxBands = $this->taxBandModel->getTaxBandsForYear($selectedYear, true);
            $monthlyTaxBands = $this->taxBandModel->getTaxBandsForYear($selectedYear, false);
            $availableTaxYears = $this->taxBandModel->getAvailableTaxYears();

            $this->view('superadmin/statutory-rates/paye-tax-bands', [
                'title' => 'Manage PAYE Tax Bands',
                'annualTaxBands' => $annualTaxBands,
                'monthlyTaxBands' => $monthlyTaxBands,
                'selectedYear' => $selectedYear,
                'availableTaxYears' => $availableTaxYears,
                'success' => $_SESSION['flash_success'] ?? null,
                'error' => $_SESSION['flash_error'] ?? null,
            ]);
            unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        } catch (\Throwable $e) {
            Log::error('Failed to load PAYE Tax Bands page: ' . $e->getMessage());
            ErrorResponder::respond(500, 'Could not load PAYE Tax Bands.');
        }
    }

    /**
     * Display a list of SSNIT Rates for Super Admin to manage.
     */
    public function ssnitRatesIndex(): void
    {
        try {
            $ssnitRates = $this->ssnitRateModel->all();

            $this->view('superadmin/statutory-rates/ssnit-rates', [
                'title' => 'Manage SSNIT Rates',
                'ssnitRates' => $ssnitRates,
                'success' => $_SESSION['flash_success'] ?? null,
                'error' => $_SESSION['flash_error'] ?? null,
            ]);
            unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        } catch (\Throwable $e) {
            Log::error('Failed to load SSNIT Rates page: ' . $e->getMessage());
            ErrorResponder::respond(500, 'Could not load SSNIT Rates.');
        }
    }

    /**
     * Display a list of Withholding Tax Rates for Super Admin to manage.
     */
    public function withholdingTaxRatesIndex(): void
    {
        try {
            $withholdingTaxRates = $this->withholdingTaxRateModel->all();

            $this->view('superadmin/statutory-rates/withholding-tax-rates', [
                'title' => 'Manage Withholding Tax Rates',
                'withholdingTaxRates' => $withholdingTaxRates,
                'success' => $_SESSION['flash_success'] ?? null,
                'error' => $_SESSION['flash_error'] ?? null,
            ]);
            unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        } catch (\Throwable $e) {
            Log::error('Failed to load Withholding Tax Rates page: ' . $e->getMessage());
            ErrorResponder::respond(500, 'Could not load Withholding Tax Rates.');
        }
    }

    /**
     * Store a newly created Tax Band.
     */
    public function storeTaxBand(): void
    {
        $validator = new Validator($_POST);
        $validator->validate([
            'tax_year' => 'required|int|min:1900',
            'band_start' => 'required|numeric|min:0',
            'band_end' => 'optional|numeric|min:0',
            'rate' => 'required|numeric|min:0|max:1',
            'is_annual' => 'required|bool',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = 'Tax Band creation failed: ' . implode('<br>', $validator->errors());
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/statutory-rates/paye');
            return;
        }

        try {
            $data = [
                'tax_year' => $validator->get('tax_year', 'int'),
                'band_start' => $validator->get('band_start', 'float'),
                'band_end' => $validator->get('band_end', 'float', null),
                'rate' => $validator->get('rate', 'float'),
                'is_annual' => $validator->get('is_annual', 'bool'),
            ];
            $this->taxBandModel->create($data);
            $_SESSION['flash_success'] = 'Tax Band created successfully.';
            $this->redirect('/super/statutory-rates/paye');
        } catch (\Throwable $e) {
            Log::error('Failed to store new Tax Band: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error creating Tax Band: ' . $e->getMessage();
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/statutory-rates/paye');
        }
    }

    /**
     * Update the specified Tax Band.
     */
    public function updateTaxBand(string $id): void
    {
        $id = (int)$id;
        $validator = new Validator($_POST);
        $validator->validate([
            'tax_year' => 'required|int|min:1900',
            'band_start' => 'required|numeric|min:0',
            'band_end' => 'optional|numeric|min:0',
            'rate' => 'required|numeric|min:0|max:1',
            'is_annual' => 'required|bool',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = 'Tax Band update failed: ' . implode('<br>', $validator->errors());
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/statutory-rates/paye');
            return;
        }

        try {
            $data = [
                'tax_year' => $validator->get('tax_year', 'int'),
                'band_start' => $validator->get('band_start', 'float'),
                'band_end' => $validator->get('band_end', 'float', null),
                'rate' => $validator->get('rate', 'float'),
                'is_annual' => $validator->get('is_annual', 'bool'),
            ];
            $this->taxBandModel->update($id, $data);
            $_SESSION['flash_success'] = 'Tax Band updated successfully.';
            $this->redirect('/super/statutory-rates/paye');
        } catch (\Throwable $e) {
            Log::error('Failed to update Tax Band ID ' . $id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error updating Tax Band: ' . $e->getMessage();
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/statutory-rates/paye');
        }
    }

    // --- SSNIT Rate Management ---

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
            $this->redirect('/super/statutory-rates'); // Redirect back to index page
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
            $this->redirect('/super/statutory-rates/ssnit');
        } catch (\Throwable $e) {
            Log::error('Failed to store new SSNIT rate: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error creating SSNIT rate: ' . $e->getMessage();
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/statutory-rates'); // Redirect back to index page
        }
    }

    /**
     * Update the specified SSNIT rate.
     */
    public function updateSsnitRate(string $id): void
    {
        $id = (int)$id;
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
            $this->redirect('/super/statutory-rates'); // Redirect back to index page
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
            $this->redirect('/super/statutory-rates/ssnit');
        } catch (\Throwable $e) {
            Log::error('Failed to update SSNIT rate ID ' . $id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error updating SSNIT rate: ' . $e->getMessage();
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/statutory-rates'); // Redirect back to index page
        }
    }

    /**
     * Delete an SSNIT rate.
     */
    public function deleteSsnitRate(string $id): void
    {
        $id = (int)$id;
        try {
            $this->ssnitRateModel->delete($id);
            $_SESSION['flash_success'] = 'SSNIT Rate deleted successfully.';
            $this->redirect('/super/statutory-rates/ssnit');
        } catch (\Throwable $e) {
            Log::error('Failed to delete SSNIT rate ID ' . $id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error deleting SSNIT rate: ' . $e->getMessage();
            $this->redirect('/super/statutory-rates');
        }
    }

    // --- Withholding Tax Rate Management ---

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
            $this->redirect('/super/statutory-rates'); // Redirect back to index page
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
            $this->redirect('/super/statutory-rates/wht');
        } catch (\Throwable $e) {
            Log::error('Failed to store new Withholding Tax rate: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error creating Withholding Tax rate: ' . $e->getMessage();
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/statutory-rates'); // Redirect back to index page
        }
    }

    /**
     * Update the specified Withholding Tax rate.
     */
    public function updateWithholdingTaxRate(string $id): void
    {
        $id = (int)$id;
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
            $this->redirect('/super/statutory-rates'); // Redirect back to index page
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
            $this->redirect('/super/statutory-rates/wht');
        } catch (\Throwable $e) {
            Log::error('Failed to update Withholding Tax rate ID ' . $id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error updating Withholding Tax rate: ' . $e->getMessage();
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/super/statutory-rates'); // Redirect back to index page
        }
    }

    /**
     * Delete a Withholding Tax rate.
     */
    public function deleteWithholdingTaxRate(string $id): void
    {
        $id = (int)$id;
        try {
            $this->withholdingTaxRateModel->delete($id);
            $_SESSION['flash_success'] = 'Withholding Tax Rate deleted successfully.';
            $this->redirect('/super/statutory-rates/wht');
        } catch (\Throwable $e) {
            Log::error('Failed to delete Withholding Tax rate ID ' . $id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error deleting Withholding Tax rate: ' . $e->getMessage();
            $this->redirect('/super/statutory-rates');
        }
    }

    /**
     * API endpoint to get SSNIT rate details for editing.
     * @param int $id
     */
    public function getSsnitRateDetails(string $id): void
    {
        $id = (int)$id;
        try {
            $rate = $this->ssnitRateModel->find($id);
            if (!$rate) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'SSNIT Rate not found'], JSON_PRETTY_PRINT);
                return;
            }

            header('Content-Type: application/json');
            echo json_encode($rate, JSON_PRETTY_PRINT);
        } catch (\Throwable $e) {
            Log::error('Failed to get SSNIT rate details for API ID ' . $id . ': ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Could not retrieve SSNIT rate details.'], JSON_PRETTY_PRINT);
        }
    }

    /**
     * API endpoint to get Withholding Tax rate details for editing.
     * @param int $id
     */
    public function getWithholdingTaxRateDetails(string $id): void
    {
        $id = (int)$id;
        try {
            $rate = $this->withholdingTaxRateModel->find($id);
            if (!$rate) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Withholding Tax Rate not found'], JSON_PRETTY_PRINT);
                return;
            }

            header('Content-Type: application/json');
            echo json_encode($rate, JSON_PRETTY_PRINT);
        } catch (\Throwable $e) {
            Log::error('Failed to get Withholding Tax rate details for API ID ' . $id . ': ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Could not retrieve Withholding Tax rate details.'], JSON_PRETTY_PRINT);
        }
    }

    /**
     * Delete a Tax Band.
     */
    public function deleteTaxBand(string $id): void
    {
        $id = (int)$id;
        try {
            $this->taxBandModel->delete($id);
            $_SESSION['flash_success'] = 'Tax Band deleted successfully.';
            $this->redirect('/super/statutory-rates/paye');
        } catch (\Throwable $e) {
            Log::error('Failed to delete Tax Band ID ' . $id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error deleting Tax Band: ' . $e->getMessage();
            $this->redirect('/super/statutory-rates/paye');
        }
    }

    /**
     * API endpoint to get Tax Band details for editing.
     * @param int $id
     */
    public function getTaxBandDetails(string $id): void
    {
        $id = (int)$id;
        try {
            $rate = $this->taxBandModel->find($id);
            if (!$rate) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Tax Band not found'], JSON_PRETTY_PRINT);
                return;
            }

            header('Content-Type: application/json');
            echo json_encode($rate, JSON_PRETTY_PRINT);
        } catch (\Throwable $e) {
            Log::error('Failed to get Tax Band details for API ID ' . $id . ': ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Could not retrieve Tax Band details.'], JSON_PRETTY_PRINT);
        }
    }
}
