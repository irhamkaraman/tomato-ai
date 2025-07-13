# Panduan Penggunaan GET Method untuk ESP32

## Ringkasan Perubahan

Sistem telah diperbarui untuk mendukung pengiriman data sensor dari ESP32 menggunakan **GET method** alih-alih POST method. Perubahan ini memungkinkan data dikirim melalui parameter URL, yang lebih sederhana dan kompatibel dengan berbagai konfigurasi server.

## Perubahan yang Dilakukan

### 1. Kode ESP32 (Arduino)

**File:** `arduino/tomato_sensor_esp32/tomato_sensor_esp32.ino`

**Perubahan Utama:**
- Mengubah method HTTP dari `POST` ke `GET`
- Data sensor dikirim sebagai parameter URL
- Menghapus penggunaan JSON payload
- Menyederhanakan header HTTP

**URL Endpoint Baru:**
```
// Production
https://tomato-ai.lik.my.id/api/tomat-readings/sensor-data

// Development/Local
http://localhost:8000/api/tomat-readings/sensor-data
```

**Format URL dengan Parameter:**
```
https://tomato-ai.lik.my.id/api/tomat-readings/sensor-data?device_id=ESP32_SENSOR_001&red_value=120&green_value=80&blue_value=45&clear_value=1500&temperature=25.5&humidity=60.2
```

### 2. Route Laravel

**File:** `routes/api.php`

**Route Baru:**
```php
Route::get('/sensor-data', [TomatReadingController::class, 'receiveSensorData']);
```

### 3. Controller Laravel

**File:** `app/Http/Controllers/TomatReadingController.php`

**Method Baru:** `receiveSensorData()`
- Menerima data dari query parameters
- Validasi data yang sama seperti method `store()`
- Memproses data dengan algoritma AI yang sama
- Menyimpan ke database dengan format yang sama

## Parameter yang Dikirim ESP32

| Parameter | Tipe | Deskripsi | Contoh |
|-----------|------|-----------|--------|
| `device_id` | String | ID unik perangkat ESP32 | `ESP32_SENSOR_001` |
| `red_value` | Integer (0-255) | Nilai merah RGB | `120` |
| `green_value` | Integer (0-255) | Nilai hijau RGB | `80` |
| `blue_value` | Integer (0-255) | Nilai biru RGB | `45` |
| `clear_value` | Integer | Nilai clear sensor warna | `1500` |
| `temperature` | Float | Suhu dalam Celsius | `25.5` |
| `humidity` | Float | Kelembaban dalam persen | `60.2` |

## Keuntungan GET Method

### 1. **Kompatibilitas Server**
- Tidak memerlukan konfigurasi khusus untuk JSON payload
- Lebih kompatibel dengan berbagai web server (Apache, Nginx)
- Mengurangi masalah CORS dan content-type

### 2. **Debugging Lebih Mudah**
- URL dapat ditest langsung di browser
- Parameter terlihat jelas di URL
- Logging lebih sederhana

### 3. **Ukuran Request Lebih Kecil**
- Tidak ada overhead JSON
- Header HTTP minimal
- Bandwidth lebih efisien

### 4. **Caching Friendly**
- GET request dapat di-cache oleh proxy/CDN
- Meningkatkan performa jika diperlukan

## Cara Testing

### 1. **Test Manual di Browser**
```
https://tomato-ai.lik.my.id/api/tomat-readings/sensor-data?device_id=TEST_001&red_value=150&green_value=100&blue_value=50&clear_value=2000&temperature=26.0&humidity=65.0
```

### 2. **Test dengan cURL**
```bash
curl "https://tomato-ai.lik.my.id/api/tomat-readings/sensor-data?device_id=TEST_001&red_value=150&green_value=100&blue_value=50&clear_value=2000&temperature=26.0&humidity=65.0"
```

### 3. **Test Lokal**
```
http://localhost:8000/api/tomat-readings/sensor-data?device_id=TEST_001&red_value=150&green_value=100&blue_value=50&clear_value=2000&temperature=26.0&humidity=65.0
```

## Response Format

**Success Response (200):**
```json
{
  "success": true,
  "message": "Reading created successfully via GET",
  "data": {
    "id": 123,
    "device_id": "ESP32_SENSOR_001",
    "red_value": 150,
    "green_value": 100,
    "blue_value": 50,
    "clear_value": 2000,
    "temperature": 26.0,
    "humidity": 65.0,
    "maturity_level": "setengah_matang",
    "confidence_score": 85.5,
    "status": "good",
    "created_at": "2025-01-20T10:30:00.000000Z"
  },
  "recommendations": [
    "Tomat dalam tahap setengah matang",
    "Cocok untuk transportasi jarak jauh"
  ],
  "analysis": {
    "algorithm_used": "ensemble_voting",
    "confidence": 85.5,
    "rgb_analysis": {
      "dominant_color": "orange",
      "color_ratio": {
        "red_ratio": 0.5,
        "green_ratio": 0.33,
        "blue_ratio": 0.17
      }
    }
  }
}
```

**Error Response (422 - Validation Error):**
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "red_value": ["The red value field is required."]
  }
}
```

## Monitoring dan Logging

### Log ESP32
```
GET URL: https://tomato-ai.lik.my.id/api/tomat-readings/sensor-data?device_id=ESP32_SENSOR_001&red_value=150&...
URL Length: 156
HTTP Response Code: 200
Server Response: {"success":true,"message":"Reading created successfully via GET",...}
✓ Data berhasil dikirim ke server
```

### Log Laravel
```
[2025-01-20 10:30:00] local.INFO: ESP32 GET data received {"device_id":"ESP32_SENSOR_001","data":{"device_id":"ESP32_SENSOR_001","red_value":"150",...}}
[2025-01-20 10:30:00] local.INFO: TomatReading created successfully via GET {"reading_id":123,"device_id":"ESP32_SENSOR_001"}
[2025-01-20 10:30:00] local.INFO: ESP32 GET data processed successfully {"reading_id":123,"device_id":"ESP32_SENSOR_001","maturity_level":"setengah_matang","confidence_score":85.5}
```

## Dashboard Integration

Dashboard akan secara otomatis menampilkan data yang diterima melalui GET method karena:

1. **Data disimpan ke database yang sama** (`tomat_readings` table)
2. **Format data identik** dengan method POST sebelumnya
3. **API dashboard tidak berubah** - tetap menggunakan endpoint yang sama:
   - `/api/dashboard/latest` - Data terbaru
   - `/api/dashboard/readings` - 10 data terbaru
   - `/api/dashboard/stats` - Statistik sistem

## Troubleshooting

### 1. **URL Terlalu Panjang**
- Maksimal panjang URL: ~2000 karakter
- URL saat ini: ~150-200 karakter (aman)

### 2. **Parameter Tidak Diterima**
- Pastikan semua parameter required ada
- Cek format nilai (integer untuk RGB, float untuk suhu/kelembaban)

### 3. **Server Error 500**
- Cek log Laravel di `storage/logs/laravel.log`
- Pastikan database connection aktif
- Verifikasi semua model dan migration sudah benar

### 4. **Validation Error 422**
- Cek format parameter sesuai dengan aturan validasi
- Pastikan nilai RGB dalam range 0-255
- Pastikan device_id tidak kosong

## Backward Compatibility

Sistem masih mendukung **POST method** melalui endpoint lama:
```
POST https://tomato-ai.lik.my.id/api/tomat-readings/
```

Jadi jika ada perangkat lain yang masih menggunakan POST, tidak akan terpengaruh.

## Kesimpulan

Perubahan ke GET method memberikan:
- ✅ **Kompatibilitas server yang lebih baik**
- ✅ **Debugging yang lebih mudah**
- ✅ **Request yang lebih ringan**
- ✅ **Testing yang lebih sederhana**
- ✅ **Backward compatibility tetap terjaga**

Sistem sekarang lebih robust dan mudah di-maintain untuk deployment di berbagai jenis hosting.