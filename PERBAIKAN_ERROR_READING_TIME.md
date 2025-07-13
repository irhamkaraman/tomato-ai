# Perbaikan Error: Call to a member function format() on null

## Masalah
Terjadi error `Call to a member function format() on null` saat ESP32 mengirim data sensor ke endpoint `/api/dashboard/sensor-data`.

## Penyebab
1. **Kolom `reading_time` tidak ada di database**: Tabel `tomat_readings` tidak memiliki kolom `reading_time`
2. **Model mencoba mengakses field yang tidak ada**: `DashboardController` mencoba menyimpan dan mengakses `reading_time` yang tidak ada di skema database
3. **Null pointer exception**: Saat memanggil `$reading->reading_time->format()`, nilai `reading_time` adalah null

## Solusi yang Diterapkan

### 1. Perbaikan Model TomatReading
- Menghapus `reading_time` dari array `$fillable`
- Menghapus casting `reading_time` dari array `$casts`
- Menggunakan `created_at` yang sudah tersedia di Laravel

### 2. Perbaikan DashboardController
- Menghapus `'reading_time' => now()` dari proses create data
- Mengubah `$reading->reading_time->format()` menjadi `$reading->created_at->format()`
- Menggunakan timestamp Laravel bawaan (`created_at`) sebagai pengganti

## Perubahan File

### app/Models/TomatReading.php
```php
// SEBELUM
protected $fillable = [
    // ...
    'reading_time',
    // ...
];

protected $casts = [
    // ...
    'reading_time' => 'datetime',
];

// SESUDAH
protected $fillable = [
    // ... (tanpa reading_time)
];

protected $casts = [
    // ... (tanpa reading_time)
];
```

### app/Http/Controllers/DashboardController.php
```php
// SEBELUM
$reading = TomatReading::create([
    // ...
    'reading_time' => now()
]);

return [
    // ...
    'reading_time' => $reading->reading_time->format('Y-m-d H:i:s')
];

// SESUDAH
$reading = TomatReading::create([
    // ... (tanpa reading_time)
]);

return [
    // ...
    'reading_time' => $reading->created_at->format('Y-m-d H:i:s')
];
```

## Hasil
- Error `Call to a member function format() on null` teratasi
- ESP32 dapat mengirim data sensor tanpa error
- Timestamp tetap tersimpan menggunakan `created_at` Laravel
- Fungsionalitas dashboard tetap berjalan normal

## Testing
Untuk menguji perbaikan:
```bash
curl -X GET "http://localhost:8000/api/dashboard/sensor-data?device_id=ESP32_SENSOR_001&red_value=112&green_value=87&blue_value=60&clear_value=8981&temperature=27.1&humidity=84.0" -H "Accept: application/json"
```

## Catatan
- `created_at` dan `updated_at` adalah kolom timestamp bawaan Laravel yang selalu tersedia
- Tidak perlu membuat migration baru karena menggunakan field yang sudah ada
- Solusi ini lebih konsisten dengan konvensi Laravel