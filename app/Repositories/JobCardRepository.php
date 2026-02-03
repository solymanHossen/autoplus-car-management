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

    public function find(string $id): ?JobCard
    {
        return JobCard::find($id);
    }
}
