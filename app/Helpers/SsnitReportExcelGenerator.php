<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

        // Set headers
        $sheet->setCellValue('A1', 'Employee ID');
        $sheet->setCellValue('B1', 'Employee Name');
        $sheet->setCellValue('C1', 'Gross Salary');
        $sheet->setCellValue('D1', 'Employee SSNIT');
        $sheet->setCellValue('E1', 'Employer SSNIT');

        // Set data
        $row = 2;
        foreach ($this->reportData as $data) {
            $sheet->setCellValue('A' . $row, $data['employee_id']);
            $sheet->setCellValue('B' . $row, $data['employee_name']);
            $sheet->setCellValue('C' . $row, $data['gross_salary']);
            $sheet->setCellValue('D' . $row, $data['employee_ssnit']);
            $sheet->setCellValue('E' . $row, $data['employer_ssnit']);
            $row++;
        }

        // Create a writer
        $writer = new Xlsx($spreadsheet);

        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'ssnit_report_');
        $writer->save($tempFile);

        // Return the path to the temporary file
        return $tempFile;
    }
}
