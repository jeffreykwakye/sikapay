<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Jeffrey\Sikapay\Core\Log;

class PayeReportExcelGenerator
{
    private array $reportData;
    private array $tenantData;
    private array $payrollPeriodData;
    private string $currencySymbol = 'GHS';

    public function __construct(array $reportData, array $tenantData, array $payrollPeriodData)
    {
        $this->reportData = $reportData;
        $this->tenantData = $tenantData;
        $this->payrollPeriodData = $payrollPeriodData;
    }

    public function generate(): string
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('PAYE Report');

            // --- Calculate totals first for summary ---
            $totalPaye = array_sum(array_column($this->reportData, 'paye_amount'));
            $totalTaxableIncome = array_sum(array_column($this->reportData, 'taxable_income'));
            $employeeCount = count($this->reportData);

            // --- Simplified Header ---
            $sheet->mergeCells('A1:D1');
            $sheet->setCellValue('A1', 'PAYE Report');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('A2:D2');
            $sheet->setCellValue('A2', $this->tenantData['legal_name']);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('A3:D3');
            $sheet->setCellValue('A3', 'For Payroll Period: ' . $this->payrollPeriodData['period_name']);
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension(1)->setRowHeight(22);
            
            $sheet->mergeCells('A4:D4');
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
            
            $sheet->setCellValue('A' . $summaryRow, 'Total Taxable Income:');
            $sheet->setCellValue('B' . $summaryRow, number_format($totalTaxableIncome, 2));
            $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
            $sheet->getStyle('B' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $summaryRow++;

            $sheet->setCellValue('A' . $summaryRow, 'Total PAYE Due:');
            $sheet->setCellValue('B' . $summaryRow, number_format($totalPaye, 2));
            $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
            $sheet->getStyle('B' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // --- Main Data Table Headers ---
            $headerRow = $summaryRow + 2;
            $sheet->setCellValue('A' . $headerRow, 'Employee Name');
            $sheet->setCellValue('B' . $headerRow, 'TIN Number');
            $sheet->setCellValue('C' . $headerRow, 'Taxable Income (' . $this->currencySymbol . ')');
            $sheet->setCellValue('D' . $headerRow, 'PAYE (' . $this->currencySymbol . ')');
            $sheet->getStyle('A' . $headerRow . ':D' . $headerRow)->getFont()->setBold(true);
            $sheet->getStyle('A' . $headerRow . ':D' . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // --- Main Data Table Body ---
            $currentRow = $headerRow + 1;
            foreach ($this->reportData as $data) {
                $sheet->setCellValue('A' . $currentRow, $data['employee_name']);
                $sheet->setCellValueExplicit('B' . $currentRow, $data['tin_number'], DataType::TYPE_STRING);
                $sheet->setCellValue('C' . $currentRow, number_format((float)$data['taxable_income'], 2));
                $sheet->setCellValue('D' . $currentRow, number_format((float)$data['paye_amount'], 2));

                $sheet->getStyle('C' . $currentRow . ':D' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $currentRow++;
            }

            // --- Total Row ---
            $currentRow++; // Add a little space
            $sheet->setCellValue('B' . $currentRow, 'TOTALS');
            $sheet->setCellValue('C' . $currentRow, number_format($totalTaxableIncome, 2));
            $sheet->setCellValue('D' . $currentRow, number_format($totalPaye, 2));
            $sheet->getStyle('B' . $currentRow . ':D' . $currentRow)->getFont()->setBold(true);
            $sheet->getStyle('C' . $currentRow . ':D' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Auto size columns
            foreach (range('A', 'D') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Create a writer and save to a temporary file
            $writer = new Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'paye_excel_');
            $writer->save($tempFile);

            return $tempFile;

        } catch (\Exception $e) {
            Log::critical("Failed to generate PAYE Excel report: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            // Return an empty file path on error
            $emptyFile = tempnam(sys_get_temp_dir(), 'paye_excel_error_');
            file_put_contents($emptyFile, "Error generating report.");
            return $emptyFile;
        }
    }
}
