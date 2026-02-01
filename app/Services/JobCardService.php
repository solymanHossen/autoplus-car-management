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
        $items = $jobCard->jobCardItems()->get(); 

        $subtotal = 0;
        $taxAmount = 0;
        $itemsTotal = 0;

        foreach ($items as $item) {
            $lineSubtotal = $item->quantity * $item->unit_price;
            $lineTax = $item->tax_rate ? ($lineSubtotal * $item->tax_rate / 100) : 0;
            $lineDiscount = $item->discount ?? 0;
            
            $lineTotal = $lineSubtotal + $lineTax - $lineDiscount;

            $subtotal += $lineSubtotal;
            $taxAmount += $lineTax;
            $itemsTotal += $lineTotal;
        }

        // JobCard level discount
        $finalTotal = $itemsTotal - ($jobCard->discount_amount ?? 0);

        $jobCard->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => max(0, $finalTotal),
        ]);
        
        return $jobCard;
    }
}
