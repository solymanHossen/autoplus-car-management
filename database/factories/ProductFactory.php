<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    private static int $skuCounter = 10000;

    private static array $parts = [
        'Oil Filter', 'Air Filter', 'Brake Pads', 'Brake Discs', 'Spark Plugs',
        'Battery', 'Wiper Blades', 'Timing Belt', 'Alternator', 'Starter Motor',
        'Clutch Kit', 'Radiator', 'Fuel Pump', 'Water Pump', 'Shock Absorbers',
    ];

    private static array $services = [
        'Oil Change', 'Brake Service', 'Wheel Alignment', 'Tire Rotation',
        'Engine Diagnostics', 'AC Service', 'Transmission Service', 'Battery Check',
        'Inspection Service', 'Cooling System Flush',
    ];

    private static array $categories = [
        'Engine', 'Brakes', 'Electrical', 'Suspension', 'Cooling', 'Fuel System',
        'Transmission', 'Exhaust', 'Filters', 'Maintenance',
    ];

    public function definition(): array
    {
        $type = fake()->randomElement(['part', 'service']);
        $costPrice = fake()->randomFloat(2, 5, 500);
        $markup = fake()->randomFloat(2, 1.2, 2.5);
        $unitPrice = round($costPrice * $markup, 2);

        $name = $type === 'part'
            ? fake()->randomElement(self::$parts)
            : fake()->randomElement(self::$services);

        return [
            'tenant_id' => Tenant::factory(),
            'sku' => strtoupper(substr($type, 0, 3)).'-'.str_pad((string) self::$skuCounter++, 5, '0', STR_PAD_LEFT),
            'name' => $name,
            'name_local' => fake()->optional(0.2)->passthrough(json_encode(['ar' => $name])),
            'type' => $type,
            'category' => fake()->randomElement(self::$categories),
            'unit_price' => $unitPrice,
            'cost_price' => $costPrice,
            'stock_quantity' => $type === 'part' ? fake()->numberBetween(0, 100) : 0,
            'min_stock_level' => $type === 'part' ? fake()->numberBetween(5, 20) : 0,
            'supplier_id' => null,
            'description' => fake()->optional(0.5)->sentence(),
        ];
    }

    public function part(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'part',
            'name' => fake()->randomElement(self::$parts),
            'stock_quantity' => fake()->numberBetween(10, 100),
            'min_stock_level' => fake()->numberBetween(5, 20),
        ]);
    }

    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'service',
            'name' => fake()->randomElement(self::$services),
            'stock_quantity' => 0,
            'min_stock_level' => 0,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'part',
            'stock_quantity' => fake()->numberBetween(0, 5),
            'min_stock_level' => 10,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'part',
            'stock_quantity' => 0,
            'min_stock_level' => fake()->numberBetween(10, 20),
        ]);
    }
}
