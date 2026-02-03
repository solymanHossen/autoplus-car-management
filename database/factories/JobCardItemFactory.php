<?php

namespace Database\Factories;

use App\Models\JobCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobCardItem>
 */
class JobCardItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 10);
        $unitPrice = $this->faker->randomFloat(2, 10, 500);
        
        return [
            'job_card_id' => JobCard::factory(),
            'item_type' => $this->faker->randomElement(['part', 'service']),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => 0,
            'discount' => 0,
            'total' => $quantity * $unitPrice, // Simple calculation for seeded data
            'notes' => $this->faker->sentence(),
        ];
    }
}
