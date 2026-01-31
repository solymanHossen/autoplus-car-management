<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->company();
        $subdomain = Str::slug($name);

        return [
            'id' => Str::uuid(),
            'name' => $name,
            'domain' => fake()->unique()->domainName(),
            'subdomain' => $subdomain,
            'logo_url' => fake()->optional(0.3)->imageUrl(200, 200, 'business', true),
            'primary_color' => fake()->optional(0.5)->hexColor(),
            'subscription_status' => fake()->randomElement(['active', 'suspended', 'cancelled']),
            'trial_ends_at' => fake()->optional(0.3)->dateTimeBetween('now', '+30 days'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'active',
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'suspended',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'cancelled',
        ]);
    }

    public function onTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'active',
            'trial_ends_at' => fake()->dateTimeBetween('now', '+14 days'),
        ]);
    }
}
