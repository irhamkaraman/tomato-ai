#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <Adafruit_TCS34725.h>
#include <DHT.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <WiFiClientSecure.h>

// Pin Definitions
#define DHTPIN 23     // Pin untuk sensor DHT11
#define DHTTYPE DHT11 // Tipe sensor DHT

// Setup DHT sensor
DHT dht(DHTPIN, DHTTYPE);

// Setup TCS34725 sensor
Adafruit_TCS34725 colorSensor = Adafruit_TCS34725(TCS34725_INTEGRATIONTIME_614MS, TCS34725_GAIN_1X);

// Setup LCD 16x2 I2C - ganti alamat jika perlu (0x27 / 0x3F)
LiquidCrystal_I2C lcd(0x27, 16, 2); // Alamat default 0x27

// WiFi Credentials - GANTI SESUAI JARINGAN ANDA
const char *ssid = "arduino";
const char *password = "12345678";

// Device ID - GANTI SESUAI DATABASE
const char *deviceId = "ESP32_SENSOR_001";

// URL server API - Domain yang sudah di-hosting (GET endpoint) - Dashboard dengan AI Analysis
const char *serverUrl = "https://tomato-ai.lik.my.id/api/dashboard/sensor-data";

// URL untuk development/testing lokal (GET endpoint)
// const char *serverUrl = "http://localhost:8000/api/dashboard/sensor-data";

// URL legacy (Endpoint lama)
// const char *serverUrl = "https://tomato-ai.lik.my.id/api/tomat-readings/sensor-data";

// Interval baca/kirim data
unsigned long lastSensorRead = 0;
unsigned long lastDataSend = 0;
const unsigned long SENSOR_INTERVAL = 2000; // Baca setiap 2 detik
const unsigned long SEND_INTERVAL = 10000;  // Kirim setiap 10 detik

// Struktur data sensor
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
 * Fungsi untuk mengirim data ke server Laravel menggunakan GET method
 */
bool sendDataToServer(SensorData data)
{
    if (WiFi.status() != WL_CONNECTED)
    {
        Serial.println("✗ WiFi tidak terhubung!");
        return false;
    }

    // Buat URL dengan parameter GET
    String getUrl = String(serverUrl) + "?";
    getUrl += "device_id=" + String(deviceId);
    getUrl += "&red_value=" + String(data.red);
    getUrl += "&green_value=" + String(data.green);
    getUrl += "&blue_value=" + String(data.blue);
    getUrl += "&clear_value=" + String(data.clear);
    getUrl += "&temperature=" + String(round(data.temperature * 10) / 10.0, 1);
    getUrl += "&humidity=" + String(round(data.humidity * 10) / 10.0, 1);

    // Debug: Print URL yang digunakan
    Serial.println("GET URL: " + getUrl);
    Serial.println("URL Length: " + String(getUrl.length()));

    // Untuk HTTPS, gunakan WiFiClientSecure
    WiFiClientSecure client;
    client.setInsecure(); // Skip certificate verification (untuk testing)

    HTTPClient http;
    http.begin(client, getUrl);

    // Tambahkan header yang diperlukan
    http.addHeader("Accept", "application/json");
    http.addHeader("User-Agent", "ESP32-TomatoSensor/1.0");
    http.setTimeout(15000); // 15 detik timeout

    // Gunakan GET method
    int httpResponseCode = http.GET();
    bool success = false;

    Serial.println("HTTP Response Code: " + String(httpResponseCode));

    if (httpResponseCode > 0)
    {
        String response = http.getString();
        Serial.println("Server Response: " + response);

        if (httpResponseCode == 200)
        {
            Serial.println("✓ Data berhasil dikirim ke server");
            displayStatus("OK", true);
            success = true;
        }
        else
        {
            Serial.println("✗ Server menolak data: " + String(httpResponseCode));
            displayStatus("Server Err", false);
            success = false;
        }
    }
    else
    {
        // Handle specific error codes
        switch(httpResponseCode) {
            case -1:
                Serial.println("✗ Connection failed");
                break;
            case -2:
                Serial.println("✗ Send header failed");
                break;
            case -3:
                Serial.println("✗ Send payload failed");
                break;
            case -4:
                Serial.println("✗ Not connected");
                break;
            case -5:
                Serial.println("✗ Connection lost");
                break;
            case -6:
                Serial.println("✗ No stream");
                break;
            case -7:
                Serial.println("✗ No HTTP server");
                break;
            case -8:
                Serial.println("✗ Too less RAM");
                break;
            case -9:
                Serial.println("✗ Encoding error");
                break;
            case -10:
                Serial.println("✗ Stream write error");
                break;
            case -11:
                Serial.println("✗ Read timeout");
                break;
            default:
                Serial.println("✗ Unknown error: " + String(httpResponseCode));
        }
        displayStatus("Send Failed", false);
        success = false;
    }

    http.end();
    return success;
}

/**
 * Fungsi untuk membaca data dari semua sensor
 */
SensorData readSensors()
{
    SensorData data;
    data.isValid = true;

    data.temperature = dht.readTemperature();
    data.humidity = dht.readHumidity();

    if (isnan(data.temperature) || isnan(data.humidity))
    {
        Serial.println("⚠ Gagal membaca sensor DHT11");
        data.isValid = false;
        return data;
    }

    uint16_t rawRed, rawGreen, rawBlue, rawClear;
    colorSensor.getRawData(&rawRed, &rawGreen, &rawBlue, &rawClear);

    if (rawClear == 0)
    {
        Serial.println("⚠ Gagal membaca sensor warna TCS34725");
        data.isValid = false;
        return data;
    }

    data.red = map(rawRed, 0, rawClear, 0, 255);
    data.green = map(rawGreen, 0, rawClear, 0, 255);
    data.blue = map(rawBlue, 0, rawClear, 0, 255);
    data.clear = rawClear;

    data.red = constrain(data.red, 0, 255);
    data.green = constrain(data.green, 0, 255);
    data.blue = constrain(data.blue, 0, 255);

    return data;
}

/**
 * Fungsi untuk menampilkan data sensor di LCD 16x2
 */
void displaySensorData(SensorData data)
{
    lcd.clear();

    // Baris pertama: suhu dan kelembaban
    lcd.setCursor(0, 0);
    lcd.printf("T:%.1f H:%.1f", data.temperature, data.humidity);

    // Baris kedua: warna RGB
    lcd.setCursor(0, 1);
    lcd.printf("R:%d G:%d B:%d", data.red, data.green, data.blue);
}

/**
 * Fungsi untuk menampilkan status pengiriman data
 */
void displayStatus(String message, bool success)
{
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print(success ? "✓" : "✗");
    lcd.print(" Sent:");
    lcd.setCursor(0, 1);
    lcd.print(message);
    delay(1000);
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
        Serial.println("\n✓ WiFi terhubung!");
        Serial.print("IP Address: ");
        Serial.println(WiFi.localIP());
    }
    else
    {
        Serial.println("\n✗ Gagal terhubung ke WiFi");
    }
}

/**
 * Setup function
 */
void setup()
{
    Serial.begin(115200);
    Serial.println("=== SISTEM SENSOR KEMATANGAN TOMAT ===");

    Wire.begin(21, 22); // SDA, SCL

    // Inisialisasi LCD
    lcd.begin();
    lcd.backlight();
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("LCD OK");

    // Inisialisasi WiFi
    initWiFi();

    // Inisialisasi DHT11
    dht.begin();
    Serial.println("✓ DHT11 sensor initialized");

    // Inisialisasi TCS34725
    if (colorSensor.begin())
    {
        Serial.println("✓ TCS34725 color sensor initialized");
    }
    else
    {
        Serial.println("✗ TCS34725 tidak ditemukan!");
        while (1)
            ; // Stop program
    }

    delay(2000);
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("System Ready");
    delay(2000);
    Serial.println("=== SISTEM SIAP BEROPERASI ===\n");
}

/**
 * Loop function
 */
void loop()
{
    unsigned long currentTime = millis();

    if (currentTime - lastSensorRead >= SENSOR_INTERVAL)
    {
        currentData = readSensors();
        if (currentData.isValid)
        {
            displaySensorData(currentData);
        }
        lastSensorRead = currentTime;
    }

    if (currentTime - lastDataSend >= SEND_INTERVAL && currentData.isValid)
    {
        bool sendSuccess = sendDataToServer(currentData);
        lastDataSend = currentTime;
    }

    if (WiFi.status() != WL_CONNECTED)
    {
        initWiFi();
    }

    delay(100);
}
