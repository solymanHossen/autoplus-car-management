<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\JobCard\RecalculateJobCardTotalsAction;
use App\Models\JobCard;
use App\Repositories\JobCardRepository;
use Illuminate\Support\Facades\DB;

class JobCardService
{
    public function __construct(
        protected RecalculateJobCardTotalsAction $recalculateTotalsAction,
        protected JobCardRepository $jobCardRepository
    ) {}

    /**
     * Create a new job card with a unique job number.
     */
    public function createJobCard(array $data): JobCard
    {
        return DB::transaction(function () use ($data) {
            // Generate a unique job number safely within the transaction lock
            $data['job_number'] = $this->jobCardRepository->generateNextJobNumber();

            // Create the job card
            return $this->jobCardRepository->create($data);
        });
    }

    /**
     * Recalculate job card totals based on its items.
     * Uses integer arithmetic (cents) to avoid floating point precision errors.
     */
    public function recalculateTotals(JobCard $jobCard): JobCard
    {
        return $this->recalculateTotalsAction->execute($jobCard);
    }

    /**
     * Update an existing job card.
     */
    public function updateJobCard(JobCard $jobCard, array $data): JobCard
    {
        $jobCard->update($data);

        return $jobCard;
    }

    /**
     * Add a line item to a job card and recalculate totals.
     */
    public function addJobCardItem(JobCard $jobCard, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Use round() at every step to ensure financial precision to 2 decimal places.
        // This prevents floating point errors (e.g., 19.9999998).
        $quantity = $data['quantity'];
        $unitPrice = $data['unit_price'];

        $subtotal = round($quantity * $unitPrice, 2);

        $taxRate = $data['tax_rate'] ?? 0;
        $taxAmount = 0;

        if ($taxRate > 0) {
            $taxAmount = round(($subtotal * $taxRate) / 100, 2);
        }

        $discount = isset($data['discount']) ? round((float) $data['discount'], 2) : 0;

        $data['total'] = round($subtotal + $taxAmount - $discount, 2);

        $item = $jobCard->jobCardItems()->create($data);

        // Recalculate job card totals
        $this->recalculateTotals($jobCard);

        return $item;
    }
}
