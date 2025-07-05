# Panduan Import Data Training Excel

## Format File Excel yang Diperlukan

Untuk melakukan import data training, file Excel harus mengikuti format kolom yang telah ditentukan. Berikut adalah aturan yang harus dipatuhi:

### 1. Header Kolom (Baris Pertama)
File Excel harus memiliki header kolom pada baris pertama dengan nama kolom yang **EXACT** seperti berikut:

| Kolom A | Kolom B | Kolom C | Kolom D | Kolom E | Kolom F |
|---------|---------|---------|---------|---------|----------|
| nilai_merah_r | nilai_hijau_g | nilai_biru_b | kelas_kematangan | deskripsi | status_aktif |

### 2. Aturan Data per Kolom

#### A. nilai_merah_r (Wajib)
- **Tipe Data**: Angka bulat (integer)
- **Rentang**: 0 - 255
- **Contoh**: 255, 128, 0

#### B. nilai_hijau_g (Wajib)
- **Tipe Data**: Angka bulat (integer)
- **Rentang**: 0 - 255
- **Contoh**: 255, 128, 0

#### C. nilai_biru_b (Wajib)
- **Tipe Data**: Angka bulat (integer)
- **Rentang**: 0 - 255
- **Contoh**: 255, 128, 0

#### D. kelas_kematangan (Wajib)
- **Tipe Data**: Teks
- **Nilai yang Diizinkan**:
  - `Mentah` atau `mentah` atau `MENTAH`
  - `Setengah Matang` atau `setengah matang` atau `SETENGAH MATANG`
  - `Matang` atau `matang` atau `MATANG`
  - `Busuk` atau `busuk` atau `BUSUK`

#### E. deskripsi (Opsional)
- **Tipe Data**: Teks
- **Maksimal**: 1000 karakter
- **Contoh**: "Tomat dengan warna merah dominan"
- **Boleh kosong**

#### F. status_aktif (Opsional)
- **Tipe Data**: Teks
- **Nilai yang Diizinkan**:
  - `Aktif` atau `aktif` atau `AKTIF` atau `1` atau `true`
  - `Tidak Aktif` atau `tidak aktif` atau `TIDAK AKTIF` atau `0` atau `false`
- **Default**: Jika kosong, akan diset sebagai `Aktif`

### 3. Contoh Data yang Benar

| nilai_merah_r | nilai_hijau_g | nilai_biru_b | kelas_kematangan | deskripsi | status_aktif |
|---------------|---------------|--------------|------------------|-----------|-------------|
| 255 | 0 | 0 | Matang | Tomat matang dengan warna merah dominan | Aktif |
| 150 | 200 | 100 | Setengah Matang | Tomat setengah matang | Aktif |
| 100 | 150 | 50 | Mentah | Tomat mentah dengan warna hijau dominan | Aktif |
| 70 | 90 | 100 | Busuk | Tomat busuk | Tidak Aktif |

### 4. Cara Menggunakan Fitur Import

1. **Download Template**: Klik tombol "Download Template Excel" untuk mendapatkan file template yang sudah sesuai format
2. **Isi Data**: Buka file template dan isi data sesuai aturan di atas
3. **Simpan File**: Simpan file dalam format Excel (.xlsx atau .xls)
4. **Import**: Klik tombol "Import Excel" dan pilih file yang sudah diisi
5. **Validasi**: Sistem akan memvalidasi data dan memberikan notifikasi hasil import

### 5. Tips Penting

- **Gunakan Template**: Selalu gunakan template yang disediakan untuk menghindari kesalahan format
- **Periksa Data**: Pastikan semua nilai RGB berada dalam rentang 0-255
- **Konsistensi**: Gunakan format yang konsisten untuk kelas kematangan
- **Backup**: Selalu backup data sebelum melakukan import
- **Batch Import**: Untuk data besar, disarankan import dalam batch maksimal 1000 baris

### 6. Troubleshooting

#### Error: "Nilai RGB tidak valid"
- Pastikan nilai RGB adalah angka bulat antara 0-255
- Jangan gunakan desimal atau karakter lain

#### Error: "Kelas kematangan tidak valid"
- Pastikan menggunakan salah satu dari: Mentah, Setengah Matang, Matang, Busuk
- Perhatikan penulisan dan spasi

#### Error: "Format file tidak didukung"
- Pastikan file berformat .xlsx atau .xls
- Jangan gunakan format CSV atau format lainnya

### 7. Fitur Export

Anda juga dapat menggunakan fitur "Export Excel" untuk:
- Mendapatkan backup data training yang ada
- Melihat format data yang benar
- Mengedit data existing dan import kembali

---

**Catatan**: Sistem akan otomatis melewati baris yang memiliki error dan memberikan laporan detail tentang baris mana saja yang gagal diimport beserta alasannya.