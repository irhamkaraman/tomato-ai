/*
 * =====================================================================================
 * SISTEM SENSOR KEMATANGAN TOMAT BERBASIS ESP32
 * =====================================================================================
 *
 * Kode ini mengimplementasikan sistem sensor untuk deteksi kematangan tomat menggunakan:
 * - ESP32 sebagai mikrokontroler utama
 * - Sensor TCS34725 untuk deteksi warna RGB
 * - Sensor DHT11 untuk suhu dan kelembaban
 * - Display OLED SSD1306 untuk menampilkan data
 * - Koneksi WiFi untuk mengirim data ke server Laravel
 *
 * KOMPONEN YANG DIGUNAKAN:
 * - ESP32 Development Board
 * - Sensor Warna TCS34725 (I2C)
 * - Sensor Suhu & Kelembaban DHT11
 * - Display OLED 128x64 SSD1306 (I2C)
 *
 * KONEKSI PIN:
 * - DHT11 Data Pin: GPIO 23
 * - I2C SDA: GPIO 21
 * - I2C SCL: GPIO 22
 * - TCS34725: I2C (SDA/SCL)
 * - OLED Display: I2C (SDA/SCL)
 *
 * =====================================================================================
 */

#include <Wire.h>
#include <Adafruit_SSD1306.h>
#include <Adafruit_TCS34725.h>
#include <DHT.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// Pin Definitions
#define DHTPIN 23        // Pin untuk sensor DHT11
#define DHTTYPE DHT11    // Tipe sensor DHT
#define SCREEN_WIDTH 128 // Lebar OLED
#define SCREEN_HEIGHT 64 // Tinggi OLED
#define OLED_RESET -1    // Pin reset untuk OLED, jika tidak digunakan
#define I2C_SDA_PIN 21   // Pin SDA untuk I2C
#define I2C_SCL_PIN 22   // Pin SCL untuk I2C

// Setup DHT sensor
DHT dht(DHTPIN, DHTTYPE);

// Setup TCS34725 sensor
Adafruit_TCS34725 colorSensor = Adafruit_TCS34725(TCS34725_INTEGRATIONTIME_614MS, TCS34725_GAIN_1X);

// Setup OLED display
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// WiFi Credentials - GANTI DENGAN KREDENSIAL WIFI ANDA
const char *ssid = "YOUR_SSID";
const char *password = "YOUR_PASSWORD";

// Device ID - GANTI DENGAN ID DEVICE YANG TERDAFTAR DI DATABASE
const char *deviceId = "ESP32_SENSOR_001";

// URL server API untuk mengirim data - GANTI DENGAN URL SERVER ANDA
const char *serverUrl = "https:://tomato-ai.lik.my.id/api/tomat-readings";

// Variabel untuk tracking waktu
unsigned long lastSensorRead = 0;
unsigned long lastDataSend = 0;
const unsigned long SENSOR_INTERVAL = 2000; // Baca sensor setiap 2 detik
const unsigned long SEND_INTERVAL = 10000;  // Kirim data setiap 10 detik

// Variabel untuk menyimpan data sensor
struct SensorData
{
    float temperature;
    float humidity;
    uint16_t red;
    uint16_t green;
    uint16_t blue;
    uint16_t clear;
    bool isValid;
};

SensorData currentData;

/**
 * Fungsi untuk mengirim data ke server Laravel
 */
void sendDataToServer(SensorData data)
{
    if (WiFi.status() != WL_CONNECTED)
    {
        Serial.println("WiFi tidak terhubung, mencoba reconnect...");
        WiFi.reconnect();
        return;
    }

    HTTPClient http;
    http.begin(serverUrl);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("Accept", "application/json");

    // Membuat JSON payload
    StaticJsonDocument<300> jsonDoc;
    jsonDoc["device_id"] = deviceId;
    jsonDoc["red_value"] = data.red;
    jsonDoc["green_value"] = data.green;
    jsonDoc["blue_value"] = data.blue;
    jsonDoc["clear_value"] = data.clear;
    jsonDoc["temperature"] = data.temperature;
    jsonDoc["humidity"] = data.humidity;

    String jsonString;
    serializeJson(jsonDoc, jsonString);

    Serial.println("Mengirim data ke server:");
    Serial.println(jsonString);

    int httpResponseCode = http.POST(jsonString);

    if (httpResponseCode > 0)
    {
        String response = http.getString();
        Serial.printf("Response Code: %d\n", httpResponseCode);
        Serial.println("Response: " + response);

        if (httpResponseCode == 200 || httpResponseCode == 201)
        {
            Serial.println("✓ Data berhasil dikirim ke server");
            displayStatus("Data Sent OK", true);
        }
        else
        {
            Serial.println("⚠ Server merespons dengan error");
            displayStatus("Server Error", false);
        }
    }
    else
    {
        Serial.printf("✗ Error mengirim data: %d\n", httpResponseCode);
        displayStatus("Send Failed", false);
    }

    http.end();
}

/**
 * Fungsi untuk membaca data dari semua sensor
 */
SensorData readSensors()
{
    SensorData data;
    data.isValid = true;

    // Membaca data DHT11
    data.temperature = dht.readTemperature();
    data.humidity = dht.readHumidity();

    // Validasi data DHT11
    if (isnan(data.temperature) || isnan(data.humidity))
    {
        Serial.println("⚠ Gagal membaca sensor DHT11");
        data.isValid = false;
        return data;
    }

    // Membaca data sensor warna TCS34725
    colorSensor.getRawData(&data.red, &data.green, &data.blue, &data.clear);

    // Validasi data sensor warna
    if (data.clear == 0)
    {
        Serial.println("⚠ Gagal membaca sensor warna TCS34725");
        data.isValid = false;
        return data;
    }

    return data;
}

/**
 * Fungsi untuk menampilkan data sensor di OLED
 */
void displaySensorData(SensorData data)
{
    display.clearDisplay();
    display.setTextSize(1);
    display.setTextColor(SSD1306_WHITE);

    // Baris 1: Suhu dan Kelembaban
    display.setCursor(0, 0);
    display.printf("T:%.1fC H:%.1f%%", data.temperature, data.humidity);

    // Baris 2: Data RGB
    display.setCursor(0, 12);
    display.printf("R:%d G:%d B:%d", data.red, data.green, data.blue);

    // Baris 3: Clear value
    display.setCursor(0, 24);
    display.printf("Clear: %d", data.clear);

    // Baris 4: Status deteksi tomat
    display.setCursor(0, 36);
    display.setTextSize(1);

    // Algoritma sederhana deteksi tomat berdasarkan warna
    if (data.red > data.green && data.red > data.blue && data.red > 100)
    {
        display.print("TOMAT TERDETEKSI");
    }
    else if (data.green > data.red && data.green > data.blue)
    {
        display.print("TOMAT MENTAH");
    }
    else
    {
        display.print("TIDAK ADA TOMAT");
    }

    // Status WiFi
    display.setCursor(0, 48);
    display.setTextSize(1);
    if (WiFi.status() == WL_CONNECTED)
    {
        display.print("WiFi: Connected");
    }
    else
    {
        display.print("WiFi: Disconnected");
    }

    display.display();
}

/**
 * Fungsi untuk menampilkan status pengiriman data
 */
void displayStatus(String message, bool success)
{
    display.clearDisplay();
    display.setTextSize(1);
    display.setTextColor(SSD1306_WHITE);
    display.setCursor(0, 56);

    if (success)
    {
        display.print("✓ " + message);
    }
    else
    {
        display.print("✗ " + message);
    }

    display.display();
    delay(1000); // Tampilkan status selama 1 detik
}

/**
 * Fungsi untuk inisialisasi WiFi
 */
void initWiFi()
{
    Serial.println("Menghubungkan ke WiFi...");
    WiFi.begin(ssid, password);

    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 20)
    {
        delay(1000);
        Serial.print(".");
        attempts++;
    }

    if (WiFi.status() == WL_CONNECTED)
    {
        Serial.println();
        Serial.println("✓ WiFi terhubung!");
        Serial.print("IP Address: ");
        Serial.println(WiFi.localIP());
    }
    else
    {
        Serial.println();
        Serial.println("✗ Gagal terhubung ke WiFi");
    }
}

/**
 * Setup function - dijalankan sekali saat ESP32 dinyalakan
 */
void setup()
{
    // Inisialisasi Serial Monitor
    Serial.begin(115200);
    Serial.println("\n=== SISTEM SENSOR KEMATANGAN TOMAT ===");
    Serial.println("Initializing...");

    // Inisialisasi I2C
    Wire.begin(I2C_SDA_PIN, I2C_SCL_PIN);

    // Inisialisasi WiFi
    initWiFi();

    // Inisialisasi sensor DHT11
    dht.begin();
    Serial.println("✓ DHT11 sensor initialized");

    // Inisialisasi sensor TCS34725
    if (colorSensor.begin())
    {
        Serial.println("✓ TCS34725 color sensor initialized");
    }
    else
    {
        Serial.println("✗ TCS34725 color sensor not detected!");
        while (1)
        {
            delay(1000);
            Serial.println("Periksa koneksi sensor TCS34725...");
        }
    }

    // Inisialisasi OLED display
    if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C))
    {
        Serial.println("✗ OLED display initialization failed!");
        while (1)
        {
            delay(1000);
            Serial.println("Periksa koneksi OLED display...");
        }
    }

    Serial.println("✓ OLED display initialized");

    // Tampilkan splash screen
    display.clearDisplay();
    display.setTextSize(2);
    display.setTextColor(SSD1306_WHITE);
    display.setCursor(0, 0);
    display.println("TOMATO");
    display.println("SENSOR");
    display.setTextSize(1);
    display.println("System Ready");
    display.display();
    delay(3000);

    Serial.println("=== SISTEM SIAP BEROPERASI ===");
    Serial.println();
}

/**
 * Loop function - dijalankan berulang-ulang
 */
void loop()
{
    unsigned long currentTime = millis();

    // Baca sensor setiap SENSOR_INTERVAL
    if (currentTime - lastSensorRead >= SENSOR_INTERVAL)
    {
        currentData = readSensors();

        if (currentData.isValid)
        {
            // Tampilkan data di Serial Monitor
            Serial.printf("Suhu: %.1f°C, Kelembaban: %.1f%%, ",
                          currentData.temperature, currentData.humidity);
            Serial.printf("RGB: R=%d G=%d B=%d, Clear=%d\n",
                          currentData.red, currentData.green, currentData.blue, currentData.clear);

            // Tampilkan data di OLED
            displaySensorData(currentData);
        }

        lastSensorRead = currentTime;
    }

    // Kirim data ke server setiap SEND_INTERVAL
    if (currentTime - lastDataSend >= SEND_INTERVAL && currentData.isValid)
    {
        sendDataToServer(currentData);
        lastDataSend = currentTime;
    }

    // Cek koneksi WiFi dan reconnect jika perlu
    if (WiFi.status() != WL_CONNECTED)
    {
        Serial.println("WiFi terputus, mencoba reconnect...");
        initWiFi();
    }

    delay(100); // Small delay untuk stabilitas
}
