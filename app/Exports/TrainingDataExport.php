<?php

namespace App\Exports;

use App\Models\TrainingData;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TrainingDataExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return TrainingData::orderBy('id')->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nilai Merah (R)',
            'Nilai Hijau (G)',
            'Nilai Biru (B)',
            'Kelas Kematangan',
            'Deskripsi',
            'Status Aktif',
            'Tanggal Dibuat',
            'Tanggal Diperbarui'
        ];
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->red_value,
            $row->green_value,
            $row->blue_value,
            $this->formatMaturityClass($row->maturity_class),
            $row->description,
            $row->is_active ? 'Aktif' : 'Tidak Aktif',
            $row->created_at->format('d/m/Y H:i:s'),
            $row->updated_at->format('d/m/Y H:i:s')
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
            
            // Set background color for header
            'A1:I1' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4472C4']
                ],
                'font' => [
                    'color' => ['argb' => Color::COLOR_WHITE],
                    'bold' => true
                ]
            ]
        ];
    }

    /**
     * Format maturity class for display
     */
    private function formatMaturityClass($class)
    {
        return match($class) {
            'mentah' => 'Mentah',
            'setengah_matang' => 'Setengah Matang',
            'matang' => 'Matang',
            'busuk' => 'Busuk',
            default => $class
        };
    }
}