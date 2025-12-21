<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use FPDF;
use Jeffrey\Sikapay\Core\Log;
use NumberToWords\NumberToWords;

class BankAdvicePdfGenerator extends FPDF
{
    private array $reportData;
    private array $tenantData;
    private array $payrollPeriodData;
    private string $currencySymbol = 'GHS';
    private bool $isCoverLetter = false;
    private bool $includeCoverLetter;
    private ?array $departmentData;

    // New professional color scheme
    private array $documentHeaderColor = [51, 51, 51];
    private array $sectionHeaderColor = [0, 128, 128];
    private array $summaryFillColor = [245, 245, 245];
    private array $borderColor = [200, 200, 200];
    private array $alternateRowColor = [242, 242, 242];

    public function __construct(array $reportData, array $tenantData, array $payrollPeriodData, bool $includeCoverLetter = false, ?array $department = null)
    {
        parent::__construct('L', 'mm', 'A4');
        $this->reportData = $reportData;
        $this->tenantData = $tenantData;
        $this->payrollPeriodData = $payrollPeriodData;
        $this->includeCoverLetter = $includeCoverLetter;
        $this->departmentData = $department;
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

        $year = date('Y', strtotime($this->payrollPeriodData['start_date']));
        $title = $year . ' BANK ADVICE REPORT'; // Default title
        if ($this->departmentData) {
            $title = $year . ' BANK ADVICE FOR ' . strtoupper($this->departmentData['name']);
        }

        $this->Cell(90, 7, $title, 0, 1, 'R');
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
        $this->Cell(0, 5, 'Page ' . ($this->includeCoverLetter ? $this->PageNo() - 1 : $this->PageNo()) . '/{nb}', 0, 0, 'C');
    }

    private function generateCoverLetterContent()
    {
        $totalNetPay = array_sum(array_column($this->reportData, 'net_pay'));
        
        $numberToWords = new NumberToWords();
        $numberTransformer = $numberToWords->getNumberTransformer('en');
        $cedis = (int) $totalNetPay;
        $pesewas = (int) round(($totalNetPay - $cedis) * 100);
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
        $recipientName = $this->tenantData['bank_advice_recipient_name'] ?? 'The Manager';
        $this->Cell(0, 6, $recipientName, 0, 1, 'L');
        if (!empty($this->tenantData['bank_name'])) {
            $this->Cell(0, 6, $this->tenantData['bank_name'], 0, 1, 'L');
            if (!empty($this->tenantData['bank_branch'])) {
                $this->Cell(0, 6, $this->tenantData['bank_branch'], 0, 1, 'L');
            }
            if (!empty($this->tenantData['bank_address'])) {
                $this->MultiCell(0, 6, $this->tenantData['bank_address'], 0, 'L');
            }
        } else {
            $this->Cell(0, 6, '[Your Bank Name]', 0, 1, 'L');
            $this->Cell(0, 6, '[Your Bank Branch Address]', 0, 1, 'L');
        }
        $this->Ln(10);
        $this->Cell(0, 6, date('F jS, Y'), 0, 1, 'L');
        $this->Ln(15);

        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, 'RE: SALARY PAYMENT ADVICE FOR ' . strtoupper($this->payrollPeriodData['period_name']), 0, 1, 'L');
        $this->Ln(10);

        $this->SetFont('Arial', '', 11);
        $this->MultiCell(0, 6, 'Dear Sir/Madam,', 0, 'L');
        $this->Ln(6);

        $body = sprintf(
            "Please find attached the salary payment advice for %s for the payroll period of %s. " .
            "We kindly request that you credit the accounts of our employees as listed. " .
            "The total amount to be disbursed is %s (%s %s).",
            $this->tenantData['legal_name'],
            strtoupper($this->payrollPeriodData['period_name']),
            $amountInWords,
            $this->currencySymbol,
            number_format($totalNetPay, 2)
        );

        $this->MultiCell(0, 6, $body, 0, 'L');
        $this->Ln(6);
        $this->MultiCell(0, 6, 'We have credited your corporate account with the total amount. Thank you for your prompt cooperation.', 0, 'L');
        $this->Ln(15);

        $this->Cell(0, 6, 'Sincerely,', 0, 1, 'L');
        $this->Ln(25);

        $this->Cell(80, 0, '', 'T', 1, 'L');
        $this->Ln(1);
        $this->SetFont('Arial', '', 10);
        $authorizedSignatory = $this->tenantData['authorized_signatory_name'] ?? 'Authorised Signatory';
        $this->Cell(80, 6, '(' . $authorizedSignatory . ')', 0, 1, 'L');
        $authorizedSignatoryTitle = $this->tenantData['authorized_signatory_title'] ?? 'Management';
        if (!empty($authorizedSignatoryTitle)) {
            $this->Cell(80, 6, $authorizedSignatoryTitle, 0, 1, 'L');
        }
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(80, 6, $this->tenantData['legal_name'], 0, 1, 'L');
    }

    public function generate(): string
    {
        if ($this->includeCoverLetter) {
            $this->isCoverLetter = true;
            $this->AddPage('P', 'A4');
            $this->generateCoverLetterContent();
            $this->isCoverLetter = false;
        }

        $this->AddPage('L', 'A4');
        $this->SetMargins(10, 15, 10);
        $this->AliasNbPages();

        $totalNetPay = array_sum(array_column($this->reportData, 'net_pay'));
        $employeeCount = count($this->reportData);

        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0);
        $this->Cell(0, 8, 'Report Summary for ' . strtoupper($this->payrollPeriodData['period_name']), 0, 1, 'L');
        $this->Ln(1);

        $summaryData = [
            'Payroll Period' => strtoupper($this->payrollPeriodData['period_name']),
            'Total Employees' => $employeeCount,
            'Total Net Pay' => $this->currencySymbol . ' ' . number_format($totalNetPay, 2),
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
        $this->SetFont('Arial', 'B', 9);
        $this->SetLineWidth(0.2);
        
        $colWidths = [45, 45, 45, 50, 50, 42];
        $this->Cell($colWidths[0], 8, 'Employee Name', 1, 0, 'C', true);
        $this->Cell($colWidths[1], 8, 'Bank', 1, 0, 'C', true);
        $this->Cell($colWidths[2], 8, 'Branch', 1, 0, 'C', true);
        $this->Cell($colWidths[3], 8, 'Account Number', 1, 0, 'C', true);
        $this->Cell($colWidths[4], 8, 'Account Name', 1, 0, 'C', true);
        $this->Cell($colWidths[5], 8, 'Net Salary (' . $this->currencySymbol . ')', 1, 1, 'C', true);

        $this->SetFont('Arial', '', 9);
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
                $this->SetFont('Arial', 'B', 9);
                $this->Cell($colWidths[0], 8, 'Employee Name', 1, 0, 'C', true);
                $this->Cell($colWidths[1], 8, 'Bank', 1, 0, 'C', true);
                $this->Cell($colWidths[2], 8, 'Branch', 1, 0, 'C', true);
                $this->Cell($colWidths[3], 8, 'Account Number', 1, 0, 'C', true);
                $this->Cell($colWidths[4], 8, 'Account Name', 1, 0, 'C', true);
                $this->Cell($colWidths[5], 8, 'Net Salary (' . $this->currencySymbol . ')', 1, 1, 'C', true);
                $this->SetFont('Arial', '', 9);
                $this->SetTextColor(0);
                $this->SetDrawColor($this->borderColor[0], $this->borderColor[1], $this->borderColor[2]);
            }

            $fill = $alternateRow;
            $this->SetFillColor($fill ? $this->alternateRowColor[0] : 255, $fill ? $this->alternateRowColor[1] : 255, $fill ? $this->alternateRowColor[2] : 255);
            
            $this->Cell($colWidths[0], $rowHeight, ' ' . ($row['employee_name'] ?? ''), 1, 0, 'L', true);
            $this->Cell($colWidths[1], $rowHeight, ' ' . ($row['bank_name'] ?? ''), 1, 0, 'L', true);
            $this->Cell($colWidths[2], $rowHeight, ' ' . ($row['bank_branch'] ?? ''), 1, 0, 'L', true);
            $this->Cell($colWidths[3], $rowHeight, ' ' . ($row['bank_account_number'] ?? ''), 1, 0, 'L', true);
            $this->Cell($colWidths[4], $rowHeight, ' ' . ($row['bank_account_name'] ?? ''), 1, 0, 'L', true);
            $this->Cell($colWidths[5], $rowHeight, number_format((float)($row['net_pay'] ?? 0), 2) . ' ', 1, 1, 'R', true);
            
            $alternateRow = !$alternateRow;
        }

        if ($this->GetY() + 8 > $this->PageBreakTrigger) {
            $this->AddPage('L', 'A4');
            $this->SetMargins(10, 15, 10);
        }

        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor($this->sectionHeaderColor[0], $this->sectionHeaderColor[1], $this->sectionHeaderColor[2]);
        $this->SetTextColor(255);
        $this->SetDrawColor(0);
        $this->Cell(array_sum(array_slice($colWidths, 0, 5)), 8, 'TOTAL NET PAY', 1, 0, 'C', true);
        $this->Cell($colWidths[5], 8, $this->currencySymbol . ' ' . number_format($totalNetPay, 2), 1, 1, 'R', true);

        return $this->Output('S');
    }
}
