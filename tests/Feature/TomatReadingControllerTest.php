<?php

namespace Tests\Feature;

use App\Models\TomatReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TomatReadingControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test untuk endpoint store tomat reading
     */
    public function test_can_store_tomat_reading()
    {
        // Create a device first
        \App\Models\Device::create([
            'device_id' => 'DEVICE456',
            'name' => 'Test Device'
        ]);

        $data = [
            'red_value' => 180,
            'green_value' => 75,
            'blue_value' => 60,
            'clear_value' => 500,
            'temperature' => 25.5,
            'humidity' => 65.5,
            'device_id' => 'DEVICE456'
        ];

        $response = $this->postJson('/api/tomat-readings', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'red_value',
                    'green_value',
                    'blue_value',
                    'clear_value',
                    'temperature',
                    'humidity',
                    'maturity_level',
                    'confidence_score',
                    'status',
                    'created_at'
                ],
                'recommendations',
                'analysis'
            ]);

        $this->assertDatabaseHas('tomat_readings', [
            'device_id' => 'DEVICE456',
            'red_value' => 180,
            'green_value' => 75,
            'blue_value' => 60,
            'clear_value' => 500,
            'temperature' => 25.5,
            'humidity' => 65.5
        ]);
    }

    /**
     * Test untuk endpoint mendapatkan riwayat pembacaan
     */
    public function test_can_get_tomat_readings_history()
    {
        // Buat beberapa sample data menggunakan factory
        TomatReading::factory()->count(5)->create();

        $response = $this->getJson('/api/tomat-readings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'red_value',
                        'green_value',
                        'blue_value',
                        'clear_value',
                        'temperature',
                        'humidity',
                        'maturity_level',
                        'confidence_score',
                        'status',
                        'created_at'
                    ]
                ]
            ]);
    }

    /**
     * Test untuk endpoint detail pembacaan
     */
    public function test_can_get_tomat_reading_detail()
    {
        $reading = TomatReading::factory()->create([
            'red_value' => 150,
            'green_value' => 90,
            'blue_value' => 70,
            'clear_value' => 500,
            'temperature' => 25.5,
            'humidity' => 65.5,
            'device_id' => 'DEVICE456',
        ]);

        $response = $this->getJson('/api/tomat-readings/' . $reading->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'red_value',
                    'green_value',
                    'blue_value',
                    'clear_value',
                    'temperature',
                    'humidity',
                    'maturity_level',
                    'confidence_score',
                    'status',
                    'device_id',
                    'created_at'
                ],
                'recommendations',
                'analysis'
            ]);
    }

    /**
     * Test untuk validasi input
     */
    public function test_validation_errors_when_storing_tomat_reading()
    {
        // Data dengan nilai RGB yang tidak valid
        $data = [
            'red_value' => 300, // Melebihi 255
            'green_value' => -10, // Negatif
            'blue_value' => 'tidak valid', // Bukan angka
            'clear_value' => -1, // Negatif
            'temperature' => 'invalid', // Bukan angka
            'humidity' => 'invalid', // Bukan angka
            'device_id' => 'DEVICE456'
        ];

        $response = $this->postJson('/api/tomat-readings', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['red_value', 'green_value', 'blue_value', 'clear_value', 'temperature', 'humidity']);
    }
}
