<?php

namespace Database\Factories;

use App\Models\TomatReading;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TomatReading>
 */
class TomatReadingFactory extends Factory
{
    protected $model = TomatReading::class;

    public function definition(): array
    {
        // Generate random RGB values based on maturity levels
        $maturityScenarios = [
            'mentah' => [
                'red' => fake()->numberBetween(60, 100),
                'green' => fake()->numberBetween(120, 160),
                'blue' => fake()->numberBetween(60, 80)
            ],
            'setengah_matang' => [
                'red' => fake()->numberBetween(120, 150),
                'green' => fake()->numberBetween(90, 120),
                'blue' => fake()->numberBetween(70, 85)
            ],
            'matang' => [
                'red' => fake()->numberBetween(160, 200),
                'green' => fake()->numberBetween(70, 90),
                'blue' => fake()->numberBetween(60, 75)
            ],
            'busuk' => [
                'red' => fake()->numberBetween(60, 90),
                'green' => fake()->numberBetween(80, 100),
                'blue' => fake()->numberBetween(90, 110)
            ]
        ];

        // Randomly select a maturity scenario
        $selectedScenario = array_rand($maturityScenarios);
        $rgbValues = $maturityScenarios[$selectedScenario];

        // Determine status based on maturity level
        $statuses = [
            'mentah' => 'Belum siap panen',
            'setengah_matang' => 'Siap panen untuk transportasi jauh',
            'matang' => 'Siap konsumsi',
            'busuk' => 'Tidak layak konsumsi'
        ];

        // Generate raw sensor data
        $rawSensorData = [
            'timestamp' => now()->timestamp,
            'sensor_type' => 'TCS34725',
            'integration_time' => fake()->randomElement([2.4, 24, 50, 101, 154, 700]),
            'gain' => fake()->randomElement([1, 4, 16, 60]),
            'raw_readings' => [
                'r' => fake()->numberBetween(1000, 5000),
                'g' => fake()->numberBetween(1000, 5000),
                'b' => fake()->numberBetween(1000, 5000),
                'c' => fake()->numberBetween(3000, 8000)
            ],
            'ambient_light' => fake()->randomFloat(2, 100, 1000),
            'color_temp_k' => fake()->numberBetween(2000, 7000)
        ];

        return [
            'device_id' => fake()->regexify('[A-Z]{6}[0-9]{3}'),
            'red_value' => $rgbValues['red'],
            'green_value' => $rgbValues['green'],
            'blue_value' => $rgbValues['blue'],
            'clear_value' => fake()->numberBetween(400, 600),
            'temperature' => fake()->randomFloat(1, 20, 30),
            'humidity' => fake()->randomFloat(1, 60, 80),
            'maturity_level' => $selectedScenario,
            'confidence_score' => fake()->randomFloat(2, 0.6, 0.95),
            'status' => $statuses[$selectedScenario],
            'raw_sensor_data' => $rawSensorData,
        ];
    }
}
