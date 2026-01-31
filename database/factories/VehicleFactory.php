<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    private static array $makes = [
        'Toyota', 'Honda', 'Ford', 'Chevrolet', 'Nissan', 'BMW', 'Mercedes-Benz',
        'Audi', 'Volkswagen', 'Hyundai', 'Kia', 'Mazda', 'Subaru', 'Lexus', 'Jeep'
    ];

    private static array $colors = [
        'White', 'Black', 'Silver', 'Gray', 'Red', 'Blue', 'Green', 'Yellow', 'Orange', 'Brown'
    ];

    public function definition(): array
    {
        $make = fake()->randomElement(self::$makes);
        $year = fake()->numberBetween(2010, 2024);

        return [
            'tenant_id' => Tenant::factory(),
            'customer_id' => Customer::factory(),
            'registration_number' => strtoupper(fake()->bothify('???-####')),
            'make' => $make,
            'model' => $this->generateModel($make),
            'year' => $year,
            'color' => fake()->optional(0.8)->randomElement(self::$colors),
            'vin' => fake()->optional(0.7)->regexify('[A-HJ-NPR-Z0-9]{17}'),
            'engine_number' => fake()->optional(0.6)->regexify('[A-Z0-9]{10,14}'),
            'current_mileage' => fake()->numberBetween(5000, 250000),
            'last_service_date' => fake()->optional(0.6)->dateTimeBetween('-6 months', 'now'),
            'next_service_date' => fake()->optional(0.5)->dateTimeBetween('now', '+3 months'),
            'purchase_date' => fake()->optional(0.4)->dateTimeBetween("-{$year} years", 'now'),
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    private function generateModel(string $make): string
    {
        $models = [
            'Toyota' => ['Camry', 'Corolla', 'RAV4', 'Highlander', 'Tacoma', 'Prius'],
            'Honda' => ['Civic', 'Accord', 'CR-V', 'Pilot', 'Fit', 'HR-V'],
            'Ford' => ['F-150', 'Mustang', 'Explorer', 'Escape', 'Focus', 'Edge'],
            'BMW' => ['3 Series', '5 Series', 'X3', 'X5', '7 Series', 'X1'],
            'Mercedes-Benz' => ['C-Class', 'E-Class', 'GLC', 'GLE', 'S-Class', 'A-Class'],
            'Audi' => ['A4', 'A6', 'Q5', 'Q7', 'A3', 'Q3'],
        ];

        return isset($models[$make]) ? fake()->randomElement($models[$make]) : fake()->word();
    }

    public function newVehicle(): static
    {
        return $this->state(fn (array $attributes) => [
            'year' => fake()->numberBetween(2022, 2024),
            'current_mileage' => fake()->numberBetween(1000, 15000),
        ]);
    }

    public function highMileage(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_mileage' => fake()->numberBetween(150000, 300000),
        ]);
    }
}
