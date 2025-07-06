# 🍅 Sistem Pakar Kematangan Tomat Berbasis AI

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/ESP32-IoT-000000?style=for-the-badge&logo=espressif&logoColor=white" alt="ESP32">
  <img src="https://img.shields.io/badge/AI-Machine%20Learning-FF6B6B?style=for-the-badge&logo=tensorflow&logoColor=white" alt="AI">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Status-Production%20Ready-4CAF50?style=flat-square" alt="Status">
  <img src="https://img.shields.io/badge/Version-1.0.0-blue?style=flat-square" alt="Version">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License">
</p>

---

## 🎯 Tentang Sistem

**Sistem Pakar Kematangan Tomat Berbasis AI** adalah solusi inovatif yang mengintegrasikan teknologi **Internet of Things (IoT)**, **Machine Learning**, dan **Web Application** untuk mendeteksi tingkat kematangan tomat secara otomatis dan akurat.

Sistem ini menggunakan pendekatan multi-algoritma AI yang mencakup:
- 🌳 **Decision Tree** - Untuk klasifikasi berbasis aturan
- 🎯 **K-Nearest Neighbors (KNN)** - Untuk pencocokan pola
- 🌲 **Random Forest** - Untuk ensemble learning
- 🤖 **Ensemble Method** - Kombinasi semua algoritma untuk akurasi maksimal

### ✨ Fitur Utama

- 📊 **Analisis RGB Real-time** - Deteksi warna tomat menggunakan sensor TCS34725
- 🌡️ **Monitoring Lingkungan** - Suhu dan kelembaban dengan sensor DHT11
- 📱 **Web Dashboard** - Interface modern dengan Filament Admin Panel
- 📈 **Machine Learning** - 4 algoritma AI terintegrasi
- 📋 **Data Management** - Import/Export Excel untuk training data
- 🔄 **IoT Integration** - ESP32 dengan komunikasi WiFi
- 📊 **Real-time Analytics** - Grafik dan statistik akurasi model
- 🎨 **Modern UI/UX** - Responsive design dengan Tailwind CSS

---

## 🚀 Quick Start

### Prasyarat
- PHP 8.2+
- Composer
- Node.js & NPM
- MySQL/PostgreSQL
- Arduino IDE (untuk ESP32)

### Instalasi

```bash
# Clone repository
git clone https://github.com/username/sistem-pakar-kematangan-tomat-ai.git
cd sistem-pakar-kematangan-tomat-ai

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate
php artisan db:seed

# Build assets
npm run build

# Start server
php artisan serve
```

---

## 📚 Dokumentasi Lengkap

### 📖 Panduan Utama

| 📋 Dokumentasi | 📝 Deskripsi | 🔗 Link |
|----------------|---------------|----------|
| **Import Data Excel** | Panduan lengkap import/export data training untuk machine learning | [📄 PANDUAN_IMPORT_EXCEL.md](./PANDUAN_IMPORT_EXCEL.md) |
| **Arduino ESP32** | Setup hardware IoT sensor, wiring diagram, dan konfigurasi | [🔧 arduino/README.md](./arduino/README.md) |

### 🎯 Fitur Sistem

#### 🤖 Machine Learning Engine
- **Decision Tree**: Klasifikasi berbasis aturan dengan akurasi tinggi
- **K-Nearest Neighbors**: Pencocokan pola berdasarkan data historis
- **Random Forest**: Ensemble learning untuk prediksi robust
- **Ensemble Method**: Kombinasi semua algoritma untuk hasil optimal

#### 📊 Data Management
- **Training Data**: Kelola dataset RGB untuk training model
- **Import Excel**: Upload data training dalam format Excel
- **Export Data**: Download data untuk analisis eksternal
- **Data Validation**: Validasi otomatis format dan nilai data

#### 🌐 IoT Integration
- **ESP32 Sensor**: Pembacaan RGB, suhu, dan kelembaban
- **Real-time Data**: Streaming data sensor ke dashboard
- **WiFi Communication**: Koneksi nirkabel ke server
- **OLED Display**: Tampilan status dan data di perangkat

---

## 🏗️ Arsitektur Sistem

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   ESP32 Sensor  │───▶│  Laravel API    │───▶│   Web Dashboard │
│                 │    │                 │    │                 │
│ • TCS34725 RGB  │    │ • ML Algorithms │    │ • Filament UI   │
│ • DHT11 Temp    │    │ • Data Storage  │    │ • Real-time     │
│ • OLED Display  │    │ • API Endpoints │    │ • Analytics     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

---

## 🔧 Teknologi Stack

### Backend
- **Framework**: Laravel 11.x
- **Database**: MySQL/PostgreSQL
- **API**: RESTful API
- **Admin Panel**: Filament 3.x

### Frontend
- **CSS Framework**: Tailwind CSS
- **JavaScript**: Alpine.js
- **Charts**: Chart.js
- **Icons**: Heroicons

### IoT & Hardware
- **Microcontroller**: ESP32
- **Color Sensor**: TCS34725
- **Environment**: DHT11
- **Display**: OLED SSD1306

### Machine Learning
- **Algorithms**: Decision Tree, KNN, Random Forest
- **Data Processing**: PHP ML Library
- **Training**: Custom implementation

---

## 📈 Tingkat Kematangan

Sistem dapat mendeteksi 4 tingkat kematangan tomat:

| 🎨 Tingkat | 📊 Karakteristik RGB | 📝 Deskripsi |
|------------|----------------------|---------------|
| 🟢 **Mentah** | Dominan hijau (G > R, G > B) | Tomat belum matang, keras |
| 🟡 **Setengah Matang** | Campuran hijau-kuning | Proses pematangan |
| 🔴 **Matang** | Dominan merah (R > G, R > B) | Siap konsumsi |
| 🟤 **Busuk** | Warna gelap, nilai rendah | Tidak layak konsumsi |

---

## 🎮 Demo & Testing

### Web Interface
1. Akses dashboard di `http://localhost:8000`
2. Login dengan kredensial admin
3. Test sensor simulation di `/test/sensor`
4. Lihat analytics di admin panel

### Hardware Testing
1. Upload kode Arduino ke ESP32
2. Konfigurasi WiFi credentials
3. Monitor Serial untuk debug
4. Test dengan sampel tomat

---

## 🤝 Kontribusi

Kami menyambut kontribusi dari komunitas! Silakan:

1. Fork repository ini
2. Buat branch fitur (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah [MIT License](https://opensource.org/licenses/MIT).

---

## 👥 Tim Pengembang

- **Backend Development**: Laravel & Machine Learning
- **IoT Development**: ESP32 & Sensor Integration
- **Frontend Development**: UI/UX & Dashboard
- **Data Science**: Algorithm Training & Optimization

---

## 📞 Dukungan

Jika Anda mengalami masalah atau memiliki pertanyaan:

- 📧 **Email**: support@tomato-ai.com
- 📱 **Issues**: [GitHub Issues](https://github.com/username/sistem-pakar-kematangan-tomat-ai/issues)
- 📖 **Wiki**: [Project Wiki](https://github.com/username/sistem-pakar-kematangan-tomat-ai/wiki)

---

<p align="center">
  <strong>🍅 Sistem Pakar Kematangan Tomat Berbasis AI</strong><br>
  <em>Revolutionizing Agriculture with AI & IoT Technology</em>
</p>
