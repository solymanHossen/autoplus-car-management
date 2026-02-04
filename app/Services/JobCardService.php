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
}
