<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SamOpportunitiesStateExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    /**
     * @param array<int, array<string, mixed>> $opportunities
     */
    public function __construct(
        protected array $opportunities
    ) {
    }

    public function collection(): Collection
    {
        return collect($this->opportunities);
    }

    public function headings(): array
    {
        return [
            'Notice ID',
            'Solicitation Number',
            'Title',
            'Agency',
            'Notice Type',
            'NAICS Code',
            'PSC Code',
            'State',
            'Set Aside',
            'Posted Date',
            'Response Deadline',
            'SAM.gov URL',
        ];
    }

    /**
     * @param array<string, mixed> $opportunity
     */
    public function map($opportunity): array
    {
        return [
            $opportunity['notice_id'] ?? '',
            $opportunity['solicitation_number'] ?? '',
            $opportunity['title'] ?? '',
            $opportunity['agency_name'] ?? '',
            $opportunity['notice_type'] ?? '',
            $opportunity['naics_code'] ?? '',
            $opportunity['psc_code'] ?? '',
            $opportunity['state_code'] ?? '',
            $opportunity['set_aside_type'] ?? '',
            $this->formatDate($opportunity['posted_date'] ?? null),
            $this->formatDate($opportunity['response_deadline'] ?? null),
            $opportunity['sam_url'] ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
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
                    'startColor' => ['rgb' => '1D4ED8'],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22, // Notice ID
            'B' => 22, // Solicitation Number
            'C' => 60, // Title
            'D' => 30, // Agency
            'E' => 18, // Notice Type
            'F' => 12, // NAICS Code
            'G' => 12, // PSC Code
            'H' => 10, // State
            'I' => 22, // Set Aside
            'J' => 15, // Posted Date
            'K' => 18, // Response Deadline
            'L' => 60, // SAM.gov URL
        ];
    }

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
}
