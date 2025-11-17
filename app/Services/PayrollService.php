<?php

declare(strict_types=1);

namespace Jeffrey\Sikapay\Services;

use Jeffrey\Sikapay\Core\Database;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Models\EmployeeModel;
use Jeffrey\Sikapay\Models\TaxBandModel;
use Jeffrey\Sikapay\Models\SsnitRateModel;
use Jeffrey\Sikapay\Models\PayrollSettingsModel;
use Jeffrey\Sikapay\Models\PayrollPeriodModel;
use Jeffrey\Sikapay\Models\PayslipModel;
use Jeffrey\Sikapay\Models\EmployeePayrollDetailsModel;
use Jeffrey\Sikapay\Models\PayslipAllowanceModel;
use Jeffrey\Sikapay\Models\PayslipDeductionModel;
use Jeffrey\Sikapay\Models\PayslipOvertimeModel;
use Jeffrey\Sikapay\Models\PayslipBonusModel;
use Jeffrey\Sikapay\Models\SsnitAdviceModel;
use Jeffrey\Sikapay\Models\GraPayeAdviceModel;
use Jeffrey\Sikapay\Models\BankAdviceModel;
use Jeffrey\Sikapay\Helpers\PayslipPdfGenerator;
use Jeffrey\Sikapay\Models\AuditModel;
use \PDO;
use \Exception;

class PayrollService
{
    private PDO $db;
    private EmployeeModel $employeeModel;
    private TaxBandModel $taxBandModel;
    private SsnitRateModel $ssnitRateModel;
    private PayrollSettingsModel $payrollSettingsModel;
    private PayrollPeriodModel $payrollPeriodModel;
    private PayslipModel $payslipModel;
    private EmployeePayrollDetailsModel $employeePayrollDetailsModel;
    private PayslipAllowanceModel $payslipAllowanceModel;
    private PayslipDeductionModel $payslipDeductionModel;
    private PayslipOvertimeModel $payslipOvertimeModel;
    private PayslipBonusModel $payslipBonusModel;
    private SsnitAdviceModel $ssnitAdviceModel;
    private GraPayeAdviceModel $graPayeAdviceModel;
    private BankAdviceModel $bankAdviceModel;
    private AuditModel $auditModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->employeeModel = new EmployeeModel();
        $this->taxBandModel = new TaxBandModel();
        $this->ssnitRateModel = new SsnitRateModel();
        $this->payrollSettingsModel = new PayrollSettingsModel();
        $this->payrollPeriodModel = new PayrollPeriodModel();
        $this->payslipModel = new PayslipModel();
        $this->employeePayrollDetailsModel = new EmployeePayrollDetailsModel();
        $this->payslipAllowanceModel = new PayslipAllowanceModel();
        $this->payslipDeductionModel = new PayslipDeductionModel();
        $this->payslipOvertimeModel = new PayslipOvertimeModel();
        $this->payslipBonusModel = new PayslipBonusModel();
        $this->ssnitAdviceModel = new SsnitAdviceModel();
        $this->graPayeAdviceModel = new GraPayeAdviceModel();
        $this->bankAdviceModel = new BankAdviceModel();
        $this->auditModel = new AuditModel();
    }

    /**
     * Calculates payroll for a single employee for a given period.
     *
     * @param int $employeeUserId The user ID of the employee.
     * @param int $tenantId The ID of the tenant.
     * @param array $payrollPeriod The current payroll period details.
     * @return array The calculated payroll details.
     * @throws \Exception If employee not found or critical data missing.
     */
    public function calculateEmployeePayroll(int $employeeUserId, int $tenantId, array $payrollPeriod): array
    {
        $employee = $this->employeeModel->getEmployeeProfile($employeeUserId);
        if (!$employee) {
            throw new Exception("Employee with ID {$employeeUserId} not found.");
        }

        $basicSalary = (float)$employee['current_salary_ghs']; // Explicitly define basic salary
        $ssnitRate = $this->ssnitRateModel->getCurrentSsnitRate();
        $taxBands = $this->taxBandModel->getTaxBandsForYear((int)date('Y', strtotime($payrollPeriod['start_date'])), false); // Monthly bands

        // 1. Fetch employee-specific assigned payroll elements
        $assignedElements = $this->employeePayrollDetailsModel->getDetailsForEmployee($employeeUserId, $tenantId);

        $totalAllowances = 0.0;
        $totalTaxableAllowances = 0.0;
        $totalSsnitChargeableAllowances = 0.0;
        $totalDeductions = 0.0;

        // New arrays to store detailed components
        $detailedAllowances = [];
        $detailedDeductions = [];
        $detailedOvertimes = []; // Assuming overtime is an allowance type for now, will refine if needed
        $detailedBonuses = [];   // Assuming bonus is an allowance type for now, will refine if needed

        // Process assigned payroll elements
        foreach ($assignedElements as $element) {
            $elementValue = (float)$element['assigned_amount'];

            // If percentage, calculate actual amount based on calculation_base
            if ($element['amount_type'] === 'percentage') {
                $baseAmount = 0.0;
                switch ($element['calculation_base']) {
                    case 'basic_salary':
                        $baseAmount = $basicSalary;
                        break;
                    case 'gross_salary':
                        $baseAmount = $basicSalary; // Fallback, as gross is not yet final
                        break;
                    case 'net_salary':
                        $baseAmount = 0.0; 
                        break;
                    default:
                        $baseAmount = $basicSalary; // Fallback to basic salary
                        break;
                }
                $elementValue = $baseAmount * ($elementValue / 100); // Convert percentage to actual amount
            }

            if ($element['category'] === 'allowance') {
                $totalAllowances += $elementValue;
                if ((bool)$element['is_taxable']) {
                    $totalTaxableAllowances += $elementValue;
                }
                if ((bool)$element['is_ssnit_chargeable']) {
                    $totalSsnitChargeableAllowances += $elementValue;
                }
                $detailedAllowances[] = [
                    'name' => $element['name'],
                    'amount' => $elementValue,
                    'is_taxable' => (bool)$element['is_taxable'],
                    'is_ssnit_chargeable' => (bool)$element['is_ssnit_chargeable'],
                ];
            } elseif ($element['category'] === 'deduction') {
                $totalDeductions += $elementValue;
                $detailedDeductions[] = [
                    'name' => $element['name'],
                    'amount' => $elementValue,
                ];
            }
            // TODO: Implement specific logic for overtime and bonuses if they are distinct element types
            // For now, they are treated as general allowances if configured as such.
        }

        // 2. Calculate Gross Pay (Basic Salary + Total Allowances + Overtime + Bonuses)
        // For now, assuming overtime and bonuses are part of totalAllowances if configured as such.
        // If they are separate payroll elements, they need to be processed distinctly.
        $totalOvertime = 0.0; // Placeholder for now
        $totalBonuses = 0.0;  // Placeholder for now

        $grossPay = $basicSalary + $totalAllowances + $totalOvertime + $totalBonuses;

        // 3. Calculate SSNIT Contribution (5.5% of basic salary for employee, 13% for employer)
        // SSNIT is calculated on basic salary + SSNIT-chargeable allowances
        $ssnitBase = $basicSalary + $totalSsnitChargeableAllowances;
        $employeeSsnit = $ssnitBase * $ssnitRate['employee_rate'];
        $employerSsnit = $ssnitBase * $ssnitRate['employer_rate'];

        // 4. Calculate Taxable Income (Gross Pay - Employee SSNIT - Non-taxable allowances/deductions)
        // For now, all deductions are applied after tax calculation for simplicity, except employee SSNIT.
        // If there are non-taxable deductions that reduce taxable income, they need to be factored here.
        $totalTaxableIncome = $grossPay - $employeeSsnit - ($totalAllowances - $totalTaxableAllowances); // Gross - Employee SSNIT - Non-taxable allowances

        // 5. Calculate PAYE (directly use monthly taxable income with monthly bands)
        $monthlyPaye = $this->calculatePaye($totalTaxableIncome, $taxBands, false);

        // 6. Calculate Total Deductions (Custom + Statutory)
        $totalStatutoryDeductions = $employeeSsnit + $monthlyPaye;
        $grandTotalDeductions = $totalDeductions + $totalStatutoryDeductions;

        // 7. Calculate Net Pay
        $netPay = $grossPay - $grandTotalDeductions;

        return [
            'basic_salary' => $basicSalary,
            'total_allowances' => $totalAllowances,
            'total_overtime' => $totalOvertime, // Will be populated from detailedOvertimes
            'total_bonuses' => $totalBonuses,   // Will be populated from detailedBonuses
            'gross_pay' => $grossPay,
            'total_taxable_income' => $totalTaxableIncome,
            'employee_ssnit' => $employeeSsnit,
            'employer_ssnit' => $employerSsnit,
            'paye' => $monthlyPaye,
            'total_deductions' => $grandTotalDeductions, // Now includes custom and statutory deductions
            'net_pay' => $netPay,
            'detailed_allowances' => $detailedAllowances,
            'detailed_deductions' => $detailedDeductions,
            'detailed_overtimes' => $detailedOvertimes,
            'detailed_bonuses' => $detailedBonuses,
            'applied_elements' => $assignedElements, // Return the raw elements for post-processing
        ];
    }

    /**
     * Calculates PAYE based on taxable income and tax bands.
     *
     * @param float $taxableIncome The income to be taxed.
     * @param array $taxBands An array of tax band objects/arrays.
     * @param bool $isAnnual Whether the taxable income and bands are annual.
     * @return float The calculated PAYE.
     */
    private function calculatePaye(float $taxableIncome, array $taxBands, bool $isAnnual): float
    {
        $paye = 0.0;
        $remainingTaxable = $taxableIncome;
        $previousThreshold = 0.00;

        foreach ($taxBands as $band) {
            if ($remainingTaxable <= 0) {
                break;
            }

            $bandEnd = (float)($band['band_end'] ?? PHP_FLOAT_MAX);
            $rate = (float)$band['rate'];

            // Calculate the width of the current band based on cumulative thresholds
            $bandWidth = $bandEnd - $previousThreshold;

            // Determine the portion of income within this band
            $incomeInBand = min($remainingTaxable, $bandWidth);

            Log::debug("PAYE Calculation Step", [
                'taxableIncome' => $taxableIncome,
                'band' => $band,
                'previousThreshold' => $previousThreshold,
                'bandWidth' => $bandWidth,
                'incomeInBand' => $incomeInBand,
                'paye_before' => $paye,
                'remainingTaxable_before' => $remainingTaxable,
            ]);

            if ($incomeInBand > 0) {
                $paye += $incomeInBand * $rate;
                $remainingTaxable -= $incomeInBand;
            }

            $previousThreshold = $bandEnd;

            Log::debug("PAYE Calculation Result", [
                'paye_after' => $paye,
                'remainingTaxable_after' => $remainingTaxable,
            ]);
        }

        return $paye;
    }

    /**
     * Retrieves all active employees for a given tenant who are eligible for payroll.
     *
     * @param int $tenantId
     * @return array An array of employee records.
     */
    public function getEmployeesForPayroll(int $tenantId): array
    {
        // Assuming EmployeeModel has a method to get active, payroll-eligible employees
        return $this->employeeModel->getAllPayrollEligibleEmployees($tenantId);
    }

    /**
     * Orchestrates the entire payroll run process for a given period and set of employees.
     *
     * @param int $tenantId
     * @param array $payrollPeriod
     * @param array $employees
     * @throws \Exception
     */
    public function processPayrollRun(int $tenantId, array $payrollPeriod, array $employees): void
    {
        $this->db->beginTransaction();
        try {
            // 1. Delete existing payslips and related details for this period to prevent duplicate entry errors on re-run
            $this->payslipModel->deletePayslipsForPeriod((int)$payrollPeriod['id'], $tenantId);
            // TODO: Also delete from payslip_allowances, payslip_deductions, etc. for this period

            // Fetch tenant profile data for payslip header
            $tenantProfileModel = new \Jeffrey\Sikapay\Models\TenantProfileModel();
            $tenantData = $tenantProfileModel->findByTenantId($tenantId);

            // Define payslip storage path
            $basePublicPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
            $payslipRelativeDir = "payslips" . DIRECTORY_SEPARATOR . $tenantId . DIRECTORY_SEPARATOR . date('Y', strtotime($payrollPeriod['start_date'])) . DIRECTORY_SEPARATOR . date('m', strtotime($payrollPeriod['start_date']));
            $fullPayslipDirPath = $basePublicPath . DIRECTORY_SEPARATOR . $payslipRelativeDir;

            if (!is_dir($fullPayslipDirPath)) {
                $mkdirResult = mkdir($fullPayslipDirPath, 0777, true);
                Log::debug("mkdir result for {$fullPayslipDirPath}: ", ['result' => $mkdirResult]);
            }

            foreach ($employees as $employee) {
                $calculatedPayroll = $this->calculateEmployeePayroll((int)$employee['user_id'], $tenantId, $payrollPeriod);

                // Log calculated payroll for debugging
                Log::debug("Calculated Payroll for Employee " . $employee['user_id'], $calculatedPayroll);

                // Generate PDF Payslip
                $employeeFullData = $this->employeeModel->getEmployeeProfile((int)$employee['user_id']); // Get full employee data for payslip
                $payslipFileName = "payslip_" . $employeeFullData['user_id'] . "_" . date('YmdHis') . ".pdf"; // Using user ID and timestamp
                $payslipRelativeFilePath = $payslipRelativeDir . DIRECTORY_SEPARATOR . $payslipFileName; // Relative path for DB
                $fullPayslipFilePath = $basePublicPath . DIRECTORY_SEPARATOR . $payslipRelativeFilePath; // Absolute path for file_put_contents

                Log::debug("Payslip File Path: ", ['path' => $fullPayslipFilePath]);

                $pdf = new PayslipPdfGenerator($calculatedPayroll, $employeeFullData, $tenantData, $payrollPeriod);
                $pdfContent = $pdf->generatePayslip();
                $filePutContentsResult = file_put_contents($fullPayslipFilePath, $pdfContent);

                if ($filePutContentsResult === false) {
                    Log::error("Failed to save payslip PDF to {$fullPayslipFilePath}. Check directory permissions.");
                }

                Log::debug("file_put_contents result: ", ['result' => $filePutContentsResult]);

                // Save main payslip data
                $payslipData = [
                    'user_id' => (int)$employee['user_id'],
                    'tenant_id' => $tenantId,
                    'payroll_period_id' => (int)$payrollPeriod['id'],
                    'basic_salary' => $calculatedPayroll['basic_salary'],
                    'total_allowances' => $calculatedPayroll['total_allowances'],
                    'total_overtime' => $calculatedPayroll['total_overtime'],
                    'total_bonuses' => $calculatedPayroll['total_bonuses'],
                    'gross_pay' => $calculatedPayroll['gross_pay'],
                    'total_taxable_income' => $calculatedPayroll['total_taxable_income'],
                    'total_deductions' => $calculatedPayroll['total_deductions'], // This is custom deductions only
                    'net_pay' => $calculatedPayroll['net_pay'],
                    'paye_amount' => $calculatedPayroll['paye'],
                    'ssnit_employee_amount' => $calculatedPayroll['employee_ssnit'],
                    'ssnit_employer_amount' => $calculatedPayroll['employer_ssnit'],
                    'payslip_path' => $payslipRelativeFilePath, // Store relative path in DB
                ];
                $payslipId = $this->payslipModel->createPayslip($payslipData);

                if ($payslipId > 0) {
                    // Save detailed allowances
                    foreach ($calculatedPayroll['detailed_allowances'] as $allowance) {
                        $this->payslipAllowanceModel->create([
                            'payslip_id' => $payslipId,
                            'tenant_id' => $tenantId,
                            'allowance_name' => $allowance['name'],
                            'amount' => $allowance['amount'],
                        ]);
                    }
                    // Save detailed deductions
                    foreach ($calculatedPayroll['detailed_deductions'] as $deduction) {
                        $this->payslipDeductionModel->create([
                            'payslip_id' => $payslipId,
                            'tenant_id' => $tenantId,
                            'deduction_name' => $deduction['name'],
                            'amount' => $deduction['amount'],
                        ]);
                    }
                    // TODO: Save detailed overtimes and bonuses once implemented in calculateEmployeePayroll

                    // Auto-unassign non-recurring elements
                    foreach ($calculatedPayroll['applied_elements'] as $element) {
                        if (!$element['is_recurring']) {
                            $this->employeePayrollDetailsModel->deleteByEmployeeAndElement(
                                (int)$employee['user_id'],
                                (int)$element['payroll_element_id'],
                                $tenantId
                            );

                            $this->auditModel->log(
                                $tenantId,
                                'NON_RECURRING_ELEMENT_REMOVED',
                                [
                                    'employee_user_id' => (int)$employee['user_id'],
                                    'element_name' => $element['name'],
                                    'payslip_id' => $payslipId,
                                    'details' => 'Non-recurring element automatically unassigned after payroll run.'
                                ]
                            );
                        }
                    }
                }
            }

            // After all payslips are processed, generate and save advice records
            $this->saveAdviceRecords($tenantId, (int)$payrollPeriod['id'], $payrollPeriod, $tenantData);

            // Mark payroll period as closed
            $this->payrollPeriodModel->markPeriodAsClosed((int)$payrollPeriod['id'], $tenantId);

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Log::critical("Payroll run transaction failed for Tenant {$tenantId}, Period {$payrollPeriod['id']}: " . $e->getMessage());
            throw new Exception("Payroll processing failed: " . $e->getMessage());
        }
    }

    /**
     * Saves the immutable advice records for a given payroll period.
     * This method should be called after all individual payslips have been processed.
     *
     * @param int $tenantId
     * @param int $payrollPeriodId
     * @param array $payrollPeriodData
     * @param array $tenantData
     */
    private function saveAdviceRecords(int $tenantId, int $payrollPeriodId, array $payrollPeriodData, array $tenantData): void
    {
        // SSNIT Advice
        $ssnitData = $this->payslipModel->getSsnitReportDataByPeriod($payrollPeriodId, $tenantId);
        foreach ($ssnitData as $data) {
            $totalSsnit = (float)$data['ssnit_employee_amount'] + (float)$data['ssnit_employer_amount'];
            $this->ssnitAdviceModel->create([
                'payroll_period_id' => $payrollPeriodId,
                'tenant_id' => $tenantId,
                'employee_name' => $data['first_name'] . ' ' . $data['last_name'],
                'ssnit_number' => $data['ssnit_number'],
                'basic_salary' => $data['basic_salary'],
                'employee_ssnit' => $data['ssnit_employee_amount'],
                'employer_ssnit' => $data['ssnit_employer_amount'],
                'total_ssnit' => $totalSsnit,
            ]);
        }

        // PAYE Advice
        $payeData = $this->payslipModel->getPayslipsByPeriod($payrollPeriodId, $tenantId); // Re-using existing method
        foreach ($payeData as $data) {
            $this->graPayeAdviceModel->create([
                'payroll_period_id' => $payrollPeriodId,
                'tenant_id' => $tenantId,
                'employee_name' => $data['first_name'] . ' ' . $data['last_name'],
                'tin_number' => $data['tin_number'],
                'taxable_income' => $data['total_taxable_income'], // Now using the correct column name
                'paye_amount' => $data['paye_amount'],
            ]);
        }

        // Bank Advice
        $bankAdviceData = $this->payslipModel->getBankAdviceDataByPeriod($payrollPeriodId, $tenantId);
        foreach ($bankAdviceData as $data) {
            $this->bankAdviceModel->create([
                'payroll_period_id' => $payrollPeriodId,
                'tenant_id' => $tenantId,
                'employee_name' => $data['first_name'] . ' ' . $data['last_name'],
                'bank_name' => $data['bank_name'],
                'bank_branch' => $data['bank_branch'],
                'bank_account_number' => $data['bank_account_number'],
                'bank_account_name' => $data['bank_account_name'],
                'net_pay' => $data['net_pay'],
            ]);
        }
    }

    /**
     * Retrieves payslips for a specific payroll period and tenant.
     *
     * @param int $periodId
     * @param int $tenantId
     * @return array An array of payslip records.
     */
    public function getPayslipsByPeriod(int $periodId, int $tenantId): array
    {
        return $this->payslipModel->getPayslipsByPeriod($periodId, $tenantId);
    }

    /**
     * Retrieves a single payslip record by its ID and tenant.
     *
     * @param int $payslipId
     * @param int $tenantId
     * @return array|null The payslip record, or null if not found.
     */
    public function getPayslipById(int $payslipId, int $tenantId): ?array
    {
        return $this->payslipModel->find($payslipId, $tenantId);
    }

    /**
     * Retrieves full employee data for payslip generation.
     *
     * @param int $userId
     * @return array|null
     */
    public function getEmployeeFullData(int $userId): ?array
    {
        return $this->employeeModel->getEmployeeProfile($userId);
    }

    /**
     * Retrieves tenant data for payslip generation.
     *
     * @param int $tenantId
     * @return array|null
     */
    public function getTenantData(int $tenantId): ?array
    {
        $tenantProfileModel = new \Jeffrey\Sikapay\Models\TenantProfileModel();
        return $tenantProfileModel->findByTenantId($tenantId);
    }

    // Other payroll-related methods will go here
}
