<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

use FPDF;

class ActivityLogPdfGenerator extends FPDF
{
    private array $activities;
    private bool $isSuperAdminView;

    public function __construct(array $activities, bool $isSuperAdminView)
    {
        parent::__construct('L', 'mm', 'A4'); // Landscape mode to fit more columns
        $this->activities = $activities;
        $this->isSuperAdminView = $isSuperAdminView;
    }

    public function Header()
    {
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, 'Activity Log Report', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $this->Ln(10);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    public function generate(): void
    {
        $this->AddPage();
        $this->AliasNbPages();
        $this->SetFont('Arial', '', 10);

        // Table Header
        $this->SetFillColor(230, 230, 230);
        $this->SetFont('Arial', 'B', 10);

        $this->Cell(35, 7, 'Date & Time', 1, 0, 'C', true);
        if ($this->isSuperAdminView) {
            $this->Cell(40, 7, 'Tenant', 1, 0, 'C', true);
        }
        $this->Cell(40, 7, 'User', 1, 0, 'C', true);
        $this->Cell($this->isSuperAdminView ? 162 : 202, 7, 'Description', 1, 1, 'C', true);

        // Table Body
        $this->SetFont('Arial', '', 9);
        foreach ($this->activities as $activity) {
            // --- Calculate Row Height ---
            $description = $this->formatDescription($activity);
            $descriptionWidth = $this->isSuperAdminView ? 162 : 202;
            $nb = $this->NbLines($descriptionWidth, $description);
            $rowHeight = $nb * 6; // 6mm is the line height
            if ($rowHeight < 7) $rowHeight = 7; // Ensure a minimum height

            // --- Check for Page Break ---
            if ($this->GetY() + $rowHeight > $this->PageBreakTrigger) {
                $this->AddPage($this->CurOrientation);
            }

            // --- Draw Cells Sequentially ---
            $x = $this->GetX();
            $y = $this->GetY();

            // Date & Time
            $this->MultiCell(35, $rowHeight, date('Y-m-d H:i', strtotime($activity['created_at'])), 1, 'L');
            $this->SetXY($x + 35, $y);

            // Tenant (if applicable)
            if ($this->isSuperAdminView) {
                $this->MultiCell(40, $rowHeight, $activity['tenant_name'] ?? 'N/A', 1, 'L');
                $this->SetXY($x + 75, $y);
            }

            // User
            $userX = $this->isSuperAdminView ? $x + 75 : $x + 35;
            $this->SetXY($userX, $y);
            $this->MultiCell(40, $rowHeight, ($activity['first_name'] ?? 'System') . ' ' . ($activity['last_name'] ?? ''), 1, 'L');
            
            // Description
            $descX = $this->isSuperAdminView ? $x + 115 : $x + 75;
            $this->SetXY($descX, $y);
            $this->MultiCell($descriptionWidth, $rowHeight, $description, 1, 'L');
        }

        $filename = "activity_log_" . date('Y-m-d') . ".pdf";
        $this->Output('D', $filename);
        exit();
    }

    // Helper function to calculate number of lines a MultiCell will take
    private function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb == 0)
            return 1;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j)
                        $i++;
                } else
                    $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else
                $i++;
        }
        return $nl;
    }

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
            $description .= "\n(" . implode(', ', $detailParts) . ")";
        }

        return $description;
    }
}
