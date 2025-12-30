<?php

namespace App\Exports;

use App\Models\SamOpportunity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SamOpportunitiesExport implements FromQuery, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStyles
{
    /**
     * Maximum number of rows to export (to prevent memory issues).
     */
    protected const MAX_EXPORT_ROWS = 50000;

    protected int $rowCount = 0;

    public function __construct(
        protected ?User $user = null,
        protected bool $favoritesOnly = false,
        protected array $filters = []
    ) {
    }

    /**
     * Build the query for SAM opportunities export.
     */
    public function query(): Builder
    {
        $query = SamOpportunity::query();

        // Apply favorites filter if requested
        if ($this->favoritesOnly && $this->user) {
            $query->whereHas('favoritedBy', function (Builder $q) {
                $q->where('user_id', $this->user->id);
            });
        }

        // Apply additional filters (can be extended in future)
        if (!empty($this->filters['agency'])) {
            $query->where('agency', $this->filters['agency']);
        }

        if (!empty($this->filters['notice_type'])) {
            $query->where('notice_type', $this->filters['notice_type']);
        }

        if (!empty($this->filters['naics_code'])) {
            $query->where('naics_code', $this->filters['naics_code']);
        }

        // Default sorting: response_deadline ascending, then by posted_date descending
        $query->orderBy('response_deadline', 'asc')
              ->orderBy('posted_date', 'desc');

        // Apply row limit for performance
        $query->limit(self::MAX_EXPORT_ROWS);

        return $query;
    }

    /**
     * Map each row to the export columns.
     */
    public function map($opportunity): array
    {
        return [
            $opportunity->notice_id ?? '',
            $opportunity->title ?? '',
            $opportunity->agency ?? '',
            $opportunity->notice_type ?? '',
            $opportunity->naics_code ?? '',
            $opportunity->psc_code ?? '',
            $opportunity->set_aside ?? '',
            $this->formatDate($opportunity->posted_date),
            $this->formatDate($opportunity->response_deadline),
            $opportunity->place_of_performance ?? '',
            $opportunity->url ?? '',
            $this->computeStatus($opportunity),
        ];
    }

    /**
     * Define column headings.
     */
    public function headings(): array
    {
        return [
            'SAM Notice ID',
            'Title',
            'Agency',
            'Notice Type',
            'NAICS Code',
            'PSC Code',
            'Set Aside',
            'Posted Date',
            'Response Deadline',
            'Place of Performance',
            'SAM.gov URL',
            'Status',
        ];
    }

    /**
     * Apply styles to the worksheet.
     */
    public function styles(Worksheet $sheet): array
    {
        // Freeze the header row
        $sheet->freezePane('A2');

        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
            ],
        ];
    }

    /**
     * Set column widths for better readability.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 20,  // SAM Notice ID
            'B' => 50,  // Title
            'C' => 30,  // Agency
            'D' => 15,  // Notice Type
            'E' => 12,  // NAICS Code
            'F' => 12,  // PSC Code
            'G' => 20,  // Set Aside
            'H' => 15,  // Posted Date
            'I' => 18,  // Response Deadline
            'J' => 25,  // Place of Performance
            'K' => 50,  // SAM.gov URL
            'L' => 12,  // Status
        ];
    }

    /**
     * Format date for Excel display.
     */
    protected function formatDate(?string $date): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Compute the status based on response deadline.
     */
    protected function computeStatus(SamOpportunity $opportunity): string
    {
        if (empty($opportunity->response_deadline)) {
            return 'Unknown';
        }

        try {
            $deadline = Carbon::parse($opportunity->response_deadline);
            $now = Carbon::now();

            if ($deadline->isFuture()) {
                return 'Open';
            }

            return 'Closed';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Register events for additional formatting and metadata.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $this->rowCount = $highestRow - 1; // Subtract header row

                // Add text wrapping to title and description columns
                $sheet->getStyle('B2:B' . $highestRow)->getAlignment()->setWrapText(true);
                $sheet->getStyle('J2:J' . $highestRow)->getAlignment()->setWrapText(true);

                // Center align some columns
                $sheet->getStyle('D2:D' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('E2:E' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F2:F' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('L2:L' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Add metadata footer
                $footerRow = $highestRow + 2;
                $exportTimestamp = Carbon::now('America/Denver')->format('Y-m-d H:i:s T');
                $exportedBy = $this->user ? $this->user->name : 'System';

                $sheet->setCellValue('A' . $footerRow, "Exported by {$exportedBy} on {$exportTimestamp}");
                $sheet->getStyle('A' . $footerRow)->getFont()->setItalic(true)->setSize(9);
                $sheet->getStyle('A' . $footerRow)->getFont()->getColor()->setARGB('FF666666');

                // Add row count info
                if ($this->rowCount >= self::MAX_EXPORT_ROWS) {
                    $limitRow = $footerRow + 1;
                    $sheet->setCellValue('A' . $limitRow, "Note: Export limited to " . number_format(self::MAX_EXPORT_ROWS) . " rows. Some results may be excluded.");
                    $sheet->getStyle('A' . $limitRow)->getFont()->setItalic(true)->setSize(9)->setBold(true);
                    $sheet->getStyle('A' . $limitRow)->getFont()->getColor()->setARGB('FFFF6600');
                }
            },
        ];
    }
}
