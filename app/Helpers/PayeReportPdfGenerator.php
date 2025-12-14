<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use FPDF;
use Jeffrey\Sikapay\Core\Log;
use NumberToWords\NumberToWords;

class PayeReportPdfGenerator extends FPDF
{
    private array $reportData;
    private array $tenantData;
    private array $payrollPeriodData;
    private string $currencySymbol = 'GHS';
    private bool $isCoverLetter = false;

    // New professional color scheme
    private array $documentHeaderColor = [51, 51, 51];    // Deep Gray for top banner
    private array $sectionHeaderColor = [0, 128, 128];     // Teal for table headers
    private array $summaryFillColor = [245, 245, 245];     // Very light gray for summary box
    private array $borderColor = [200, 200, 200];          // Light gray for table borders
    private array $alternateRowColor = [242, 242, 242];    // For alternating table rows

    public function __construct(array $reportData, array $tenantData, array $payrollPeriodData)
    {
        parent::__construct('P', 'mm', 'A4'); // Default to Portrait
        $this->reportData = $reportData;
        $this->tenantData = $tenantData;
        $this->payrollPeriodData = $payrollPeriodData;
    }

    public function Header()
    {
        // Check if it's the cover letter page
        if ($this->isCoverLetter) {
            // Render a header specifically for the Portrait cover letter
            $this->SetFillColor($this->documentHeaderColor[0], $this->documentHeaderColor[1], $this->documentHeaderColor[2]);
            $this->Rect(0, 0, 210, 45, 'F'); // A4 Portrait width

            // --- Left Section: Company Logo ---
            $fullLogoPath = '';
            if (!empty($this->tenantData['logo_path'])) {
                $projectRoot = dirname(__DIR__, 2);
                $relativePath = ltrim(str_replace('/', DIRECTORY_SEPARATOR, $this->tenantData['logo_path']), DIRECTORY_SEPARATOR);
                $fullLogoPath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $relativePath;
            }
            if ($fullLogoPath && file_exists($fullLogoPath)) {
                $this->Image($fullLogoPath, 10, 8, 25);
            }

            // --- Right Section: Company Legal Name (Adjusted for Portrait) ---
            $this->SetXY(110, 8); // Adjusted X for Portrait
            $this->SetFont('Arial', 'B', 16);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(90, 7, strtoupper($this->tenantData['legal_name']), 0, 1, 'R');
            
            // Tenant Details (Adjusted for Portrait)
            $this->SetFont('Arial', '', 9); // Set font directly for address
            $this->SetX(110);
            $this->Cell(90, 4, $this->tenantData['physical_address'], 0, 1, 'R');
            if (!empty($this->tenantData['support_email'])) {
                $this->SetX(110);
                $this->Cell(90, 4, $this->tenantData['support_email'], 0, 1, 'R');
            }
            if (!empty($this->tenantData['phone_number'])) {
                $this->SetX(110);
                $this->Cell(90, 4, $this->tenantData['phone_number'], 0, 1, 'R');
            }
            return; // End here for cover letter header
        }

        // --- Header for main report (Portrait) ---
        $this->SetFillColor($this->documentHeaderColor[0], $this->documentHeaderColor[1], $this->documentHeaderColor[2]);
        $this->Rect(0, 0, 210, 45, 'F'); // A4 Portrait width

        // --- Left Section: Company Logo ---
        $fullLogoPath = '';
        if (!empty($this->tenantData['logo_path'])) {
            $projectRoot = dirname(__DIR__, 2);
            $relativePath = ltrim(str_replace('/', DIRECTORY_SEPARATOR, $this->tenantData['logo_path']), DIRECTORY_SEPARATOR);
            $fullLogoPath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $relativePath;
        }
        if ($fullLogoPath && file_exists($fullLogoPath)) {
            $this->Image($fullLogoPath, 10, 8, 25);
        }

        // --- Right Section: Report Title ---
        $this->SetXY(110, 8); // Adjusted for Portrait
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(90, 7, 'PAYE REPORT', 0, 1, 'R');
        $this->Ln(2);

        // Tenant Details
        $this->SetX(110);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(90, 4, $this->tenantData['legal_name'], 0, 1, 'R');
        $this->SetFont('Arial', '', 9);
        $this->SetX(110);
        $this->Cell(90, 4, $this->tenantData['physical_address'], 0, 1, 'R');
        if (!empty($this->tenantData['support_email'])) {
            $this->SetX(110);
            $this->Cell(90, 4, $this->tenantData['support_email'], 0, 1, 'R');
        }
        if (!empty($this->tenantData['phone_number'])) {
            $this->SetX(110);
            $this->Cell(90, 4, $this->tenantData['phone_number'], 0, 1, 'R');
        }

        $this->SetY(55);
        $this->SetTextColor(0);
    }

    public function Footer()
    {
        if ($this->isCoverLetter) {
            return; // Suppress footer for cover letter
        }
        
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 5, 'This is a statutory report. Unauthorized reproduction is prohibited.', 0, 1, 'C');
        $this->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $this->Cell(0, 5, 'Page ' . ($this->PageNo() - 1) . '/{nb}', 0, 0, 'C'); // Adjust page number
    }

    private function generateCoverLetterContent()
    {
        // Totals needed for the letter body
        $totalPaye = array_sum(array_map(function($row) { return (float)($row['paye_amount'] ?? 0); }, $this->reportData));
        $employeeCount = count($this->reportData);

        // Convert amount to words
        $numberToWords = new NumberToWords();
        $numberTransformer = $numberToWords->getNumberTransformer('en');

        $cedis = (int) $totalPaye;
        $pesewas = (int) round(($totalPaye - $cedis) * 100);

        $cedisInWords = $numberTransformer->toWords($cedis);
        $amountInWords = ucwords($cedisInWords) . ' Ghana Cedis';

        if ($pesewas > 0) {
            $pesewasInWords = $numberTransformer->toWords($pesewas);
            $amountInWords .= ' and ' . ucwords($pesewasInWords) . ' Pesewas';
        }

        $this->SetFont('Arial', '', 11);
        $this->SetMargins(25, 25, 25);
        $this->SetY(55); // Start content below the new portrait header

        // Recipient Info (GRA) - Dynamic based on tenant settings
        $this->SetFont('Arial', '', 11);
        if (!empty($this->tenantData['gra_office_name']) && !empty($this->tenantData['gra_office_address'])) {
            $this->Cell(0, 6, 'The Manager', 0, 1, 'L');
            $this->Cell(0, 6, $this->tenantData['gra_office_name'], 0, 1, 'L');
            // Handle multi-line address
            $addressLines = explode("\n", str_replace("\r\n", "\n", $this->tenantData['gra_office_address']));
            foreach ($addressLines as $line) {
                $this->Cell(0, 6, $line, 0, 1, 'L');
            }
        } else {
            // Fallback to default
            $this->Cell(0, 6, 'The Commissioner', 0, 1, 'L');
            $this->Cell(0, 6, 'Ghana Revenue Authority (GRA)', 0, 1, 'L');
            $this->Cell(0, 6, 'Accra, Ghana', 0, 1, 'L');
        }
        $this->Ln(10);
        $this->Cell(0, 6, date('F jS, Y'), 0, 1, 'L');

        $this->Ln(15);

        // Subject
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, 'RE: SUBMISSION OF PAYE RETURNS FOR ' . strtoupper($this->payrollPeriodData['period_name']), 0, 1, 'L');
        $this->Ln(10);

        // Body
        $this->SetFont('Arial', '', 11);
        $this->MultiCell(0, 6, 'Dear Sir/Madam,', 0, 'L');
        $this->Ln(6);

        $body = sprintf(
            "Please find attached the PAYE returns for %s for the payroll period of %s. " .
            "This report details the PAYE contributions for %d employee(s), with a total amount of %s (%s %s).",
            $this->tenantData['legal_name'],
            strtoupper($this->payrollPeriodData['period_name']),
            $employeeCount,
            $amountInWords,
            $this->currencySymbol,
            number_format($totalPaye, 2)
        );

        $this->MultiCell(0, 6, $body, 0, 'L');
        $this->Ln(6);
        $this->MultiCell(0, 6, 'We trust you will find everything in order and appreciate your cooperation.', 0, 'L');
        $this->Ln(15);

        // Closing
        $this->Cell(0, 6, 'Sincerely,', 0, 1, 'L');
        $this->Ln(25); // Space for signature

        $this->Cell(80, 0, '', 'T', 1, 'L');
        $this->Ln(1);
        $this->SetFont('Arial', '', 10);
        $this->Cell(80, 6, '(Authorised Signatory)', 0, 1, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(80, 6, $this->tenantData['legal_name'], 0, 1, 'L');
    }

    public function generate(): string
    {
        // 1. Generate Cover Letter
        $this->isCoverLetter = true;
        $this->AddPage('P', 'A4'); // Add a Portrait page for the letter
        $this->generateCoverLetterContent();
        $this->isCoverLetter = false;

        // 2. Generate Report Pages
        $this->AddPage('P', 'A4'); // Report also in Portrait
        $this->SetMargins(10, 15, 10); // Reset margins for report pages
        $this->AliasNbPages();

        // Calculate totals for summary
        $totalPaye = array_sum(array_map(function($row) { return (float)($row['paye_amount'] ?? 0); }, $this->reportData));
        $totalTaxableIncome = array_sum(array_map(function($row) { return (float)($row['taxable_income'] ?? 0); }, $this->reportData));
        $employeeCount = count($this->reportData);

        // --- Redesigned Summary Section ---
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0);
        $this->Cell(0, 8, 'Report Summary for ' . strtoupper($this->payrollPeriodData['period_name']), 0, 1, 'L');
        $this->Ln(1);

        $summaryData = [
            'Payroll Period' => strtoupper($this->payrollPeriodData['period_name']),
            'Total Employees' => $employeeCount,
            'Total Taxable Income' => $this->currencySymbol . ' ' . number_format($totalTaxableIncome, 2),
            'Total PAYE Due' => $this->currencySymbol . ' ' . number_format($totalPaye, 2),
        ];

        $this->SetFillColor($this->summaryFillColor[0], $this->summaryFillColor[1], $this->summaryFillColor[2]);
        $this->SetFont('Arial', '', 10);
        $rowHeight = 8;
        $labelWidth = 70;
        
        $yBefore = $this->GetY();
        $summaryBoxHeight = count($summaryData) * $rowHeight;
        $this->Rect(10, $yBefore, 190, $summaryBoxHeight, 'F'); // A4 Portrait width is 210, 10mm margins = 190 content width
        $this->SetY($yBefore);

        foreach($summaryData as $label => $value) {
            $this->SetX(10);
            $this->SetFont('Arial', 'B');
            $this->Cell($labelWidth, $rowHeight, ' ' . $label, 0, 0, 'L');
            $this->SetFont('Arial', '');
            $this->Cell(190 - $labelWidth, $rowHeight, $value . ' ', 0, 1, 'R');
        }
        $this->Ln(8);

        // --- Table Header ---
        $this->SetFillColor($this->sectionHeaderColor[0], $this->sectionHeaderColor[1], $this->sectionHeaderColor[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(0, 0, 0);
        $this->SetFont('Arial', 'B', 9);
        $this->SetLineWidth(0.2);
        
        $colWidths = [60, 40, 45, 45]; // Total 190 for Portrait
        $this->Cell($colWidths[0], 8, 'Employee Name', 1, 0, 'C', true);
        $this->Cell($colWidths[1], 8, 'TIN Number', 1, 0, 'C', true);
        $this->Cell($colWidths[2], 8, 'Taxable Income (' . $this->currencySymbol . ')', 1, 0, 'C', true);
        $this->Cell($colWidths[3], 8, 'PAYE (' . $this->currencySymbol . ')', 1, 1, 'C', true);

        // --- Table Body ---
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(0);
        $this->SetDrawColor($this->borderColor[0], $this->borderColor[1], $this->borderColor[2]);
        $rowHeight = 7;
        $alternateRow = false;
        
        foreach ($this->reportData as $row) {
            if ($this->GetY() + $rowHeight > $this->PageBreakTrigger) {
                $this->AddPage('P', 'A4');
                $this->SetMargins(10, 15, 10); // Reset margins for new page
                // Redraw table header on new page
                $this->SetFillColor($this->sectionHeaderColor[0], $this->sectionHeaderColor[1], $this->sectionHeaderColor[2]);
                $this->SetTextColor(255, 255, 255);
                $this->SetDrawColor(0, 0, 0);
                $this->SetFont('Arial', 'B', 9);
                $this->Cell($colWidths[0], 8, 'Employee Name', 1, 0, 'C', true);
                $this->Cell($colWidths[1], 8, 'TIN Number', 1, 0, 'C', true);
                $this->Cell($colWidths[2], 8, 'Taxable Income (' . $this->currencySymbol . ')', 1, 0, 'C', true);
                $this->Cell($colWidths[3], 8, 'PAYE (' . $this->currencySymbol . ')', 1, 1, 'C', true);
                // Reset body styling
                $this->SetFont('Arial', '', 9);
                $this->SetTextColor(0);
                $this->SetDrawColor($this->borderColor[0], $this->borderColor[1], $this->borderColor[2]);
            }

            $fill = $alternateRow;
            $this->SetFillColor($fill ? $this->alternateRowColor[0] : 255, $fill ? $this->alternateRowColor[1] : 255, $fill ? $this->alternateRowColor[2] : 255);
            
            $this->Cell($colWidths[0], $rowHeight, ' ' . ($row['employee_name'] ?? ''), 1, 0, 'L', true);
            $this->Cell($colWidths[1], $rowHeight, ($row['tin_number'] ?? ''), 1, 0, 'C', true);
            $this->Cell($colWidths[2], $rowHeight, number_format((float)($row['taxable_income'] ?? 0), 2) . ' ', 1, 0, 'R', true);
            $this->Cell($colWidths[3], $rowHeight, number_format((float)($row['paye_amount'] ?? 0), 2) . ' ', 1, 1, 'R', true);
            
            $alternateRow = !$alternateRow;
        }

        // --- Total Row ---
        if ($this->GetY() + 8 > $this->PageBreakTrigger) {
            $this->AddPage('P', 'A4');
            $this->SetMargins(10, 15, 10); // Reset margins for new page
        }

        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor($this->sectionHeaderColor[0], $this->sectionHeaderColor[1], $this->sectionHeaderColor[2]);
        $this->SetTextColor(255);
        $this->SetDrawColor(0);
        $this->Cell($colWidths[0] + $colWidths[1], 8, 'TOTALS', 1, 0, 'C', true);
        $this->Cell($colWidths[2], 8, $this->currencySymbol . ' ' . number_format($totalTaxableIncome, 2), 1, 0, 'R', true);
        $this->Cell($colWidths[3], 8, $this->currencySymbol . ' ' . number_format($totalPaye, 2), 1, 1, 'R', true);

        return $this->Output('S');
    }
}