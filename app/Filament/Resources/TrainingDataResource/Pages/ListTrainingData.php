<?php

namespace App\Filament\Resources\TrainingDataResource\Pages;

use App\Filament\Resources\TrainingDataResource;
use App\Exports\TrainingDataExport;
use App\Imports\TrainingDataImport;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ListTrainingData extends ListRecords
{
    protected static string $resource = TrainingDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            
            Actions\Action::make('downloadTemplate')
                ->label('Download Template Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function () {
                    return $this->downloadTemplate();
                }),
            
            Actions\Action::make('importExcel')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    FileUpload::make('file')
                        ->label('File Excel')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                        ->required()
                        ->helperText('Upload file Excel dengan format yang sesuai template. Maksimal 10MB.')
                        ->maxSize(10240), // 10MB
                ])
                ->action(function (array $data) {
                    $this->importExcel($data['file']);
                }),
            
            Actions\Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(function () {
                    return $this->exportExcel();
                }),
        ];
    }
    
    protected function downloadTemplate()
    {
        // Create template data
        $templateData = [
            [
                'nilai_merah_r' => 255,
                'nilai_hijau_g' => 0,
                'nilai_biru_b' => 0,
                'kelas_kematangan' => 'Matang',
                'deskripsi' => 'Contoh data tomat matang dengan warna merah dominan',
                'status_aktif' => 'Aktif'
            ],
            [
                'nilai_merah_r' => 150,
                'nilai_hijau_g' => 200,
                'nilai_biru_b' => 100,
                'kelas_kematangan' => 'Setengah Matang',
                'deskripsi' => 'Contoh data tomat setengah matang',
                'status_aktif' => 'Aktif'
            ],
            [
                'nilai_merah_r' => 100,
                'nilai_hijau_g' => 150,
                'nilai_biru_b' => 50,
                'kelas_kematangan' => 'Mentah',
                'deskripsi' => 'Contoh data tomat mentah dengan warna hijau dominan',
                'status_aktif' => 'Aktif'
            ],
            [
                'nilai_merah_r' => 70,
                'nilai_hijau_g' => 90,
                'nilai_biru_b' => 100,
                'kelas_kematangan' => 'Busuk',
                'deskripsi' => 'Contoh data tomat busuk',
                'status_aktif' => 'Tidak Aktif'
            ]
        ];
        
        return Excel::download(new class($templateData) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\ShouldAutoSize {
            private $data;
            
            public function __construct($data) {
                $this->data = $data;
            }
            
            public function array(): array {
                return $this->data;
            }
            
            public function headings(): array {
                return [
                    'nilai_merah_r',
                    'nilai_hijau_g', 
                    'nilai_biru_b',
                    'kelas_kematangan',
                    'deskripsi',
                    'status_aktif'
                ];
            }
            
            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet) {
                return [
                    1 => ['font' => ['bold' => true]],
                    'A1:F1' => [
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FF4472C4']
                        ],
                        'font' => [
                            'color' => ['argb' => \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE],
                            'bold' => true
                        ]
                    ]
                ];
            }
        }, 'template-training-data.xlsx');
    }
    
    protected function importExcel($file)
    {
        try {
            $import = new TrainingDataImport();
            Excel::import($import, $file);
            
            $errors = $import->failures();
            
            if ($errors->count() > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = "Baris {$error->row()}: " . implode(', ', $error->errors());
                }
                
                Notification::make()
                    ->title('Import Berhasil dengan Peringatan')
                    ->body('Beberapa data tidak dapat diimport: ' . implode('; ', array_slice($errorMessages, 0, 3)) . (count($errorMessages) > 3 ? '...' : ''))
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('Import Berhasil')
                    ->body('Semua data training berhasil diimport.')
                    ->success()
                    ->send();
            }
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Import Gagal')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function exportExcel()
    {
        try {
            return Excel::download(new TrainingDataExport(), 'training-data-' . now()->format('Y-m-d-H-i-s') . '.xlsx');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Export Gagal')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
