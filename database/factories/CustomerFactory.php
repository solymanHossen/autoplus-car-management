<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'name_local' => fake()->optional(0.2)->passthrough(json_encode(['ar' => fake()->name('ar_SA')])),
            'email' => fake()->optional(0.8)->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'phone_alt' => fake()->optional(0.3)->phoneNumber(),
            'address' => fake()->optional(0.7)->streetAddress(),
            'city' => fake()->optional(0.7)->city(),
            'postal_code' => fake()->optional(0.6)->postcode(),
            'national_id' => fake()->optional(0.4)->numerify('##########'),
            'company_name' => fake()->optional(0.2)->company(),
            'preferred_language' => fake()->randomElement(['en', 'ar', 'fr']),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function withCompany(): static
    {
        return $this->state(fn (array $attributes) => [
            'company_name' => fake()->company(),
        ]);
    }

    public function arabic(): static
    {
        return $this->state(fn (array $attributes) => [
            'preferred_language' => 'ar',
            'name_local' => json_encode(['ar' => fake()->name('ar_SA')]),
        ]);
    }
}
