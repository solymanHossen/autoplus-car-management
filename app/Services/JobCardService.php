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
                // Ensure floating point precision usage or use bcmath if requested (using round for currency standard)
                $lineSubtotal = (float) $item->quantity * (float) $item->unit_price;
                
                $lineTax = 0.0;
                if ($item->tax_rate) {
                    $lineTax = round($lineSubtotal * ($item->tax_rate / 100), 2);
                }

                $lineDiscount = (float) ($item->discount ?? 0);
                
                $lineTotal = round($lineSubtotal + $lineTax - $lineDiscount, 2);

                $subtotal += $lineSubtotal;
                $taxAmount += $lineTax;
                $itemsTotal += $lineTotal;
            }

            // JobCard level discount
            $finalTotal = round($itemsTotal - ($jobCard->discount_amount ?? 0), 2);

            $jobCard->update([
                'subtotal' => round($subtotal, 2),
                'tax_amount' => round($taxAmount, 2),
                'total_amount' => max(0, $finalTotal),
            ]);
            
            return $jobCard;
        });
    }
}
