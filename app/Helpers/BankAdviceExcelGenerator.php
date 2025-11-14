<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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

        // Title
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
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(3)->setRowHeight(18);


        // Set headers
        $sheet->setCellValue('A5', 'Employee Name');
        $sheet->setCellValue('B5', 'Bank');
        $sheet->setCellValue('C5', 'Branch');
        $sheet->setCellValue('D5', 'Account Number');
        $sheet->setCellValue('E5', 'Account Name');
        $sheet->setCellValue('F5', 'Net Salary');

        // Bold headers
        $sheet->getStyle('A5:F5')->getFont()->setBold(true);

        // Set data
        $row = 6;
        $totalNetPay = 0;
        foreach ($this->reportData as $data) {
            $sheet->setCellValue('A' . $row, $data['employee_name']);
            $sheet->setCellValue('B' . $row, $data['bank_name']);
            $sheet->setCellValue('C' . $row, $data['bank_branch']);
            $sheet->setCellValue('D' . $row, $data['bank_account_number']);
            $sheet->setCellValue('E' . $row, $data['bank_account_name']);
            $sheet->setCellValue('F' . $row, number_format((float)$data['net_pay'], 2));
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $totalNetPay += (float)$data['net_pay'];
            $row++;
        }

        // Total
        $sheet->setCellValue('E' . $row, 'Total');
        $sheet->setCellValue('F' . $row, number_format($totalNetPay, 2));
        $sheet->getStyle('E' . $row . ':F' . $row)->getFont()->setBold(true);
        $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);


        // Auto size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create a writer
        $writer = new Xlsx($spreadsheet);

        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'bank_advice_');
        $writer->save($tempFile);

        // Return the path to the temporary file
        return $tempFile;
    }
}
