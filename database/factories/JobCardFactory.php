<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\JobCard;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobCard>
 */
class JobCardFactory extends Factory
{
    protected $model = JobCard::class;

    private static int $jobNumberCounter = 1000;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 5000);
        $taxRate = 0.15;
        $discountAmount = fake()->randomFloat(2, 0, $subtotal * 0.2);
        $taxAmount = ($subtotal - $discountAmount) * $taxRate;
        $totalAmount = $subtotal - $discountAmount + $taxAmount;

        $status = fake()->randomElement(['pending', 'diagnosis', 'approval', 'working', 'qc', 'ready', 'delivered']);
        $createdAt = fake()->dateTimeBetween('-3 months', 'now');

        return [
            'tenant_id' => Tenant::factory(),
            'job_number' => 'JOB-'.str_pad((string) self::$jobNumberCounter++, 6, '0', STR_PAD_LEFT),
            'customer_id' => fn (array $attributes) => Customer::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'vehicle_id' => fn (array $attributes) => Vehicle::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
                'customer_id' => $attributes['customer_id'],
            ])->id,
            'assigned_to' => fn (array $attributes) => fake()->boolean(70)
                ? User::factory()->create(['tenant_id' => $attributes['tenant_id']])->id
                : null,
            'status' => $status,
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
            'mileage_in' => fake()->numberBetween(10000, 200000),
            'mileage_out' => fake()->optional(0.3)->numberBetween(10000, 200000),
            'estimated_completion' => fake()->optional(0.6)->dateTimeBetween($createdAt, '+7 days'),
            'actual_completion' => in_array($status, ['ready', 'delivered']) ? fake()->dateTimeBetween($createdAt, 'now') : null,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'customer_notes' => fake()->optional(0.5)->sentence(),
            'internal_notes' => fake()->optional(0.4)->sentence(),
            'diagnosis_notes' => fake()->optional(0.3)->paragraph(),
            'started_at' => in_array($status, ['working', 'qc', 'ready', 'delivered']) ? fake()->dateTimeBetween($createdAt, 'now') : null,
            'completed_at' => in_array($status, ['ready', 'delivered']) ? fake()->dateTimeBetween($createdAt, 'now') : null,
            'delivered_at' => $status === 'delivered' ? fake()->dateTimeBetween($createdAt, 'now') : null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
            'delivered_at' => null,
        ]);
    }

    public function working(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'working',
            'started_at' => fake()->dateTimeBetween('-5 days', 'now'),
            'completed_at' => null,
            'delivered_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ready',
            'started_at' => fake()->dateTimeBetween('-7 days', '-3 days'),
            'completed_at' => fake()->dateTimeBetween('-2 days', 'now'),
            'delivered_at' => null,
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'started_at' => fake()->dateTimeBetween('-10 days', '-5 days'),
            'completed_at' => fake()->dateTimeBetween('-4 days', '-2 days'),
            'delivered_at' => fake()->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
        ]);
    }
}
