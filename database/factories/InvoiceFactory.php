<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JobCard;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    private static int $invoiceNumberCounter = 10000;

    public function definition(): array
    {
        $invoiceDate = fake()->dateTimeBetween('-6 months', 'now');
        $dueDate = fake()->dateTimeBetween($invoiceDate, '+30 days');

        $subtotal = fake()->randomFloat(2, 100, 10000);
        $taxRate = 0.15;
        $discountAmount = fake()->randomFloat(2, 0, $subtotal * 0.15);
        $taxAmount = ($subtotal - $discountAmount) * $taxRate;
        $totalAmount = $subtotal - $discountAmount + $taxAmount;

        $status = fake()->randomElement(['draft', 'sent', 'paid', 'overdue', 'cancelled']);
        $paidAmount = match ($status) {
            'paid' => $totalAmount,
            'overdue', 'sent' => fake()->randomFloat(2, 0, $totalAmount * 0.5),
            default => 0.0,
        };

        $balance = $totalAmount - $paidAmount;

        return [
            'tenant_id' => Tenant::factory(),
            'invoice_number' => 'INV-'.date('Y', $invoiceDate->getTimestamp()).'-'.str_pad((string) self::$invoiceNumberCounter++, 5, '0', STR_PAD_LEFT),
            'customer_id' => fn (array $attributes) => Customer::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'job_card_id' => fn (array $attributes) => fake()->boolean(70)
                ? JobCard::factory()->create([
                    'tenant_id' => $attributes['tenant_id'],
                    'customer_id' => $attributes['customer_id'],
                ])->id
                : null,
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'status' => $status,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'balance' => $balance,
            'payment_terms' => fake()->optional(0.5)->randomElement([
                'Net 30',
                'Net 15',
                'Due on receipt',
                'Net 60',
                '50% upfront, 50% on completion',
            ]),
            'notes' => fake()->optional(0.3)->sentence(),
            'created_at' => $invoiceDate,
            'updated_at' => $invoiceDate,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'paid_amount' => 0,
            'balance' => $attributes['total_amount'],
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_amount' => $attributes['total_amount'],
            'balance' => 0,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
