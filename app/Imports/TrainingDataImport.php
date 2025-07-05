<?php

namespace App\Imports;

use App\Models\TrainingData;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Validation\Rule;

class TrainingDataImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithBatchInserts, WithChunkReading
{
    use Importable, SkipsErrors, SkipsFailures;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Normalize maturity class
        $maturityClass = $this->normalizeMaturityClass($row['kelas_kematangan'] ?? '');
        
        return new TrainingData([
            'red_value' => (int) $row['nilai_merah_r'],
            'green_value' => (int) $row['nilai_hijau_g'],
            'blue_value' => (int) $row['nilai_biru_b'],
            'maturity_class' => $maturityClass,
            'description' => $row['deskripsi'] ?? '',
            'is_active' => $this->normalizeActiveStatus($row['status_aktif'] ?? 'Aktif')
        ]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'nilai_merah_r' => [
                'required',
                'integer',
                'min:0',
                'max:255'
            ],
            'nilai_hijau_g' => [
                'required',
                'integer',
                'min:0',
                'max:255'
            ],
            'nilai_biru_b' => [
                'required',
                'integer',
                'min:0',
                'max:255'
            ],
            'kelas_kematangan' => [
                'required',
                'string',
                Rule::in([
                    'mentah', 'Mentah', 'MENTAH',
                    'setengah_matang', 'setengah matang', 'Setengah Matang', 'SETENGAH MATANG',
                    'matang', 'Matang', 'MATANG',
                    'busuk', 'Busuk', 'BUSUK'
                ])
            ],
            'deskripsi' => 'nullable|string|max:1000',
            'status_aktif' => [
                'nullable',
                'string',
                Rule::in(['Aktif', 'aktif', 'AKTIF', 'Tidak Aktif', 'tidak aktif', 'TIDAK AKTIF', '1', '0', 'true', 'false'])
            ]
        ];
    }

    /**
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'nilai_merah_r.required' => 'Nilai Merah (R) wajib diisi.',
            'nilai_merah_r.integer' => 'Nilai Merah (R) harus berupa angka.',
            'nilai_merah_r.min' => 'Nilai Merah (R) minimal 0.',
            'nilai_merah_r.max' => 'Nilai Merah (R) maksimal 255.',
            
            'nilai_hijau_g.required' => 'Nilai Hijau (G) wajib diisi.',
            'nilai_hijau_g.integer' => 'Nilai Hijau (G) harus berupa angka.',
            'nilai_hijau_g.min' => 'Nilai Hijau (G) minimal 0.',
            'nilai_hijau_g.max' => 'Nilai Hijau (G) maksimal 255.',
            
            'nilai_biru_b.required' => 'Nilai Biru (B) wajib diisi.',
            'nilai_biru_b.integer' => 'Nilai Biru (B) harus berupa angka.',
            'nilai_biru_b.min' => 'Nilai Biru (B) minimal 0.',
            'nilai_biru_b.max' => 'Nilai Biru (B) maksimal 255.',
            
            'kelas_kematangan.required' => 'Kelas Kematangan wajib diisi.',
            'kelas_kematangan.in' => 'Kelas Kematangan harus salah satu dari: Mentah, Setengah Matang, Matang, atau Busuk.',
            
            'deskripsi.max' => 'Deskripsi maksimal 1000 karakter.',
            
            'status_aktif.in' => 'Status Aktif harus berupa: Aktif atau Tidak Aktif.'
        ];
    }

    /**
     * Normalize maturity class to database format
     */
    private function normalizeMaturityClass($value)
    {
        $normalized = strtolower(trim($value));
        
        return match($normalized) {
            'mentah' => 'mentah',
            'setengah matang', 'setengah_matang' => 'setengah_matang',
            'matang' => 'matang',
            'busuk' => 'busuk',
            default => $normalized
        };
    }

    /**
     * Normalize active status to boolean
     */
    private function normalizeActiveStatus($value)
    {
        $normalized = strtolower(trim($value));
        
        return match($normalized) {
            'aktif', '1', 'true' => true,
            'tidak aktif', '0', 'false' => false,
            default => true // Default to active
        };
    }

    /**
     * @return int
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 100;
    }
}