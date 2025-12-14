<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use FPDF;
use Jeffrey\Sikapay\Core\Log;

class PayslipPdfGenerator extends FPDF
{
    private array $payslipData;
    private array $employeeData;
    private array $tenantData;
    private array $payrollPeriodData;
    private string $currencySymbol = 'GHS';
    private array $primaryColor = [200, 200, 200]; // Deeper gray for the header
    private array $netPayColor = [105, 105, 105]; // Dark Gray for Net Pay
    private array $headerColor = [242, 242, 242]; // A light grey

    public function __construct(array $payslipData, array $employeeData, array $tenantData, array $payrollPeriodData)
    {
        parent::__construct('P', 'mm', 'A4');
        $this->payslipData = $payslipData;
        $this->employeeData = $employeeData;
        $this->tenantData = $tenantData;
        $this->payrollPeriodData = $payrollPeriodData;
    }

    public function Header()
    {
        // Set header background color
        $this->SetFillColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
        $this->Rect(0, 0, 210, 35, 'F'); // Header height set to 35mm

        // --- Left Section: Company Logo ---
        $fullLogoPath = '';
        if (!empty($this->tenantData['logo_path'])) {
            $logo_path = $this->tenantData['logo_path'];
            // Prepend 'public/' if the path starts with '/assets/'
            if (strpos($logo_path, '/assets/') === 0) {
                $logo_path = 'public' . $logo_path;
            }
            // Construct the absolute path based on the project root
            $projectRoot = dirname(__DIR__, 2); // Go up 2 directories from app/Helpers to project root
            $fullLogoPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $logo_path);
        }
        
        Log::debug("PayslipPdfGenerator: Attempting to load logo from: " . $fullLogoPath);

        if ($fullLogoPath && file_exists($fullLogoPath)) {
            $this->Image($fullLogoPath, 10, 8, 25); // X=10, Y=8 (adjusted up), Width=25
        } else {
            Log::warning("PayslipPdfGenerator: Logo file not found or path is empty: " . ($fullLogoPath ?: "EMPTY PATH"));
        } 

        // --- Right Section: Payslip Title & Period ---
        $this->SetXY(150, 14); // Position for title, adjusted vertically to be more central
        $this->SetFont('Arial', 'B', 30);
        $this->SetTextColor(70, 70, 70); // Dark gray text for "PAYSLIP", a tad lighter than black
        $this->Cell(55, 7, 'PAYSLIP', 0, 1, 'R');

        // Reset Y position for content after header
        $this->SetY(45);
        $this->SetTextColor(0); // Reset text color to black for body content
    }

    public function Footer()
    {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        
        $this->Cell(0, 5, 'This is a confidential document intended solely for the named recipient.', 0, 1, 'C');
        $this->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $this->Cell(0, 5, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    public function generatePayslip(): string
    {
        $this->AddPage();
        $this->AliasNbPages();

        $this->drawCompanyAndEmployeeDetailsSection();
        $this->drawPayPeriodDetails();

        // Draw Earnings and Deductions side-by-side
        $this->drawEarningsAndDeductions();

        // Draw Summary
        $this->drawSummary();

        // Draw Employer Contributions
        $this->drawEmployerContributions();

        return $this->Output('S');
    }

    private function drawCompanyAndEmployeeDetailsSection()
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0);
        // $this->Ln(1); // Add some space after the header

        $leftColX = 10;
        $rightColX = 110; // Starting X for the right column, Adjusted from 130 to 110 for better spacing
        $colWidth = 90;
        $labelWidth = 30; // Width for labels like "Name:", "ID:"
        $valueWidth = $colWidth - $labelWidth; // Width for actual values

        $currentY = $this->GetY();

        // --- Left Column: Company Details ---
        $this->SetX($leftColX);
        $this->SetFont('Arial', 'B', 14); // Company Name bolder
        $this->Cell($colWidth, 5, $this->tenantData['legal_name'], 0, 1, 'L');
        $this->SetX($leftColX);
        $this->SetFont('Arial', '', 9);
        $this->Cell($colWidth, 5, $this->tenantData['physical_address'], 0, 1, 'L');
        $this->SetX($leftColX);
        $this->Cell($colWidth, 5, 'Email: ' . ($this->tenantData['support_email'] ?? 'N/A'), 0, 1, 'L');
        $this->SetX($leftColX);
        $this->Cell($colWidth, 5, 'Phone: ' . ($this->tenantData['phone_number'] ?? 'N/A'), 0, 1, 'L');

        // --- Right Column: Employee Details ---
        $this->SetY($currentY); // Reset Y to the start of the section for the right column
        $this->SetX($rightColX);
        $this->SetFont('Arial', '', 9);
        
        $this->SetX($rightColX);
        $this->Cell($labelWidth, 5, 'Name:', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($valueWidth, 5, $this->employeeData['first_name'] . ' ' . $this->employeeData['last_name'], 0, 1, 'L');
        
        $this->SetX($rightColX);
        $this->SetFont('Arial', '', 9);
        $this->Cell($labelWidth, 5, 'ID:', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($valueWidth, 5, $this->employeeData['employee_id'], 0, 1, 'L');

        $this->SetX($rightColX);
        $this->SetFont('Arial', '', 9);
        $this->Cell($labelWidth, 5, 'Position:', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($valueWidth, 5, $this->employeeData['position_title'], 0, 1, 'L');
        
        $this->SetX($rightColX);
        $this->SetFont('Arial', '', 9);
        $this->Cell($labelWidth, 5, 'Department:', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($valueWidth, 5, $this->employeeData['department_name'], 0, 1, 'L');
        
        $this->SetX($rightColX);
        $this->SetFont('Arial', '', 9);
        $this->Cell($labelWidth, 5, 'Employment Type:', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($valueWidth, 5, $this->employeeData['employment_type'] ?? 'N/A', 0, 1, 'L');

        $this->Ln(5); // Add some space after this section
    }
    
    private function drawPayPeriodDetails()
    {
        $this->SetFillColor($this->headerColor[0], $this->headerColor[1], $this->headerColor[2]);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(47.5, 8, 'Payroll Period', 1, 0, 'C', true);
        $this->Cell(47.5, 8, 'TIN', 1, 0, 'C', true);
        $this->Cell(47.5, 8, 'SSNIT Number', 1, 0, 'C', true);
        $this->Cell(47.5, 8, 'Payment Date', 1, 1, 'C', true);

        $this->SetFont('Arial', '', 10);
        $payDate = !empty($this->payrollPeriodData['payment_date']) 
            ? date('F j, Y', strtotime($this->payrollPeriodData['payment_date'])) 
            : 'N/A';
        $this->Cell(47.5, 8, $this->payrollPeriodData['period_name'] ?? 'N/A', 1, 0, 'C');
        $this->Cell(47.5, 8, $this->employeeData['tin_number'] ?? 'N/A', 1, 0, 'C');
        $this->Cell(47.5, 8, $this->employeeData['ssnit_number'] ?? 'N/A', 1, 0, 'C');
        $this->Cell(47.5, 8, $payDate, 1, 1, 'C');
        $this->Ln(10);
    }

    private function drawEarningsAndDeductions()
    {
        $startY = $this->GetY();
        $this->SetFont('Arial', 'B', 11);
        
        // --- Draw Earnings Table ---
        $this->SetFillColor($this->headerColor[0], $this->headerColor[1], $this->headerColor[2]);
        $this->SetX(10);
        $this->Cell(95, 8, 'Earnings', 'TLR', 1, 'L', true);
        $this->SetX(10);
        $this->SetFont('Arial', '', 9);
        $this->Cell(95 * 0.6, 6, 'Item', 'LR', 0, 'L');
        $this->Cell(95 * 0.4, 6, 'Amount (' . $this->currencySymbol . ')', 'LR', 1, 'R');
        
        // Content
        $this->SetFont('Arial', '', 10);
        $this->SetX(10);
        $this->Cell(95 * 0.6, 7, ' Basic Salary', 'LR', 0, 'L');
        $this->Cell(95 * 0.4, 7, number_format((float)($this->payslipData['basic_salary'] ?? 0.0), 2) . ' ', 'LR', 1, 'R');

        if (!empty($this->payslipData['detailed_allowances'])) {
            foreach ($this->payslipData['detailed_allowances'] as $item) {
                $this->SetX(10);
                $this->Cell(95 * 0.6, 7, ' ' . $item['name'], 'LR', 0, 'L');
                $this->Cell(95 * 0.4, 7, number_format((float)$item['amount'], 2) . ' ', 'LR', 1, 'R');
            }
        }

        // Total Row for Earnings
        $this->SetX(10);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(95 * 0.6, 7, ' Total Earnings', 'LBR', 0, 'L', true);
        $this->Cell(95 * 0.4, 7, number_format((float)($this->payslipData['gross_pay'] ?? 0.0), 2) . ' ', 'RBL', 1, 'R', true);
        $earningsEndY = $this->GetY();

        
        // --- Draw Deductions Table ---
        $this->SetY($startY); // Reset Y to start next to the earnings table
        $this->SetX(115);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(85, 8, 'Deductions', 'TLR', 1, 'L', true);
        $this->SetX(115);
        $this->SetFont('Arial', '', 9);
        $this->Cell(85 * 0.6, 6, 'Item', 'LR', 0, 'L');
        $this->Cell(85 * 0.4, 6, 'Amount (' . $this->currencySymbol . ')', 'LR', 1, 'R');

        // Content
        $this->SetFont('Arial', '', 10);
        $this->SetX(115);
        $this->Cell(85 * 0.6, 7, ' Employee SSNIT', 'LR', 0, 'L');
        $this->Cell(85 * 0.4, 7, number_format((float)($this->payslipData['employee_ssnit'] ?? 0.0), 2) . ' ', 'LR', 1, 'R');

        $this->SetX(115);
        $this->Cell(85 * 0.6, 7, ' PAYE Tax', 'LR', 0, 'L');
        $this->Cell(85 * 0.4, 7, number_format((float)($this->payslipData['paye'] ?? 0.0), 2) . ' ', 'LR', 1, 'R');

        if (!empty($this->payslipData['detailed_deductions'])) {
            foreach ($this->payslipData['detailed_deductions'] as $item) {
                $this->SetX(115);
                $this->Cell(85 * 0.6, 7, ' ' . $item['name'], 'LR', 0, 'L');
                $this->Cell(85 * 0.4, 7, number_format((float)$item['amount'], 2) . ' ', 'LR', 1, 'R');
            }
        }
        
        // Total Row for Deductions
        $this->SetX(115);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(85 * 0.6, 7, ' Total Deductions', 'LBR', 0, 'L', true);
        $this->Cell(85 * 0.4, 7, number_format((float)($this->payslipData['total_deductions'] ?? 0.0), 2) . ' ', 'RBL', 1, 'R', true);
        $deductionsEndY = $this->GetY();


        // Set Y for the next section to be below the taller of the two tables
        $this->SetY(max($earningsEndY, $deductionsEndY));
        $this->Ln(10);
    }
    
    private function drawSummary()
    {
        $currentX = $this->GetX();
        $currentY = $this->GetY();
        $boxWidth = 85;
        $boxHeight = 20;
        $boxX = 115; // Aligns with the deductions table start

        // Draw the colored background box
        $this->SetFillColor($this->netPayColor[0], $this->netPayColor[1], $this->netPayColor[2]);
        $this->Rect($boxX, $currentY, $boxWidth, $boxHeight, 'F');
        
        $this->SetTextColor(255, 255, 255);

        // Print "Net Pay" label
        $this->SetXY($boxX, $currentY + 3);
        $this->SetFont('Arial', '', 11); // Regular font, smaller size
        $this->Cell($boxWidth - 5, 6, 'Net Pay', 0, 1, 'R');

        // Print Net Pay Amount
        $this->SetXY($boxX, $currentY + 9);
        $this->SetFont('Arial', 'B', 16); // Bold font, larger size
        $this->Cell($boxWidth - 5, 8, $this->currencySymbol . ' ' . number_format((float)($this->payslipData['net_pay'] ?? 0.0), 2), 0, 1, 'R');

        // Reset settings
        $this->SetTextColor(0);
        $this->SetY($currentY + $boxHeight); // Move below the box
        $this->Ln(10);
    }

    private function drawEmployerContributions()
    {
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor($this->headerColor[0], $this->headerColor[1], $this->headerColor[2]);
        $this->Cell(190, 8, 'For Information: Employer Contributions', 'LTR', 1, 'L', true);
        
        $this->drawTwoColRow('Employer SSNIT (Tier 2)', $this->payslipData['employer_ssnit'] ?? 0.0, 190, false, 'LBR');
        // Add other employer contributions here if they become available
    }

    // Helper function to draw a two-column row
    private function drawTwoColRow($label, $value, $width, $isHeader = false, $border = 'LR')
    {
        $this->SetFont('Arial', $isHeader ? 'B' : '', 10);
        $this->Cell($width * 0.6, 7, ' ' . $label, $border, 0, 'L');
        $this->Cell($width * 0.4, 7, number_format((float)$value, 2) . ' ', $border, 0, 'R');
        $this->Ln();
    }
}
