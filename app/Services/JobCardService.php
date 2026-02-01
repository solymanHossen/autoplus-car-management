<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\JobCard;

class JobCardService
{
    /**
     * Recalculate job card totals based on its items.
     */
    public function recalculateTotals(JobCard $jobCard): JobCard
    {
        $items = $jobCard->jobCardItems()->get(); // Load once to avoid N+1 in loops if calling generic getters

        // Calculate subtotal (without tax)
        $subtotal = $items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        // Calculate total tax amount
        $taxAmount = $items->sum(function ($item) {
            $itemSubtotal = $item->quantity * $item->unit_price;
            return $item->tax_rate ? ($itemSubtotal * $item->tax_rate / 100) : 0;
        });

        // Sum Item Totals (this logic in controller was: sum('total'))
        // Let's verify compatibility. item->total in DB might differ if not updated correctly.
        // It's safer to recalculate everything from raw input or trust the stored item->total.
        // Controller trusted stored item->total. Let's recalculate for safety/consistency.
        // Item total = sub + tax - discount
        
        $itemsTotal = $items->sum(function ($item) {
             // If item has a 'total' property that is reliable:
             return $item->total;
        });
        
        // However, the controller logic:
        // $total = $jobCard->jobCardItems()->sum('total');
        // Let's mimic that but more efficiently.
        $totalFromItems = $items->sum('total');

        $finalTotal = $totalFromItems - ($jobCard->discount_amount ?? 0);

        $jobCard->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => max(0, $finalTotal),
        ]);
        
        return $jobCard;
    }
}
