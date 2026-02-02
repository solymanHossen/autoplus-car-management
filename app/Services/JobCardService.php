<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\JobCard;
use Illuminate\Support\Facades\DB;

class JobCardService
{
    /**
     * Recalculate job card totals based on its items.
     */
    public function recalculateTotals(JobCard $jobCard): JobCard
    {
        return DB::transaction(function () use ($jobCard) {
            $items = $jobCard->jobCardItems()->get(); 

            $subtotal = 0.0;
            $taxAmount = 0.0;
            $itemsTotal = 0.0;

            foreach ($items as $item) {
                // Ensure floating point type casting
                $quantity = (float) $item->quantity;
                $unitPrice = (float) $item->unit_price;
                
                $lineSubtotal = $quantity * $unitPrice;
                
                $lineTax = 0.0;
                // Handle potential division by zero or null tax_rate
                if ($item->tax_rate !== null && $item->tax_rate != 0) {
                    $lineTax = round($lineSubtotal * ($item->tax_rate / 100), 2);
                }

                $discount = (float) ($item->discount ?? 0);
                
                $lineTotal = round($lineSubtotal + $lineTax - $discount, 2);

                $subtotal += $lineSubtotal;
                $taxAmount += $lineTax;
                $itemsTotal += $lineTotal;
            }

            // JobCard level discount
            $jobCardDiscount = (float) ($jobCard->discount_amount ?? 0);
            $finalTotal = round($itemsTotal - $jobCardDiscount, 2);

            $jobCard->update([
                'subtotal' => round($subtotal, 2),
                'tax_amount' => round($taxAmount, 2),
                'total_amount' => max(0, $finalTotal),
            ]);
            
            return $jobCard;
        });
    }
}
