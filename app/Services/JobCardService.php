<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\JobCard;
use Illuminate\Support\Facades\DB;

class JobCardService
{
    /**
     * Recalculate job card totals based on its items.
     * Uses integer arithmetic (cents) to avoid floating point precision errors.
     */
    public function recalculateTotals(JobCard $jobCard): JobCard
    {
        return DB::transaction(function () use ($jobCard) {
            $items = $jobCard->jobCardItems()->get(); 

            // Initialize accumulators in cents (integers)
            $subtotalCents = 0;
            $taxCents = 0;
            $totalCents = 0;

            foreach ($items as $item) {
                // 1. Convert Unit Price to Cents (Integer)
                // Use round() before casting to int to handle float inaccuracies (e.g. 19.99 * 100 = 1998.999...)
                $unitPriceCents = (int) round((float) $item->unit_price * 100);
                
                // 2. Quantity (float) - we support fractional units (e.g., 1.5 hours)
                $quantity = (float) $item->quantity;
                
                // 3. Line Subtotal in Cents = Price (cents) * Quantity
                // Rounding here is crucial because (int)(1.5 * 100) might be 149 due to float precision
                $lineSubtotalCents = (int) round($unitPriceCents * $quantity);
                
                // 4. Calculate Tax for this line
                // Formula: (SubtotalCents * TaxRate) / 100
                $lineTaxCents = 0;
                if ($item->tax_rate !== null && $item->tax_rate != 0) {
                    $taxRate = (float) $item->tax_rate;
                    $lineTaxCents = (int) round(($lineSubtotalCents * $taxRate) / 100);
                }

                // 5. Discount (in currency units, e.g., $5.00)
                $discountAmount = (float) ($item->discount ?? 0);
                $discountCents = (int) round($discountAmount * 100);
                
                // 6. Line Total
                // Logic: Subtotal + Tax - Discount
                $lineTotalCents = $lineSubtotalCents + $lineTaxCents - $discountCents;

                // Accumulate totals
                $subtotalCents += $lineSubtotalCents;
                $taxCents += $lineTaxCents;
                $totalCents += $lineTotalCents;
            }

            // JobCard Level Discount
            $jobCardDiscountAmount = (float) ($jobCard->discount_amount ?? 0);
            $jobCardDiscountCents = (int) round($jobCardDiscountAmount * 100);
            
            // Subtract JobCard discount from accumulated Item totals
            $finalTotalCents = max(0, $totalCents - $jobCardDiscountCents);

            // Convert back to dollars (float) for storage
            $jobCard->update([
                'subtotal' => $subtotalCents / 100,
                'tax_amount' => $taxCents / 100, // NOTE: Sum of individual line taxes
                'total_amount' => $finalTotalCents / 100,
            ]);
            
            return $jobCard;
        });
    }
}
