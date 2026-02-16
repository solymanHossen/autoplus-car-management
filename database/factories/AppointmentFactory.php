<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    private static array $serviceTypes = [
        'Oil Change',
        'Brake Service',
        'General Inspection',
        'Tire Replacement',
        'Engine Diagnostics',
        'AC Service',
        'Wheel Alignment',
        'Battery Replacement',
        'Transmission Service',
        'Scheduled Maintenance',
    ];

    public function definition(): array
    {
        $appointmentDate = fake()->dateTimeBetween('-1 month', '+2 months');
        $startHour = fake()->numberBetween(8, 16);
        $startTime = sprintf('%02d:00:00', $startHour);
        $endTime = sprintf('%02d:00:00', $startHour + fake()->numberBetween(1, 3));

        $status = fake()->randomElement(['pending', 'confirmed', 'in_progress', 'completed', 'cancelled']);
        $confirmedAt = in_array($status, ['confirmed', 'in_progress', 'completed'])
            ? fake()->dateTimeBetween('-30 days', 'now')
            : null;

        return [
            'tenant_id' => Tenant::factory(),
            'customer_id' => fn (array $attributes) => Customer::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'vehicle_id' => fn (array $attributes) => Vehicle::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
                'customer_id' => $attributes['customer_id'],
            ])->id,
            'appointment_date' => $appointmentDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'service_type' => fake()->randomElement(self::$serviceTypes),
            'status' => $status,
            'notes' => fake()->optional(0.4)->sentence(),
            'confirmed_at' => $confirmedAt,
            'confirmed_by' => fn (array $attributes) => $attributes['confirmed_at'] !== null
                ? User::factory()->create(['tenant_id' => $attributes['tenant_id']])->id
                : null,
            'created_at' => fake()->dateTimeBetween('-2 months', 'now'),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'confirmed_at' => null,
            'confirmed_by' => null,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'confirmed_at' => fake()->dateTimeBetween('-5 days', 'now'),
            'confirmed_by' => User::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'appointment_date' => now()->toDateString(),
            'confirmed_at' => fake()->dateTimeBetween('-2 days', 'now'),
            'confirmed_by' => User::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'appointment_date' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'confirmed_at' => fake()->dateTimeBetween('-35 days', '-2 days'),
            'confirmed_by' => User::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'appointment_date' => fake()->dateTimeBetween('now', '+30 days'),
            'status' => fake()->randomElement(['pending', 'confirmed']),
        ]);
    }
}
