<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Models\PayrollSettingsModel;
use Jeffrey\Sikapay\Models\TaxBandModel;
use Jeffrey\Sikapay\Models\SsnitRateModel;
use Jeffrey\Sikapay\Models\WithholdingTaxRateModel;
use Jeffrey\Sikapay\Core\Log;
use \Throwable;

class PayrollSettingsController extends Controller
{
    private PayrollSettingsModel $payrollSettingsModel;
    private TaxBandModel $taxBandModel;
    private SsnitRateModel $ssnitRateModel;
    private WithholdingTaxRateModel $withholdingTaxRateModel;

    public function __construct()
    {
        parent::__construct();
        $this->payrollSettingsModel = new PayrollSettingsModel();
        $this->taxBandModel = new TaxBandModel();
        $this->ssnitRateModel = new SsnitRateModel();
        $this->withholdingTaxRateModel = new WithholdingTaxRateModel();
    }

    public function index(): void
    {
        try {
            // 1. Check Subscription Plan: Only 'Standard' and above can view these rates
            if (!$this->auth->isSuperAdmin() && !in_array($this->subscriptionPlan, ['Standard', 'Professional', 'Enterprise'])) {
                $_SESSION['flash_error'] = 'Your current subscription plan does not allow viewing of statutory rates.';
                $this->redirect('/dashboard');
                return;
            }

            // 2. Fetch Data
            $currentYear = (int)date('Y');
            $selectedYear = (int)($_GET['year'] ?? $currentYear);

            $taxBands = $this->taxBandModel->getTaxBandsForYear($selectedYear, true); // Annual bands
            $monthlyTaxBands = $this->taxBandModel->getTaxBandsForYear($selectedYear, false); // Monthly bands
            $ssnitRate = $this->ssnitRateModel->getCurrentSsnitRate();
            $withholdingTaxRates = $this->withholdingTaxRateModel->getAllCurrentEffectiveRates();

            // Get all years for which tax bands exist for the filter dropdown
            $availableTaxYears = $this->taxBandModel->getAvailableTaxYears();
            
            $this->view('payroll/settings', [
                'title' => 'Statutory Rates Overview',
                'taxBands' => $taxBands,
                'monthlyTaxBands' => $monthlyTaxBands,
                'ssnitRate' => $ssnitRate,
                'withholdingTaxRates' => $withholdingTaxRates,
                'selectedYear' => $selectedYear,
                'availableTaxYears' => $availableTaxYears,
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to load statutory rates page for Tenant {$this->tenantId}: " . $e->getMessage());
            $this->redirect('/dashboard', ['flash_error' => 'Could not load statutory rates due to a system error.']);
        }
    }
}
