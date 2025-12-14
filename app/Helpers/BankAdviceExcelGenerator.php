<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Jeffrey\Sikapay\Core\Log;

class BankAdviceExcelGenerator
{
    private array $reportData;
    private array $tenantData;
    private array $payrollPeriodData;

    public function __construct(array $reportData, array $tenantData, array $payrollPeriodData)
    {
        $this->reportData = $reportData;
        $this->tenantData = $tenantData;
        $this->payrollPeriodData = $payrollPeriodData;
    }

    public function generate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bank Advice');

        // --- Calculate totals first for summary ---
        $totalNetPay = array_sum(array_column($this->reportData, 'net_pay'));
        $employeeCount = count($this->reportData);

        // --- Header ---
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'Bank Advice Report');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', $this->tenantData['legal_name']);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A3:F3');
        $sheet->setCellValue('A3', 'For Payroll Period: ' . $this->payrollPeriodData['period_name']);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $sheet->mergeCells('A4:F4');
        $sheet->setCellValue('A4', 'Generated on: ' . date('Y-m-d H:i:s'));
        $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // --- Summary Section ---
        $summaryRow = 6;
        $sheet->setCellValue('A' . $summaryRow, 'Report Summary');
        $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
        $sheet->mergeCells('A' . $summaryRow . ':B' . $summaryRow);
        $summaryRow++;

        $sheet->setCellValue('A' . $summaryRow, 'Total Employees:');
        $sheet->setCellValue('B' . $summaryRow, $employeeCount);
        $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
        $summaryRow++;
        
        $sheet->setCellValue('A' . $summaryRow, 'Total Net Pay:');
        $sheet->setCellValue('B' . $summaryRow, number_format($totalNetPay, 2));
        $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
        $sheet->getStyle('B' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // --- Main Data Table Headers ---
        $headerRow = $summaryRow + 2;
        $sheet->setCellValue('A' . $headerRow, 'Employee Name');
        $sheet->setCellValue('B' . $headerRow, 'Bank');
        $sheet->setCellValue('C' . $headerRow, 'Branch');
        $sheet->setCellValue('D' . $headerRow, 'Account Number');
        $sheet->setCellValue('E' . $headerRow, 'Account Name');
        $sheet->setCellValue('F' . $headerRow, 'Net Salary (GHS)');
        $sheet->getStyle('A' . $headerRow . ':F' . $headerRow)->getFont()->setBold(true);
        $sheet->getStyle('F' . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


        // --- Main Data Table Body ---
        $currentRow = $headerRow + 1;
        foreach ($this->reportData as $data) {
            $sheet->setCellValue('A' . $currentRow, $data['employee_name']);
            $sheet->setCellValue('B' . $currentRow, $data['bank_name']);
            $sheet->setCellValue('C' . $currentRow, $data['bank_branch']);
            $sheet->setCellValueExplicit('D' . $currentRow, $data['bank_account_number'], DataType::TYPE_STRING);
            $sheet->setCellValue('E' . $currentRow, $data['bank_account_name']);
            $sheet->setCellValue('F' . $currentRow, number_format((float)$data['net_pay'], 2));
            $sheet->getStyle('F' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $currentRow++;
        }

        // --- Total Row ---
        $currentRow++; // Add a little space
        $sheet->setCellValue('E' . $currentRow, 'TOTAL');
        $sheet->setCellValue('F' . $currentRow, number_format($totalNetPay, 2));
        $sheet->getStyle('E' . $currentRow . ':F' . $currentRow)->getFont()->setBold(true);
        $sheet->getStyle('F' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Auto size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create a writer
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'bank_advice_');
        $writer->save($tempFile);

        return $tempFile;
    }
}
