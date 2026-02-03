<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\JobCard\RecalculateJobCardTotalsAction;
use App\Models\JobCard;

class JobCardService
{
    public function __construct(
        protected RecalculateJobCardTotalsAction $recalculateTotalsAction
    ) {}

    /**
     * Recalculate job card totals based on its items.
     * Uses integer arithmetic (cents) to avoid floating point precision errors.
     */
    public function recalculateTotals(JobCard $jobCard): JobCard
    {
        return $this->recalculateTotalsAction->execute($jobCard);
    }
}
