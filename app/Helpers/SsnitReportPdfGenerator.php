<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use FPDF;
use Jeffrey\Sikapay\Core\Log;
use NumberToWords\NumberToWords;

class SsnitReportPdfGenerator extends FPDF
{
    private array $reportData;
    private array $tenantData;
    private array $payrollPeriodData;
    private string $currencySymbol = 'GHS';
    private bool $isCoverLetter = false;
    private bool $shouldIncludeCoverLetter;

    // New professional color scheme
    private array $documentHeaderColor = [51, 51, 51];
    private array $sectionHeaderColor = [0, 128, 128];
    private array $summaryFillColor = [245, 245, 245];
    private array $borderColor = [200, 200, 200];
    private array $alternateRowColor = [242, 242, 242];

    public function __construct(array $reportData, array $tenantData, array $payrollPeriodData, bool $includeCoverLetter = false)
    {
        parent::__construct('L', 'mm', 'A4');
        $this->reportData = $reportData;
        $this->tenantData = $tenantData;
        $this->payrollPeriodData = $payrollPeriodData;
        $this->shouldIncludeCoverLetter = $includeCoverLetter;
    }

    public function Header()
    {
        if ($this->isCoverLetter) {
            $this->SetFillColor($this->documentHeaderColor[0], $this->documentHeaderColor[1], $this->documentHeaderColor[2]);
            $this->Rect(0, 0, 210, 45, 'F');

            $fullLogoPath = '';
            if (!empty($this->tenantData['logo_path'])) {
                $projectRoot = dirname(__DIR__, 2);
                $relativePath = ltrim(str_replace('/', DIRECTORY_SEPARATOR, $this->tenantData['logo_path']), DIRECTORY_SEPARATOR);
                $fullLogoPath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $relativePath;
            }
            if ($fullLogoPath && file_exists($fullLogoPath)) {
                $this->Image($fullLogoPath, 10, 8, 25);
            }

            $this->SetXY(110, 8);
            $this->SetFont('Arial', 'B', 16);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(90, 7, strtoupper($this->tenantData['legal_name']), 0, 1, 'R');
            
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
            return;
        }

        $this->SetFillColor($this->documentHeaderColor[0], $this->documentHeaderColor[1], $this->documentHeaderColor[2]);
        $this->Rect(0, 0, 297, 45, 'F');

        $fullLogoPath = '';
        if (!empty($this->tenantData['logo_path'])) {
            $projectRoot = dirname(__DIR__, 2);
            $relativePath = ltrim(str_replace('/', DIRECTORY_SEPARATOR, $this->tenantData['logo_path']), DIRECTORY_SEPARATOR);
            $fullLogoPath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $relativePath;
        }
        if ($fullLogoPath && file_exists($fullLogoPath)) {
            $this->Image($fullLogoPath, 10, 8, 25);
        }

        $this->SetXY(200, 8);
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(90, 7, 'SSNIT REPORT', 0, 1, 'R');
        $this->Ln(2);

        $this->SetX(200);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(90, 4, $this->tenantData['legal_name'], 0, 1, 'R');
        $this->SetFont('Arial', '', 9);
        $this->SetX(200);
        $this->Cell(90, 4, $this->tenantData['physical_address'], 0, 1, 'R');
        if (!empty($this->tenantData['support_email'])) {
            $this->SetX(200);
            $this->Cell(90, 4, $this->tenantData['support_email'], 0, 1, 'R');
        }
        if (!empty($this->tenantData['phone_number'])) {
            $this->SetX(200);
            $this->Cell(90, 4, $this->tenantData['phone_number'], 0, 1, 'R');
        }

        $this->SetY(55);
        $this->SetTextColor(0);
    }

    public function Footer()
    {
        if ($this->isCoverLetter) {
            return;
        }
        
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 5, 'This is a statutory report. Unauthorized reproduction is prohibited.', 0, 1, 'C');
        $this->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $this->Cell(0, 5, 'Page ' . ($this->shouldIncludeCoverLetter ? $this->PageNo() - 1 : $this->PageNo()) . '/{nb}', 0, 0, 'C');
    }

    private function generateCoverLetterContent()
    {
        $totalOverallSsnit = array_sum(array_map(function($row) { return (float)($row['total_ssnit'] ?? 0); }, $this->reportData));
        $employeeCount = count($this->reportData);

        $numberToWords = new NumberToWords();
        $numberTransformer = $numberToWords->getNumberTransformer('en');
        $cedis = (int) $totalOverallSsnit;
        $pesewas = (int) round(($totalOverallSsnit - $cedis) * 100);
        $cedisInWords = $numberTransformer->toWords($cedis);
        $amountInWords = ucwords($cedisInWords) . ' Ghana Cedis';
        if ($pesewas > 0) {
            $pesewasInWords = $numberTransformer->toWords($pesewas);
            $amountInWords .= ' and ' . ucwords($pesewasInWords) . ' Pesewas';
        }

        $this->SetFont('Arial', '', 11);
        $this->SetMargins(25, 25, 25);
        $this->SetY(55);

        $this->SetFont('Arial', '', 11);
        if (!empty($this->tenantData['ssnit_office_name']) && !empty($this->tenantData['ssnit_office_address'])) {
            $this->Cell(0, 6, 'The Manager', 0, 1, 'L');
            $this->Cell(0, 6, $this->tenantData['ssnit_office_name'], 0, 1, 'L');
            $addressLines = explode("\n", str_replace("\r\n", "\n", $this->tenantData['ssnit_office_address']));
            foreach ($addressLines as $line) {
                $this->Cell(0, 6, $line, 0, 1, 'L');
            }
        } else {
            $this->Cell(0, 6, 'The Director-General', 0, 1, 'L');
            $this->Cell(0, 6, 'Social Security and National Insurance Trust', 0, 1, 'L');
            $this->Cell(0, 6, 'P.O. Box MB 149', 0, 1, 'L');
            $this->Cell(0, 6, 'Accra, Ghana', 0, 1, 'L');
        }
        $this->Ln(10);
        $this->Cell(0, 6, date('F jS, Y'), 0, 1, 'L');
        $this->Ln(15);

        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, 'RE: SUBMISSION OF SSNIT CONTRIBUTIONS FOR ' . strtoupper($this->payrollPeriodData['period_name']), 0, 1, 'L');
        $this->Ln(10);

        $this->SetFont('Arial', '', 11);
        $this->MultiCell(0, 6, 'Dear Sir/Madam,', 0, 'L');
        $this->Ln(6);

        $body = sprintf(
            "Please find attached the SSNIT contribution report for %s for the payroll period of %s. " .
            "This report details contributions for %d employee(s), with a total amount of %s (%s %s).",
            $this->tenantData['legal_name'],
            strtoupper($this->payrollPeriodData['period_name']),
            $employeeCount,
            $amountInWords,
            $this->currencySymbol,
            number_format($totalOverallSsnit, 2)
        );

        $this->MultiCell(0, 6, $body, 0, 'L');
        $this->Ln(6);
        $this->MultiCell(0, 6, 'We trust you will find everything in order and appreciate your cooperation.', 0, 'L');
        $this->Ln(15);

        $this->Cell(0, 6, 'Sincerely,', 0, 1, 'L');
        $this->Ln(25);

        $this->Cell(80, 0, '', 'T', 1, 'L');
        $this->Ln(1);
        $this->SetFont('Arial', '', 10);
        $this->Cell(80, 6, '(Authorised Signatory)', 0, 1, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(80, 6, $this->tenantData['legal_name'], 0, 1, 'L');
    }

    public function generate(): string
    {
        if ($this->shouldIncludeCoverLetter) {
            $this->isCoverLetter = true;
            $this->AddPage('P', 'A4');
            $this->generateCoverLetterContent();
            $this->isCoverLetter = false;
        }

        $this->AddPage('L', 'A4');
        $this->SetMargins(10, 15, 10);
        $this->AliasNbPages();

        $totalBasicSalary = array_sum(array_map(function($row) { return (float)($row['basic_salary'] ?? 0); }, $this->reportData));
        $totalEmployeeSsnit = array_sum(array_map(function($row) { return (float)($row['employee_ssnit'] ?? 0); }, $this->reportData));
        $totalEmployerSsnit = array_sum(array_map(function($row) { return (float)($row['employer_ssnit'] ?? 0); }, $this->reportData));
        $totalOverallSsnit = array_sum(array_map(function($row) { return (float)($row['total_ssnit'] ?? 0); }, $this->reportData));
        $employeeCount = count($this->reportData);

        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0);
        $this->Cell(0, 8, 'Report Summary', 0, 1, 'L');
        $this->Ln(1);

        $summaryData = [
            'Payroll Period' => strtoupper($this->payrollPeriodData['period_name']),
            'Total Employees' => $employeeCount,
            'Total Basic Salary' => $this->currencySymbol . ' ' . number_format($totalBasicSalary, 2),
            'Total Employee SSNIT' => $this->currencySymbol . ' ' . number_format($totalEmployeeSsnit, 2),
            'Total Employer SSNIT' => $this->currencySymbol . ' ' . number_format($totalEmployerSsnit, 2),
            'Total SSNIT Due' => $this->currencySymbol . ' ' . number_format($totalOverallSsnit, 2),
        ];

        $this->SetFillColor($this->summaryFillColor[0], $this->summaryFillColor[1], $this->summaryFillColor[2]);
        $this->SetFont('Arial', '', 10);
        $rowHeight = 8;
        $labelWidth = 70;
        
        $yBefore = $this->GetY();
        $summaryBoxHeight = count($summaryData) * $rowHeight;
        $this->Rect(10, $yBefore, 277, $summaryBoxHeight, 'F');
        $this->SetY($yBefore);

        foreach($summaryData as $label => $value) {
            $this->SetX(10);
            $this->SetFont('Arial', 'B');
            $this->Cell($labelWidth, $rowHeight, ' ' . $label, 0, 0, 'L');
            $this->SetFont('Arial', '');
            $this->Cell(277 - $labelWidth, $rowHeight, $value . ' ', 0, 1, 'R');
        }
        $this->Ln(8);

        $this->SetFillColor($this->sectionHeaderColor[0], $this->sectionHeaderColor[1], $this->sectionHeaderColor[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(0, 0, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->SetLineWidth(0.2);
        
        $colWidths = [87, 40, 40, 40, 40, 30];
        $this->Cell($colWidths[0], 8, 'Employee Name', 1, 0, 'C', true);
        $this->Cell($colWidths[1], 8, 'SSNIT Number', 1, 0, 'C', true);
        $this->Cell($colWidths[2], 8, 'Basic Salary (' . $this->currencySymbol . ')', 1, 0, 'C', true);
        $this->Cell($colWidths[3], 8, 'Employee SSNIT (' . $this->currencySymbol . ')', 1, 0, 'C', true);
        $this->Cell($colWidths[4], 8, 'Employer SSNIT (' . $this->currencySymbol . ')', 1, 0, 'C', true);
        $this->Cell($colWidths[5], 8, 'Total SSNIT (' . $this->currencySymbol . ')', 1, 1, 'C', true);

        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0);
        $this->SetDrawColor($this->borderColor[0], $this->borderColor[1], $this->borderColor[2]);
        $rowHeight = 7;
        $alternateRow = false;
        
        foreach ($this->reportData as $row) {
            if ($this->GetY() + $rowHeight > $this->PageBreakTrigger) {
                $this->AddPage('L', 'A4');
                $this->SetMargins(10, 15, 10);
                $this->SetFillColor($this->sectionHeaderColor[0], $this->sectionHeaderColor[1], $this->sectionHeaderColor[2]);
                $this->SetTextColor(255, 255, 255);
                $this->SetDrawColor(0, 0, 0);
                $this->SetFont('Arial', 'B', 8);
                $this->Cell($colWidths[0], 8, 'Employee Name', 1, 0, 'C', true);
                $this->Cell($colWidths[1], 8, 'SSNIT Number', 1, 0, 'C', true);
                $this->Cell($colWidths[2], 8, 'Basic Salary (' . $this->currencySymbol . ')', 1, 0, 'C', true);
                $this->Cell($colWidths[3], 8, 'Employee SSNIT (' . $this->currencySymbol . ')', 1, 0, 'C', true);
                $this->Cell($colWidths[4], 8, 'Employer SSNIT (' . $this->currencySymbol . ')', 1, 0, 'C', true);
                $this->Cell($colWidths[5], 8, 'Total SSNIT (' . $this->currencySymbol . ')', 1, 1, 'C', true);
                $this->SetFont('Arial', '', 8);
                $this->SetTextColor(0);
                $this->SetDrawColor($this->borderColor[0], $this->borderColor[1], $this->borderColor[2]);
            }

            $fill = $alternateRow;
            $this->SetFillColor($fill ? $this->alternateRowColor[0] : 255, $fill ? $this->alternateRowColor[1] : 255, $fill ? $this->alternateRowColor[2] : 255);
            
            $this->Cell($colWidths[0], $rowHeight, ' ' . ($row['employee_name'] ?? ''), 1, 0, 'L', true);
            $this->Cell($colWidths[1], $rowHeight, ($row['ssnit_number'] ?? ''), 1, 0, 'C', true);
            $this->Cell($colWidths[2], $rowHeight, number_format((float)($row['basic_salary'] ?? 0), 2) . ' ', 1, 0, 'R', true);
            $this->Cell($colWidths[3], $rowHeight, number_format((float)($row['employee_ssnit'] ?? 0), 2) . ' ', 1, 0, 'R', true);
            $this->Cell($colWidths[4], $rowHeight, number_format((float)($row['employer_ssnit'] ?? 0), 2) . ' ', 1, 0, 'R', true);
            $this->Cell($colWidths[5], $rowHeight, number_format((float)($row['total_ssnit'] ?? 0), 2) . ' ', 1, 1, 'R', true);
            
            $alternateRow = !$alternateRow;
        }

        if ($this->GetY() + 8 > $this->PageBreakTrigger) {
            $this->AddPage('L', 'A4');
            $this->SetMargins(10, 15, 10);
        }

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor($this->sectionHeaderColor[0], $this->sectionHeaderColor[1], $this->sectionHeaderColor[2]);
        $this->SetTextColor(255);
        $this->SetDrawColor(0);
        $this->Cell($colWidths[0] + $colWidths[1], 8, 'TOTALS', 1, 0, 'C', true);
        $this->Cell($colWidths[2], 8, $this->currencySymbol . ' ' . number_format($totalBasicSalary, 2), 1, 0, 'R', true);
        $this->Cell($colWidths[3], 8, $this->currencySymbol . ' ' . number_format($totalEmployeeSsnit, 2), 1, 0, 'R', true);
        $this->Cell($colWidths[4], 8, $this->currencySymbol . ' ' . number_format($totalEmployerSsnit, 2), 1, 0, 'R', true);
        $this->Cell($colWidths[5], 8, $this->currencySymbol . ' ' . number_format($totalOverallSsnit, 2), 1, 1, 'R', true);

        return $this->Output('S');
    }
}