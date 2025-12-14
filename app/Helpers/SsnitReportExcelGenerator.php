<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

class SsnitReportExcelGenerator
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
        $sheet->setTitle('SSNIT Report');

        // --- Styles ---
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1665D8']], // Primary blue
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => Color::COLOR_BLACK]]],
        ];

        $subHeaderStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEBEBEB']], // Light grey
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ];
        
        $tableHeaderStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9E1F2']], // Light blue/grey
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => Color::COLOR_BLACK]]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];

        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => Color::COLOR_BLACK]]],
        ];

        $currencySymbol = 'GHS';
        $currencyStyle = [
            'numberFormat' => ['formatCode' => $currencySymbol . ' #,##0.00'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ];

        $rightAlignStyle = [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ];

        // --- Report Header ---
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', strtoupper($this->tenantData['legal_name']));
        $sheet->getStyle('A1')->applyFromArray($headerStyle);
        $sheet->getStyle('A1')->getFont()->setSize(16);
        $sheet->getRowDimension(1)->setRowHeight(25);

        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', 'SSNIT Report for Payroll Period: ' . $this->payrollPeriodData['period_name']);
        $sheet->getStyle('A2')->applyFromArray($headerStyle);
        $sheet->getStyle('A2')->getFont()->setSize(12);
        $sheet->getRowDimension(2)->setRowHeight(20);
        
        $sheet->mergeCells('A3:F3');
        $sheet->setCellValue('A3', 'TIN: ' . ($this->tenantData['tin'] ?? 'N/A') . ' | Generated: ' . date('Y-m-d H:i'));
        $sheet->getStyle('A3')->applyFromArray($headerStyle);
        $sheet->getStyle('A3')->getFont()->setSize(10);
        $sheet->getRowDimension(3)->setRowHeight(15);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Align date to right

        $currentRow = 5; // Start data after header
        
        // --- Summary Section ---
        $totalBasicSalary = 0.0;
        $totalEmployeeSsnit = 0.0;
        $totalEmployerSsnit = 0.0;
        $totalOverallSsnit = 0.0;
        $employeeCount = count($this->reportData);

        foreach ($this->reportData as $data) {
            $totalBasicSalary += (float)$data['basic_salary'];
            $totalEmployeeSsnit += (float)$data['employee_ssnit'];
            $totalEmployerSsnit += (float)$data['employer_ssnit'];
            $totalOverallSsnit += (float)$data['total_ssnit'];
        }

        $sheet->setCellValue('A' . $currentRow, 'Report Summary');
        $sheet->getStyle('A' . $currentRow)->applyFromArray($subHeaderStyle);
        $sheet->mergeCells('A' . $currentRow . ':F' . $currentRow);
        $currentRow++;
        
        $sheet->setCellValue('A' . $currentRow, 'Total Employees:');
        $sheet->setCellValue('B' . $currentRow, $employeeCount);
        $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->applyFromArray($dataStyle);
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, 'Total Basic Salary:');
        $sheet->setCellValue('B' . $currentRow, $totalBasicSalary);
        $sheet->getStyle('B' . $currentRow)->applyFromArray($currencyStyle);
        $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->applyFromArray($dataStyle);
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, 'Total Employee SSNIT:');
        $sheet->setCellValue('B' . $currentRow, $totalEmployeeSsnit);
        $sheet->getStyle('B' . $currentRow)->applyFromArray($currencyStyle);
        $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->applyFromArray($dataStyle);
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, 'Total Employer SSNIT:');
        $sheet->setCellValue('B' . $currentRow, $totalEmployerSsnit);
        $sheet->getStyle('B' . $currentRow)->applyFromArray($currencyStyle);
        $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->applyFromArray($dataStyle);
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, 'Total SSNIT Due:');
        $sheet->setCellValue('B' . $currentRow, $totalOverallSsnit);
        $sheet->getStyle('B' . $currentRow)->applyFromArray($currencyStyle);
        $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->applyFromArray($dataStyle);
        $currentRow += 2; // Add some space

        // --- Main Data Table ---
        $startDataTable = $currentRow;

        // Table Headers
        $sheet->setCellValue('A' . $currentRow, 'Employee Name');
        $sheet->setCellValue('B' . $currentRow, 'SSNIT Number');
        $sheet->setCellValue('C' . $currentRow, 'Basic Salary');
        $sheet->setCellValue('D' . $currentRow, 'Employee SSNIT (5.5%)');
        $sheet->setCellValue('E' . $currentRow, 'Employer SSNIT (13%)');
        $sheet->setCellValue('F' . $currentRow, 'Total SSNIT (18.5%)');
        $sheet->getStyle('A' . $currentRow . ':F' . $currentRow)->applyFromArray($tableHeaderStyle);
        $currentRow++;

        // Table Body
        $rowCounter = 0;
        foreach ($this->reportData as $data) {
            $sheet->setCellValue('A' . $currentRow, $data['employee_name']);
            $sheet->setCellValue('B' . $currentRow, $data['ssnit_number']);
            $sheet->setCellValue('C' . $currentRow, (float)$data['basic_salary']);
            $sheet->setCellValue('D' . $currentRow, (float)$data['employee_ssnit']);
            $sheet->setCellValue('E' . $currentRow, (float)$data['employer_ssnit']);
            $sheet->setCellValue('F' . $currentRow, (float)$data['total_ssnit']);

            // Apply data styles
            $sheet->getStyle('A' . $currentRow . ':F' . $currentRow)->applyFromArray($dataStyle);
            $sheet->getStyle('C' . $currentRow . ':F' . $currentRow)->applyFromArray($currencyStyle);

            // Alternating row color
            if ($rowCounter % 2 == 1) {
                $sheet->getStyle('A' . $currentRow . ':F' . $currentRow)->getFill()
                      ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                      ->setStartColor(new Color('FFF0F0F0')); // Lighter grey
            }
            $rowCounter++;
            $currentRow++;
        }

        // Total Row
        $sheet->setCellValue('A' . $currentRow, 'TOTALS');
        $sheet->mergeCells('A' . $currentRow . ':B' . $currentRow);
        $sheet->setCellValue('C' . $currentRow, $totalBasicSalary);
        $sheet->setCellValue('D' . $currentRow, $totalEmployeeSsnit);
        $sheet->setCellValue('E' . $currentRow, $totalEmployerSsnit);
        $sheet->setCellValue('F' . $currentRow, $totalOverallSsnit);
        $sheet->getStyle('A' . $currentRow . ':F' . $currentRow)->applyFromArray($tableHeaderStyle);
        $sheet->getStyle('C' . $currentRow . ':F' . $currentRow)->applyFromArray($currencyStyle);
        
        $currentRow++; // For spacing

        // Auto size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create a writer
        $writer = new Xlsx($spreadsheet);

        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'ssnit_excel_report_');
        $writer->save($tempFile);

        // Return the path to the temporary file
        return $tempFile;
    }
}