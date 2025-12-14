<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use Jeffrey\Sikapay\Core\Log;
use \Exception;

class PayeReportCsvGenerator
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
            fputcsv($stream, ['PAYE Report']);
            fputcsv($stream, [$this->tenantData['legal_name']]);
            fputcsv($stream, ['Period: ' . $this->payrollPeriodData['period_name']]);
            fputcsv($stream, ['Generated on: ' . date('Y-m-d H:i:s')]);
            fputcsv($stream, []); // Blank line

            // Summary Section
            $totalPaye = array_sum(array_column($this->reportData, 'paye_amount'));
            $totalTaxableIncome = array_sum(array_column($this->reportData, 'taxable_income'));
            $employeeCount = count($this->reportData);

            fputcsv($stream, ['Summary']);
            fputcsv($stream, ['Total Employees', $employeeCount]);
            fputcsv($stream, ['Total Taxable Income (GHS)', number_format($totalTaxableIncome, 2)]);
            fputcsv($stream, ['Total PAYE Due (GHS)', number_format($totalPaye, 2)]);
            fputcsv($stream, []); // Blank line

            // Data Table Header
            fputcsv($stream, [
                'Employee Name',
                'TIN Number',
                'Taxable Income (GHS)',
                'PAYE (GHS)'
            ]);

            // Data Table Body
            foreach ($this->reportData as $row) {
                fputcsv($stream, [
                    $row['employee_name'],
                    "'" . $row['tin_number'],
                    number_format((float)$row['taxable_income'], 2),
                    number_format((float)$row['paye_amount'], 2)
                ]);
            }

            // Total Row
            fputcsv($stream, [
                'TOTALS',
                '',
                number_format($totalTaxableIncome, 2),
                number_format($totalPaye, 2)
            ]);

            rewind($stream);
            $csvContent = stream_get_contents($stream);
            fclose($stream);

            return $csvContent;
        } catch (Exception $e) {
            Log::error("Failed to generate PAYE CSV report. Error: " . $e->getMessage());
            return ''; // Return empty string on failure
        }
    }
}
