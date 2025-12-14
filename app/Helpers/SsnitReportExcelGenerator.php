<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataType; // Added
use Jeffrey\Sikapay\Core\Log;

class SsnitReportExcelGenerator
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
            $sheet->setTitle('SSNIT Report');

            // --- Calculate totals first for summary ---
            $totalBasicSalary = array_sum(array_column($this->reportData, 'basic_salary'));
            $totalEmployeeSsnit = array_sum(array_column($this->reportData, 'employee_ssnit'));
            $totalEmployerSsnit = array_sum(array_column($this->reportData, 'employer_ssnit'));
            $totalOverallSsnit = array_sum(array_column($this->reportData, 'total_ssnit'));
            $employeeCount = count($this->reportData);

            // --- Simplified Header ---
            $sheet->mergeCells('A1:F1');
            $sheet->setCellValue('A1', 'SSNIT Report');
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
            
            $sheet->setCellValue('A' . $summaryRow, 'Total Basic Salary:');
            $sheet->setCellValue('B' . $summaryRow, number_format($totalBasicSalary, 2));
            $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
            $sheet->getStyle('B' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $summaryRow++;
            
            $sheet->setCellValue('A' . $summaryRow, 'Total Employee SSNIT:');
            $sheet->setCellValue('B' . $summaryRow, number_format($totalEmployeeSsnit, 2));
            $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
            $sheet->getStyle('B' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $summaryRow++;
            
            $sheet->setCellValue('A' . $summaryRow, 'Total Employer SSNIT:');
            $sheet->setCellValue('B' . $summaryRow, number_format($totalEmployerSsnit, 2));
            $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
            $sheet->getStyle('B' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $summaryRow++;
            
            $sheet->setCellValue('A' . $summaryRow, 'Total SSNIT Due:');
            $sheet->setCellValue('B' . $summaryRow, number_format($totalOverallSsnit, 2));
            $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
            $sheet->getStyle('B' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // --- Main Data Table Headers ---
            $headerRow = $summaryRow + 2;
            $sheet->setCellValue('A' . $headerRow, 'Employee Name');
            $sheet->setCellValue('B' . $headerRow, 'SSNIT Number');
            $sheet->setCellValue('C' . $headerRow, 'Basic Salary (' . $this->currencySymbol . ')');
            $sheet->setCellValue('D' . $headerRow, 'Employee SSNIT (' . $this->currencySymbol . ')');
            $sheet->setCellValue('E' . $headerRow, 'Employer SSNIT (' . $this->currencySymbol . ')');
            $sheet->setCellValue('F' . $headerRow, 'Total SSNIT (' . $this->currencySymbol . ')');
            $sheet->getStyle('A' . $headerRow . ':F' . $headerRow)->getFont()->setBold(true);
            $sheet->getStyle('A' . $headerRow . ':F' . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // --- Main Data Table Body ---
            $currentRow = $headerRow + 1;
            foreach ($this->reportData as $data) {
                $sheet->setCellValue('A' . $currentRow, $data['employee_name']);
                $sheet->setCellValueExplicit('B' . $currentRow, $data['ssnit_number'], DataType::TYPE_STRING);
                $sheet->setCellValue('C' . $currentRow, number_format((float)$data['basic_salary'], 2));
                $sheet->setCellValue('D' . $currentRow, number_format((float)$data['employee_ssnit'], 2));
                $sheet->setCellValue('E' . $currentRow, number_format((float)$data['employer_ssnit'], 2));
                $sheet->setCellValue('F' . $currentRow, number_format((float)$data['total_ssnit'], 2));

                $sheet->getStyle('C' . $currentRow . ':F' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $currentRow++;
            }

            // --- Total Row ---
            $currentRow++; // Add a little space
            $sheet->setCellValue('B' . $currentRow, 'TOTALS');
            $sheet->setCellValue('C' . $currentRow, number_format($totalBasicSalary, 2));
            $sheet->setCellValue('D' . $currentRow, number_format($totalEmployeeSsnit, 2));
            $sheet->setCellValue('E' . $currentRow, number_format($totalEmployerSsnit, 2));
            $sheet->setCellValue('F' . $currentRow, number_format($totalOverallSsnit, 2));
            $sheet->getStyle('B' . $currentRow . ':F' . $currentRow)->getFont()->setBold(true);
            $sheet->getStyle('C' . $currentRow . ':F' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Auto size columns
            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Create a writer and save to a temporary file
            $writer = new Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'ssnit_excel_');
            $writer->save($tempFile);

            return $tempFile;

        } catch (\Exception $e) {
            Log::critical("Failed to generate SSNIT Excel report: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $emptyFile = tempnam(sys_get_temp_dir(), 'ssnit_excel_error_');
            file_put_contents($emptyFile, "Error generating report.");
            return $emptyFile;
        }
    }
}