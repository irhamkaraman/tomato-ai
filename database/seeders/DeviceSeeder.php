<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Device;

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default devices for the system
        $devices = [
            [
                'device_id' => 'WEB_SENSOR_TEST',
                'name' => 'Web Sensor Testing Interface',
                'location' => 'Web Dashboard'
            ],
            [
                'device_id' => 'ESP32_SENSOR_01',
                'name' => 'ESP32 Sensor Unit 01',
                'location' => 'Greenhouse A'
            ],
            [
                'device_id' => 'ESP32_SENSOR_001',
                'name' => 'ESP32 Sensor Unit 001 dari ESP32',
                'location' => 'ESP32'
            ],
            [
                'device_id' => 'ESP32_SENSOR_001',
                'name' => 'ESP32 Sensor Unit 001',
                'location' => 'Main Greenhouse'
            ],
            [
                'device_id' => 'ESP32_SENSOR_02',
                'name' => 'ESP32 Sensor Unit 02',
                'location' => 'Greenhouse B'
            ]
        ];

        foreach ($devices as $device) {
            Device::updateOrCreate(
                ['device_id' => $device['device_id']],
                $device
            );
        }

        $this->command->info('Device seeder completed successfully!');
        $this->command->info('Created devices: ' . implode(', ', array_column($devices, 'device_id')));
    }
}
