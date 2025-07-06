# Arduino ESP32 - Sistem Sensor Kematangan Tomat

Proyek ini berisi kode Arduino untuk ESP32 yang berfungsi sebagai sensor IoT untuk sistem deteksi kematangan tomat yang terintegrasi dengan server Laravel.

## 📋 Daftar Komponen

### Hardware yang Dibutuhkan:
- **ESP32 Development Board** (NodeMCU ESP32 atau sejenisnya)
- **Sensor Warna TCS34725** - untuk deteksi RGB tomat
- **Sensor DHT11** - untuk mengukur suhu dan kelembaban lingkungan
- **Display OLED 128x64 SSD1306** - untuk menampilkan data sensor
- **Breadboard dan Kabel Jumper**
- **Resistor 10kΩ** (untuk pull-up DHT11 jika diperlukan)

### Library yang Dibutuhkan:
```
- Wire (built-in)
- Adafruit_SSD1306
- Adafruit_TCS34725
- DHT sensor library
- WiFi (built-in ESP32)
- HTTPClient (built-in ESP32)
- ArduinoJson
```

## 🔌 Diagram Koneksi

### ESP32 Pin Configuration:
```
ESP32 GPIO    | Komponen           | Keterangan
--------------|--------------------|------------------
GPIO 23       | DHT11 Data Pin     | Digital Input
GPIO 21       | SDA (I2C)          | TCS34725 & OLED
GPIO 22       | SCL (I2C)          | TCS34725 & OLED
3.3V          | VCC Sensors        | Power Supply
GND           | GND Sensors        | Ground
```

### Koneksi Detail:

#### DHT11 Temperature & Humidity Sensor:
- VCC → 3.3V ESP32
- GND → GND ESP32
- DATA → GPIO 23

#### TCS34725 Color Sensor:
- VIN → 3.3V ESP32
- GND → GND ESP32
- SDA → GPIO 21
- SCL → GPIO 22
- LED → 3.3V (optional, untuk LED built-in sensor)

#### OLED Display SSD1306:
- VCC → 3.3V ESP32
- GND → GND ESP32
- SDA → GPIO 21
- SCL → GPIO 22

## ⚙️ Instalasi dan Setup

### 1. Install Arduino IDE
- Download dan install [Arduino IDE](https://www.arduino.cc/en/software)
- Tambahkan ESP32 board ke Arduino IDE:
  - File → Preferences
  - Tambahkan URL: `https://dl.espressif.com/dl/package_esp32_index.json`
  - Tools → Board → Boards Manager → Search "ESP32" → Install

### 2. Install Library yang Diperlukan
Buka Arduino IDE → Tools → Manage Libraries, lalu install:
- `Adafruit SSD1306`
- `Adafruit TCS34725`
- `DHT sensor library by Adafruit`
- `ArduinoJson`

### 3. Konfigurasi Kode
Edit file `tomato_sensor_esp32.ino` dan ubah:

```cpp
// WiFi Credentials - GANTI DENGAN KREDENSIAL WIFI ANDA
const char* ssid = "NAMA_WIFI_ANDA";
const char* password = "PASSWORD_WIFI_ANDA";

// Device ID - GANTI DENGAN ID DEVICE YANG TERDAFTAR DI DATABASE
const char* deviceId = "ESP32_SENSOR_001";

// URL server API - GANTI DENGAN URL SERVER ANDA
const char* serverUrl = "http://alamat-server-anda.com/api/tomat-readings";
```

### 4. Upload Kode
- Hubungkan ESP32 ke komputer via USB
- Pilih board: Tools → Board → ESP32 Dev Module
- Pilih port yang sesuai: Tools → Port
- Upload kode: Sketch → Upload

## 📊 Fitur dan Fungsi

### Pembacaan Sensor:
- **Suhu dan Kelembaban**: Dibaca dari DHT11 setiap 2 detik
- **Data Warna RGB**: Dibaca dari TCS34725 untuk analisis kematangan
- **Clear Value**: Intensitas cahaya dari sensor warna

### Tampilan OLED:
- Suhu dan kelembaban real-time
- Nilai RGB dan Clear
- Status deteksi tomat (Matang/Mentah/Tidak Ada)
- Status koneksi WiFi

### Komunikasi dengan Server:
- Mengirim data sensor ke server Laravel setiap 10 detik
- Format JSON dengan struktur yang sesuai dengan API
- Auto-reconnect WiFi jika koneksi terputus
- Feedback status pengiriman di OLED

### Algoritma Deteksi Sederhana:
```cpp
if (red > green && red > blue && red > 100) {
    // Tomat Matang (dominan merah)
} else if (green > red && green > blue) {
    // Tomat Mentah (dominan hijau)
} else {
    // Tidak ada tomat
}
```

## 🔧 Troubleshooting

### Masalah Umum:

#### 1. Sensor TCS34725 tidak terdeteksi:
- Periksa koneksi I2C (SDA/SCL)
- Pastikan alamat I2C benar (biasanya 0x29)
- Cek power supply 3.3V

#### 2. OLED tidak menampilkan:
- Periksa koneksi I2C
- Pastikan alamat I2C OLED (biasanya 0x3C)
- Coba ganti kabel jumper

#### 3. DHT11 memberikan nilai NaN:
- Periksa koneksi data pin
- Tambahkan delay lebih lama antara pembacaan
- Pastikan sensor mendapat power yang cukup

#### 4. WiFi tidak terhubung:
- Periksa SSID dan password
- Pastikan ESP32 dalam jangkauan WiFi
- Coba restart ESP32

#### 5. Data tidak terkirim ke server:
- Periksa URL server dan endpoint API
- Pastikan server Laravel berjalan
- Cek firewall dan network connectivity

## 📈 Monitoring dan Debug

### Serial Monitor:
Buka Serial Monitor (Ctrl+Shift+M) untuk melihat:
- Status inisialisasi sensor
- Data sensor real-time
- Status koneksi WiFi
- Response dari server
- Error messages

### LED Indikator:
- LED built-in ESP32 akan berkedip saat mengirim data
- OLED menampilkan status "Data Sent OK" atau "Send Failed"

## 🔄 Integrasi dengan Server Laravel

Sensor ini mengirim data dalam format JSON:
```json
{
  "device_id": "ESP32_SENSOR_001",
  "red_value": 255,
  "green_value": 100,
  "blue_value": 50,
  "clear_value": 1000,
  "temperature": 25.5,
  "humidity": 60.0
}
```

Data dikirim ke endpoint: `POST /api/tomat-readings`

## 📝 Catatan Pengembangan

### Kustomisasi:
- Interval pembacaan sensor dapat diubah di variabel `SENSOR_INTERVAL`
- Interval pengiriman data dapat diubah di variabel `SEND_INTERVAL`
- Algoritma deteksi tomat dapat diperbaiki sesuai kebutuhan

### Pengembangan Lanjutan:
- Tambahkan sensor pH untuk analisis lebih akurat
- Implementasi deep sleep untuk hemat baterai
- Tambahkan SD card untuk logging data offline
- Implementasi OTA (Over-The-Air) update

## 📞 Support

Jika mengalami masalah atau butuh bantuan:
1. Periksa Serial Monitor untuk error messages
2. Pastikan semua koneksi hardware benar
3. Verifikasi konfigurasi WiFi dan server URL
4. Cek dokumentasi library yang digunakan

---

**Dibuat untuk Sistem Pakar Kematangan Tomat Berbasis AI**  
*Terintegrasi dengan Laravel Backend System*