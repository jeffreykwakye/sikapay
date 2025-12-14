<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use Jeffrey\Sikapay\Core\Log;
use \Exception;

class SsnitReportCsvGenerator
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
        try {
            $stream = fopen('php://memory', 'w');

            // Header Section
            fputcsv($stream, ['SSNIT Report']);
            fputcsv($stream, [$this->tenantData['legal_name']]);
            fputcsv($stream, ['Period: ' . $this->payrollPeriodData['period_name']]);
            fputcsv($stream, ['Generated on: ' . date('Y-m-d H:i:s')]);
            fputcsv($stream, []); // Blank line

            // Summary Section
            $totalBasicSalary = array_sum(array_column($this->reportData, 'basic_salary'));
            $totalEmployeeSsnit = array_sum(array_column($this->reportData, 'employee_ssnit'));
            $totalEmployerSsnit = array_sum(array_column($this->reportData, 'employer_ssnit'));
            $totalOverallSsnit = array_sum(array_column($this->reportData, 'total_ssnit'));
            $employeeCount = count($this->reportData);

            fputcsv($stream, ['Summary']);
            fputcsv($stream, ['Total Employees', $employeeCount]);
            fputcsv($stream, ['Total Basic Salary (GHS)', number_format($totalBasicSalary, 2)]);
            fputcsv($stream, ['Total Employee SSNIT (GHS)', number_format($totalEmployeeSsnit, 2)]);
            fputcsv($stream, ['Total Employer SSNIT (GHS)', number_format($totalEmployerSsnit, 2)]);
            fputcsv($stream, ['Total SSNIT Due (GHS)', number_format($totalOverallSsnit, 2)]);
            fputcsv($stream, []); // Blank line

            // Data Table Header
            fputcsv($stream, [
                'Employee Name',
                'SSNIT Number',
                'Basic Salary (GHS)',
                'Employee SSNIT (GHS)',
                'Employer SSNIT (GHS)',
                'Total SSNIT (GHS)'
            ]);

            // Data Table Body
            foreach ($this->reportData as $row) {
                fputcsv($stream, [
                    $row['employee_name'],
                    "'" . $row['ssnit_number'],
                    number_format((float)$row['basic_salary'], 2),
                    number_format((float)$row['employee_ssnit'], 2),
                    number_format((float)$row['employer_ssnit'], 2),
                    number_format((float)$row['total_ssnit'], 2)
                ]);
            }

            // Total Row
            fputcsv($stream, [
                'TOTALS',
                '',
                number_format($totalBasicSalary, 2),
                number_format($totalEmployeeSsnit, 2),
                number_format($totalEmployerSsnit, 2),
                number_format($totalOverallSsnit, 2)
            ]);

            rewind($stream);
            $csvContent = stream_get_contents($stream);
            fclose($stream);

            return $csvContent;
        } catch (Exception $e) {
            Log::error("Failed to generate SSNIT CSV report. Error: " . $e->getMessage());
            return ''; // Return empty string on failure
        }
    }
}
