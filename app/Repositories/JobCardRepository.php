<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\JobCard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\QueryBuilder;

class JobCardRepository
{
    public function getPaginatedList(int $perPage = 15): LengthAwarePaginator
    {
        return QueryBuilder::for(JobCard::class)
            ->allowedFilters(['status', 'priority', 'customer_id', 'vehicle_id', 'assigned_to'])
            ->allowedSorts(['job_number', 'status', 'priority', 'created_at', 'estimated_completion'])
            ->allowedIncludes(['customer', 'vehicle', 'assignedTo', 'jobCardItems'])
            ->paginate($perPage);
    }

    public function create(array $data): JobCard
    {
        return JobCard::create($data);
    }

    public function generateNextJobNumber(): string
    {
        $prefix = 'JOB';
        $year = date('Y');
        $month = date('m');

        // Use lockForUpdate to prevent race conditions
        $lastJob = JobCard::where('job_number', 'LIKE', "{$prefix}-{$year}{$month}%")
            ->orderBy('job_number', 'desc')
            ->lockForUpdate()
            ->first();

        if ($lastJob) {
            $lastNumber = (int) substr($lastJob->job_number, -4);
            $newNumber = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}-{$year}{$month}{$newNumber}";
    }

    public function find(string $id): ?JobCard
    {
        return JobCard::find($id);
    }
}
