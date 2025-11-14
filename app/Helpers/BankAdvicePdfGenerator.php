<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use FPDF;

class BankAdvicePdfGenerator extends FPDF
{
    private array $reportData;
    private array $tenantData;
    private array $payrollPeriodData;

    public function __construct(array $reportData, array $tenantData, array $payrollPeriodData)
    {
        parent::__construct('L', 'mm', 'A4'); // Landscape mode
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
            $basePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
            $logoUrlPath = $this->tenantData['logo_path'];

            // Ensure we don't have double slashes
            if (strpos($logoUrlPath, '/') === 0) {
                $logoUrlPath = substr($logoUrlPath, 1);
            }

            $logoPath = $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $logoUrlPath);
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
        $this->Cell(0, 10, 'BANK ADVICE REPORT FOR ' . strtoupper($this->payrollPeriodData['period_name']), 0, 1, 'C');
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
        $this->Cell(50, 7, 'Employee Name', 1, 0, 'C', true);
        $this->Cell(50, 7, 'Bank', 1, 0, 'C', true);
        $this->Cell(50, 7, 'Branch', 1, 0, 'C', true);
        $this->Cell(50, 7, 'Account Number', 1, 0, 'C', true);
        $this->Cell(40, 7, 'Account Name', 1, 0, 'C', true);
        $this->Cell(37, 7, 'Net Salary', 1, 1, 'C', true);

        // Table Body
        $this->SetFont('Arial', '', 9);
        $totalNetPay = 0;
        foreach ($this->reportData as $row) {
            $this->Cell(50, 6, $row['employee_name'], 1);
            $this->Cell(50, 6, $row['bank_name'], 1);
            $this->Cell(50, 6, $row['bank_branch'], 1);
            $this->Cell(50, 6, $row['bank_account_number'], 1);
            $this->Cell(40, 6, $row['bank_account_name'], 1);
            $this->Cell(37, 6, number_format((float)$row['net_pay'], 2), 1, 1, 'R');
            $totalNetPay += (float)$row['net_pay'];
        }

        // Total
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(240, 7, 'Total Net Pay', 1, 0, 'R');
        $this->Cell(37, 7, number_format($totalNetPay, 2), 1, 1, 'R');

        // Output the PDF to a string
        return $this->Output('S');
    }
}
