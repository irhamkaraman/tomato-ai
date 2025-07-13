# Panduan Dashboard AI Real-time

## Overview
Sistem telah diperbarui untuk memungkinkan ESP32 mengirim data langsung ke `DashboardController` yang akan melakukan analisis AI secara real-time dan menampilkan hasilnya di dashboard.

## Perubahan Arsitektur

### 1. Endpoint Baru
**URL Baru untuk ESP32:**
```
https://tomato-ai.lik.my.id/api/dashboard/sensor-data
```

**URL Development:**
```
http://localhost:8000/api/dashboard/sensor-data
```

### 2. Flow Data Baru
```
ESP32 → DashboardController::index() → AI Analysis → Dashboard View
```

**Proses:**
1. ESP32 mengirim data sensor via GET request
2. `DashboardController::index()` menerima dan memvalidasi data
3. Data disimpan ke database (`TomatReading`)
4. Sistem melakukan analisis AI menggunakan 4 algoritma:
   - Decision Tree
   - K-Nearest Neighbors (KNN)
   - Random Forest
   - Ensemble Voting
5. Hasil analisis ditampilkan di dashboard secara real-time
6. Rekomendasi berdasarkan tingkat kematangan digenerate

## Fitur Baru Dashboard

### 1. Real-time Sensor Data Display
- Menampilkan nilai RGB, temperature, dan humidity secara real-time
- Status device ESP32
- Timestamp data terbaru

### 2. AI Analysis Results
- **Ensemble Prediction**: Hasil akhir dari voting 3 algoritma
- **Confidence Score**: Tingkat kepercayaan prediksi
- **Consensus Level**: Unanimous, Strong Majority, atau No Consensus
- **Individual Algorithm Results**: Hasil dari setiap algoritma

### 3. Smart Recommendations
- Rekomendasi penyimpanan
- Cara penanganan
- Saran penggunaan
- Timeframe kematangan

### 4. Error Handling
- Fallback prediction jika analisis gagal
- Error messages yang informatif
- Logging untuk debugging

## Algoritma AI yang Digunakan

### 1. Decision Tree
- Menggunakan aturan berbasis RGB ratio
- Fallback ke aturan hardcoded jika database kosong
- Confidence: 0.6 - 0.85

### 2. K-Nearest Neighbors (KNN)
- K = 3 (3 tetangga terdekat)
- Menggunakan Euclidean distance
- Majority voting dari tetangga terdekat

### 3. Random Forest
- Simulasi 3 decision trees dengan variasi fokus
- Majority voting dari hasil trees
- Lebih robust terhadap noise

### 4. Ensemble Voting
- Menggabungkan hasil dari 3 algoritma di atas
- Majority voting untuk prediksi final
- Confidence berdasarkan konsensus

## Parameter ESP32

### Required Parameters
```
device_id: String (e.g., "ESP32_SENSOR_001")
red_value: Integer (0-255)
green_value: Integer (0-255)
blue_value: Integer (0-255)
```

### Optional Parameters
```
clear_value: Integer
temperature: Float
humidity: Float
```

### Contoh URL Request
```
https://tomato-ai.lik.my.id/api/dashboard/sensor-data?device_id=ESP32_SENSOR_001&red_value=112&green_value=87&blue_value=60&clear_value=8949&temperature=27.1&humidity=83.0
```

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Data processed successfully",
  "sensor_data": {
    "id": 123,
    "device_id": "ESP32_SENSOR_001",
    "red_value": 112,
    "green_value": 87,
    "blue_value": 60,
    "temperature": 27.1,
    "humidity": 83.0,
    "reading_time": "2024-01-15 10:30:45"
  },
  "ai_analysis": {
    "ensemble": {
      "prediction": "setengah_matang",
      "confidence": 0.67,
      "consensus": "Strong Majority"
    },
    "algorithms": {
      "decision_tree": {
        "classification": "setengah_matang",
        "confidence": 0.65
      },
      "knn": {
        "prediction": "setengah_matang",
        "confidence": 0.70
      },
      "random_forest": {
        "prediction": "mentah",
        "confidence": 0.75
      }
    }
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "red_value": ["The red value field is required."]
  }
}
```

## Validasi Data

### Input Validation
- `device_id`: Required, string, max 50 characters
- `red_value`: Required, integer, 0-255
- `green_value`: Required, integer, 0-255
- `blue_value`: Required, integer, 0-255
- `clear_value`: Optional, integer, min 0
- `temperature`: Optional, numeric
- `humidity`: Optional, numeric, 0-100

### Data Sanitization
- RGB values diklem ke range 0-255
- Temperature dan humidity divalidasi range wajar
- Device ID disanitasi untuk keamanan

## Monitoring & Logging

### Log Events
- Sensor data received
- AI analysis started/completed
- Errors and exceptions
- Performance metrics

### Log Locations
```
storage/logs/laravel.log
```

### Log Format
```
[2024-01-15 10:30:45] local.INFO: Sensor data received and saved in dashboard {"reading_id":123,"device_id":"ESP32_SENSOR_001","rgb":{"red":112,"green":87,"blue":60}}
```

## Troubleshooting

### Common Issues

1. **Error 500 - Internal Server Error**
   - Check log files for detailed error
   - Verify database connection
   - Ensure all required parameters are sent

2. **Validation Errors**
   - Check parameter names and values
   - Ensure RGB values are in range 0-255
   - Verify device_id format

3. **AI Analysis Fails**
   - System will use fallback prediction
   - Check training data availability
   - Verify ModelEvaluationService status

### Debug Mode
Untuk development, set `APP_DEBUG=true` di `.env` file untuk error details.

## Performance Considerations

### Optimization
- AI analysis di-cache untuk RGB values yang sama
- Database queries dioptimasi
- Lazy loading untuk data yang tidak diperlukan

### Scalability
- Sistem dapat menangani multiple ESP32 devices
- Database indexing untuk performa query
- Rate limiting untuk mencegah spam requests

## Security

### Input Sanitization
- Semua input divalidasi dan disanitasi
- SQL injection protection
- XSS protection di view

### Rate Limiting
- Maksimal 60 requests per menit per IP
- Throttling untuk mencegah abuse

## Migration dari Endpoint Lama

### Untuk ESP32
1. Update URL dari `/api/tomat-readings/sensor-data` ke `/api/dashboard/sensor-data`
2. Parameter tetap sama
3. Response format sedikit berbeda (lebih detail)

### Backward Compatibility
Endpoint lama masih tersedia untuk compatibility:
```
/api/tomat-readings/sensor-data (legacy)
```

## Testing

### Manual Testing
```bash
curl "http://localhost:8000/api/dashboard/sensor-data?device_id=TEST&red_value=150&green_value=100&blue_value=80&temperature=25.5&humidity=60.0"
```

### Expected Response
Sistem harus mengembalikan JSON dengan data sensor dan hasil analisis AI.

## Support

Untuk pertanyaan atau issues:
1. Check log files di `storage/logs/`
2. Verify database connection
3. Test dengan parameter minimal (device_id, RGB values)
4. Check network connectivity ESP32 → Server