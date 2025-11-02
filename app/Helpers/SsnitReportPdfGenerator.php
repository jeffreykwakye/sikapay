<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use FPDF;

class SsnitReportPdfGenerator extends FPDF
{
    private array $reportData;
    private array $tenantData;
    private array $payrollPeriodData;

    public function __construct(array $reportData, array $tenantData, array $payrollPeriodData)
    {
        parent::__construct();
        $this->reportData = $reportData;
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

        // Report Title
        $this->Ln(10);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'SSNIT REPORT FOR ' . strtoupper($this->payrollPeriodData['period_name']), 0, 1, 'C');
        $this->Ln(5);
    }

    // Page footer
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Report Body
    public function generate(): string
    {
        $this->AddPage();
        $this->AliasNbPages();

        $this->SetFont('Arial', '', 10);

        // Table Header
        $this->SetFillColor(230, 230, 230);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(40, 7, 'Employee ID', 1, 0, 'C', true);
        $this->Cell(60, 7, 'Employee Name', 1, 0, 'C', true);
        $this->Cell(40, 7, 'Gross Salary', 1, 0, 'C', true);
        $this->Cell(40, 7, 'Employee SSNIT', 1, 0, 'C', true);
        $this->Cell(40, 7, 'Employer SSNIT', 1, 1, 'C', true);

        // Table Body
        $this->SetFont('Arial', '', 10);
        $totalEmployeeSsnit = 0;
        $totalEmployerSsnit = 0;
        foreach ($this->reportData as $row) {
            $this->Cell(40, 6, $row['employee_id'], 1);
            $this->Cell(60, 6, $row['employee_name'], 1);
            $this->Cell(40, 6, number_format((float)$row['gross_salary'], 2), 1, 0, 'R');
            $this->Cell(40, 6, number_format((float)$row['employee_ssnit'], 2), 1, 0, 'R');
            $this->Cell(40, 6, number_format((float)$row['employer_ssnit'], 2), 1, 1, 'R');
            $totalEmployeeSsnit += (float)$row['employee_ssnit'];
            $totalEmployerSsnit += (float)$row['employer_ssnit'];
        }

        // Total
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(140, 7, 'Total Employee SSNIT', 1, 0, 'R');
        $this->Cell(40, 7, number_format($totalEmployeeSsnit, 2), 1, 1, 'R');
        $this->Cell(140, 7, 'Total Employer SSNIT', 1, 0, 'R');
        $this->Cell(40, 7, number_format($totalEmployerSsnit, 2), 1, 1, 'R');

        // Output the PDF to a string
        return $this->Output('S');
    }
}
