<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PayeReportExcelGenerator
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

        // Title
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
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(3)->setRowHeight(18);

        // Set headers
        $sheet->setCellValue('A5', 'Employee Name');
        $sheet->setCellValue('B5', 'TIN Number');
        $sheet->setCellValue('C5', 'Taxable Income');
        $sheet->setCellValue('D5', 'PAYE');

        // Bold headers
        $sheet->getStyle('A5:D5')->getFont()->setBold(true);

        // Set data
        $row = 6;
        $totalTaxableIncome = 0;
        $totalPaye = 0;
        foreach ($this->reportData as $data) {
            $sheet->setCellValue('A' . $row, $data['employee_name']);
            $sheet->setCellValue('B' . $row, $data['tin_number']);
            $sheet->setCellValue('C' . $row, number_format((float)$data['taxable_income'], 2));
            $sheet->setCellValue('D' . $row, number_format((float)$data['paye_amount'], 2));
            
            $sheet->getStyle('C' . $row . ':D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $totalTaxableIncome += (float)$data['taxable_income'];
            $totalPaye += (float)$data['paye_amount'];
            $row++;
        }

        // Total
        $sheet->setCellValue('B' . $row, 'TOTALS');
        $sheet->setCellValue('C' . $row, number_format($totalTaxableIncome, 2));
        $sheet->setCellValue('D' . $row, number_format($totalPaye, 2));
        
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
        $sheet->getStyle('C' . $row . ':D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Auto size columns
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create a writer
        $writer = new Xlsx($spreadsheet);

        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'paye_report_');
        $writer->save($tempFile);

        // Return the path to the temporary file
        return $tempFile;
    }
}
