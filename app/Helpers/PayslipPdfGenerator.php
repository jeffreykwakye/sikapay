<?php

declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use FPDF;

class PayslipPdfGenerator extends FPDF
{
    private array $payslipData;
    private array $employeeData;
    private array $tenantData;
    private array $payrollPeriodData;

    public function __construct(array $payslipData, array $employeeData, array $tenantData, array $payrollPeriodData)
    {
        parent::__construct();
        $this->payslipData = $payslipData;
        $this->employeeData = $employeeData;
        $this->tenantData = $tenantData;
        $this->payrollPeriodData = $payrollPeriodData;
    }

    // Page header
    public function Header()
    {
        // Company Logo (if available)
        if (!empty($this->tenantData['logo_path']) && file_exists($this->tenantData['logo_path'])) {
            $this->Image($this->tenantData['logo_path'], 10, 8, 33);
        }

        // Company Name
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(80);
        $this->Cell(30, 10, strtoupper($this->tenantData['legal_name']), 0, 1, 'C');

        // Company Address
        $this->SetFont('Arial', '', 10);
        $this->Cell(80);
        $this->Cell(30, 5, $this->tenantData['physical_address'], 0, 1, 'C');

        // Payslip Title
        $this->Ln(10);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'PAYSLIP FOR ' . strtoupper($this->payrollPeriodData['period_name']), 0, 1, 'C');
        $this->Ln(5);
    }

    // Page footer
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Payslip Body
    public function generatePayslip(): string
    {
        $this->AddPage();
        $this->AliasNbPages();

        $this->SetFont('Arial', '', 10);

        // Employee Details
        $this->SetFillColor(230, 230, 230);
        $this->Cell(0, 7, 'EMPLOYEE DETAILS', 0, 1, 'L', true);
        $this->Cell(50, 6, 'Employee Name:', 0); $this->Cell(0, 6, $this->employeeData['first_name'] . ' ' . $this->employeeData['last_name'], 0, 1);
        $this->Cell(50, 6, 'Employee ID:', 0); $this->Cell(0, 6, $this->employeeData['employee_id'], 0, 1);
        $this->Cell(50, 6, 'Position:', 0); $this->Cell(0, 6, $this->employeeData['position_title'], 0, 1);
        $this->Cell(50, 6, 'Department:', 0); $this->Cell(0, 6, $this->employeeData['department_name'], 0, 1);
        $this->Ln(5);

        // Earnings
        $this->SetFillColor(230, 230, 230);
        $this->Cell(0, 7, 'EARNINGS', 0, 1, 'L', true);
        $this->Cell(50, 6, 'Basic Salary:', 0); $this->Cell(0, 6, number_format((float)($this->payslipData['basic_salary'] ?? 0.0), 2) . ' GHS', 0, 1);
        
        // Detailed Allowances
        if (!empty($this->payslipData['detailed_allowances'])) {
            foreach ($this->payslipData['detailed_allowances'] as $allowance) {
                $this->Cell(50, 6, $allowance['name'] . ':', 0);
                $this->Cell(0, 6, number_format((float)$allowance['amount'], 2) . ' GHS', 0, 1);
            }
        }
        $this->Ln(5);

        // Deductions
        $this->SetFillColor(230, 230, 230);
        $this->Cell(0, 7, 'DEDUCTIONS', 0, 1, 'L', true);
        $this->Cell(50, 6, 'Employee SSNIT:', 0); $this->Cell(0, 6, number_format((float)($this->payslipData['employee_ssnit'] ?? 0.0), 2) . ' GHS', 0, 1);
        $this->Cell(50, 6, 'PAYE Tax:', 0); $this->Cell(0, 6, number_format((float)($this->payslipData['paye'] ?? 0.0), 2) . ' GHS', 0, 1);
        
        // Detailed Deductions
        if (!empty($this->payslipData['detailed_deductions'])) {
            foreach ($this->payslipData['detailed_deductions'] as $deduction) {
                $this->Cell(50, 6, $deduction['name'] . ':', 0);
                $this->Cell(0, 6, number_format((float)$deduction['amount'], 2) . ' GHS', 0, 1);
            }
        }
        $this->Ln(5);

        // Summary
        $this->SetFillColor(200, 200, 200);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(50, 7, 'GROSS PAY:', 0); $this->Cell(0, 7, number_format((float)($this->payslipData['gross_pay'] ?? 0.0), 2) . ' GHS', 0, 1);
        $this->Cell(50, 7, 'TOTAL DEDUCTIONS:', 0); $this->Cell(0, 7, number_format((float)($this->payslipData['total_deductions'] ?? 0.0), 2) . ' GHS', 0, 1);
        $this->Cell(50, 7, 'NET PAY:', 0); $this->Cell(0, 7, number_format((float)($this->payslipData['net_pay'] ?? 0.0), 2) . ' GHS', 0, 1);
        $this->Ln(10);

        // Employer Contributions (Informational)
        $this->SetFillColor(230, 230, 230);
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 7, 'EMPLOYER CONTRIBUTIONS', 0, 1, 'L', true);
        $this->Cell(50, 6, 'Employer SSNIT:', 0); $this->Cell(0, 6, number_format((float)($this->payslipData['employer_ssnit'] ?? 0.0), 2) . ' GHS', 0, 1);
        $this->Ln(10);

        // Output the PDF to a string
        return $this->Output('S');
    }
}
