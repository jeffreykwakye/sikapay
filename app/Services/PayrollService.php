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
use Jeffrey\Sikapay\Models\EmployeePayrollDetailsModel; // New model for allowances/deductions
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

        $grossSalary = (float)$employee['current_salary_ghs'];
        $ssnitRate = $this->ssnitRateModel->getCurrentSsnitRate();
        $taxBands = $this->taxBandModel->getTaxBandsForYear((int)date('Y', strtotime($payrollPeriod['start_date'])), false); // Monthly bands

        // 1. Fetch employee-specific allowances and deductions
        $employeePayrollDetails = $this->employeePayrollDetailsModel->getDetailsForEmployee($employeeUserId, $tenantId);

        $totalTaxableAllowances = 0.0;
        $totalNonTaxableAllowances = 0.0;
        $totalDeductions = 0.0;

        foreach ($employeePayrollDetails as $detail) {
            if ($detail['allowance_type'] === 'Allowance') {
                if ($detail['is_taxable']) {
                    $totalTaxableAllowances += (float)$detail['amount'];
                } else {
                    $totalNonTaxableAllowances += (float)$detail['amount'];
                }
            } elseif ($detail['allowance_type'] === 'Deduction') {
                $totalDeductions += (float)$detail['amount'];
            }
        }

        // 2. Calculate Gross Pay (Basic Salary + Taxable Allowances)
        $grossPay = $grossSalary + $totalTaxableAllowances;

        // 3. Calculate SSNIT Contribution (5.5% of basic salary for employee, 13% for employer)
        $employeeSsnit = $grossSalary * $ssnitRate['employee_rate'];
        $employerSsnit = $grossSalary * $ssnitRate['employer_rate'];

        // 4. Calculate Taxable Income (Gross Pay - Employee SSNIT)
        $taxableIncome = $grossPay - $employeeSsnit;

        // 5. Calculate PAYE (directly use monthly taxable income with monthly bands)
        $monthlyPaye = $this->calculatePaye($taxableIncome, $taxBands, false);

        // 6. Calculate Net Pay
        $netPay = $grossPay - $employeeSsnit - $monthlyPaye - $totalDeductions;

        return [
            'gross_salary' => $grossPay,
            'total_taxable_allowances' => $totalTaxableAllowances,
            'total_non_taxable_allowances' => $totalNonTaxableAllowances,
            'employee_ssnit' => $employeeSsnit,
            'employer_ssnit' => $employerSsnit, // Added employer SSNIT
            'taxable_income' => $taxableIncome,
            'paye' => $monthlyPaye,
            'total_deductions' => $totalDeductions + $employeeSsnit + $monthlyPaye,
            'net_pay' => $netPay,
            // ... other details
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
            // 1. Delete existing payslips for this period to prevent duplicate entry errors on re-run
            $this->payslipModel->deletePayslipsForPeriod((int)$payrollPeriod['id'], $tenantId);

            foreach ($employees as $employee) {
                $calculatedPayroll = $this->calculateEmployeePayroll((int)$employee['user_id'], $tenantId, $payrollPeriod);

                // Log calculated payroll for debugging PAYE issue
                Log::debug("Calculated Payroll for Employee " . $employee['user_id'], $calculatedPayroll);

                // Save payslip data
                $payslipData = [
                    'user_id' => (int)$employee['user_id'],
                    'tenant_id' => $tenantId,
                    'payroll_period_id' => (int)$payrollPeriod['id'],
                    'gross_pay' => $calculatedPayroll['gross_salary'],
                    'total_deductions' => $calculatedPayroll['total_deductions'],
                    'net_pay' => $calculatedPayroll['net_pay'],
                    'paye_amount' => $calculatedPayroll['paye'],
                    'ssnit_employee_amount' => $calculatedPayroll['employee_ssnit'],
                    'ssnit_employer_amount' => $calculatedPayroll['employer_ssnit'],
                    'payslip_path' => null, // Will be updated after PDF generation
                ];
                $this->payslipModel->createPayslip($payslipData);

                // TODO: Generate PDF Payslip and update payslip_path
            }

            // Mark payroll period as closed
            $this->payrollPeriodModel->markPeriodAsClosed((int)$payrollPeriod['id'], $tenantId);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            Log::critical("Payroll run transaction failed for Tenant {$tenantId}, Period {$payrollPeriod['id']}: " . $e->getMessage());
            throw new Exception("Payroll processing failed: " . $e->getMessage());
        }
    }

    // Other payroll-related methods will go here
}
