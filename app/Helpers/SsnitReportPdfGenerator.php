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
        parent::__construct('L', 'mm', 'A4'); // Landscape mode for more columns
        $this->reportData = $reportData;
        $this->tenantData = $tenantData;
        $this->payrollPeriodData = $payrollPeriodData;
    }

    // Page header
    public function Header()
    {
        // Construct the full, absolute path to the logo
        $logoPath = '';
        if (!empty($this->tenantData['logo_path'])) {
            // dirname(__DIR__, 2) goes from /app/Helpers up to the project root
            $logoPath = dirname(__DIR__, 2) . '/public' . $this->tenantData['logo_path'];
        }

        // Company Logo (if available and file exists)
        if ($logoPath && file_exists($logoPath)) {
            $this->Image($logoPath, 10, 8, 33);
        }

        // Company Name
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, strtoupper($this->tenantData['legal_name']), 0, 1, 'C');

        // Report Title
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
        $this->Cell(60, 7, 'Employee Name', 1, 0, 'C', true);
        $this->Cell(40, 7, 'SSNIT Number', 1, 0, 'C', true);
        $this->Cell(40, 7, 'Basic Salary', 1, 0, 'C', true);
        $this->Cell(40, 7, 'Employee SSNIT (5.5%)', 1, 0, 'C', true);
        $this->Cell(40, 7, 'Employer SSNIT (13%)', 1, 0, 'C', true);
        $this->Cell(40, 7, 'Total SSNIT (18.5%)', 1, 1, 'C', true);

        // Table Body
        $this->SetFont('Arial', '', 9);
        $totalBasicSalary = 0;
        $totalEmployeeSsnit = 0;
        $totalEmployerSsnit = 0;
        $totalOverallSsnit = 0;

        foreach ($this->reportData as $row) {
            $this->Cell(60, 6, $row['employee_name'], 1);
            $this->Cell(40, 6, $row['ssnit_number'], 1);
            $this->Cell(40, 6, number_format((float)$row['basic_salary'], 2), 1, 0, 'R');
            $this->Cell(40, 6, number_format((float)$row['employee_ssnit'], 2), 1, 0, 'R');
            $this->Cell(40, 6, number_format((float)$row['employer_ssnit'], 2), 1, 0, 'R');
            $this->Cell(40, 6, number_format((float)$row['total_ssnit'], 2), 1, 1, 'R');

            $totalBasicSalary += (float)$row['basic_salary'];
            $totalEmployeeSsnit += (float)$row['employee_ssnit'];
            $totalEmployerSsnit += (float)$row['employer_ssnit'];
            $totalOverallSsnit += (float)$row['total_ssnit'];
        }

        // Total Row
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(100, 7, 'TOTALS', 1, 0, 'R');
        $this->Cell(40, 7, number_format($totalBasicSalary, 2), 1, 0, 'R');
        $this->Cell(40, 7, number_format($totalEmployeeSsnit, 2), 1, 0, 'R');
        $this->Cell(40, 7, number_format($totalEmployerSsnit, 2), 1, 0, 'R');
        $this->Cell(40, 7, number_format($totalOverallSsnit, 2), 1, 1, 'R');

        // Output the PDF to a string
        return $this->Output('S');
    }
}
