<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

class ActivityLogCsvGenerator
{
    /**
     * Generates a CSV file from activity log data and streams it to the browser.
     */
    public function generate(): void
    {
        $filename = "activity_log_" . date('Y-m-d') . ".csv";

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Define headers
        $headers = ['Date & Time', 'User', 'Description'];
        if ($this->isSuperAdminView) {
            // Insert 'Tenant' header for super admin view
            array_splice($headers, 1, 0, 'Tenant');
        }
        fputcsv($output, $headers);

        // Write data rows
        foreach ($this->activities as $activity) {
            $row = [
                date('Y-m-d H:i:s', strtotime($activity['created_at'])),
                ($activity['first_name'] ?? 'System') . ' ' . ($activity['last_name'] ?? ''),
                $this->formatDescription($activity),
            ];

            if ($this->isSuperAdminView) {
                array_splice($row, 1, 0, $activity['tenant_name'] ?? 'N/A');
            }

            fputcsv($output, $row);
        }

        fclose($output);
        exit();
    }

    /**
     * Formats the description column by combining the action and details.
     *
     * @param array $activity The activity record.
     * @return string The formatted description.
     */
    private function formatDescription(array $activity): string
    {
        $description = $activity['action_message'];
        $details = json_decode($activity['details_json'] ?? '', true);

        if (is_array($details) && !empty($details)) {
            $detailParts = [];
            foreach ($details as $key => $value) {
                $formattedKey = ucwords(str_replace('_', ' ', $key));
                $formattedValue = is_array($value) ? json_encode($value) : $value;
                $detailParts[] = "{$formattedKey}: {$formattedValue}";
            }
            $description .= ' (' . implode(', ', $detailParts) . ')';
        }

        return $description;
    }
}
