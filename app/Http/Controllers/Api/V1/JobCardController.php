<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreJobCardItemRequest;
use App\Http\Requests\StoreJobCardRequest;
use App\Http\Requests\UpdateJobCardRequest;
use App\Http\Resources\JobCardResource;
use App\Models\JobCard;
use App\Services\JobCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Job Card API Controller
 */
class JobCardController extends ApiController
{
    public function __construct(
        protected JobCardService $jobCardService
    ) {}

    /**
     * Display a listing of job cards.
     */
    public function index(): JsonResponse
    {
        $jobCards = QueryBuilder::for(JobCard::class)
            ->allowedFilters(['status', 'priority', 'customer_id', 'vehicle_id', 'assigned_to'])
            ->allowedSorts(['job_number', 'status', 'priority', 'created_at', 'estimated_completion'])
            ->allowedIncludes(['customer', 'vehicle', 'assignedTo', 'jobCardItems'])
            ->paginate(15);

        return $this->paginatedResponse(
            $jobCards,
            JobCardResource::class,
            'Job cards retrieved successfully'
        );
    }

    /**
     * Store a newly created job card.
     */
    public function store(StoreJobCardRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['job_number'] = $this->generateJobNumber();

        // Initialize amounts to zero
        $data['subtotal'] = $data['subtotal'] ?? 0;
        $data['tax_amount'] = $data['tax_amount'] ?? 0;
        $data['discount_amount'] = $data['discount_amount'] ?? 0;
        $data['total_amount'] = $data['total_amount'] ?? 0;

        $jobCard = JobCard::create($data);
        $jobCard->load(['customer', 'vehicle', 'assignedTo']);

        return $this->successResponse(
            new JobCardResource($jobCard),
            'Job card created successfully',
            201
        );
    }

    /**
     * Display the specified job card.
     */
    public function show(JobCard $jobCard): JsonResponse
    {
        $jobCard->load(['customer', 'vehicle', 'assignedTo', 'jobCardItems']);

        return $this->successResponse(
            new JobCardResource($jobCard),
            'Job card retrieved successfully'
        );
    }

    /**
     * Update the specified job card.
     */
    public function update(UpdateJobCardRequest $request, JobCard $jobCard): JsonResponse
    {
        $jobCard->update($request->validated());

        return $this->successResponse(
            new JobCardResource($jobCard->fresh()->load(['customer', 'vehicle', 'assignedTo'])),
            'Job card updated successfully'
        );
    }

    /**
     * Remove the specified job card.
     */
    public function destroy(JobCard $jobCard): JsonResponse
    {
        $jobCard->delete();

        return $this->successResponse(
            null,
            'Job card deleted successfully'
        );
    }

    /**
     * Add an item to the job card.
     */
    public function addItem(StoreJobCardItemRequest $request, JobCard $jobCard): JsonResponse
    {
        $validated = $request->validated();

        // Calculate total
        $subtotal = $validated['quantity'] * $validated['unit_price'];
        $taxAmount = isset($validated['tax_rate']) ? ($subtotal * $validated['tax_rate'] / 100) : 0;
        $discount = $validated['discount'] ?? 0;
        $validated['total'] = $subtotal + $taxAmount - $discount;

        $item = $jobCard->jobCardItems()->create($validated);

        // Recalculate job card totals
        $this->jobCardService->recalculateTotals($jobCard);

        return $this->successResponse(
            $item,
            'Item added to job card successfully',
            201
        );
    }

    /**
     * Update job card status.
     */
    public function updateStatus(Request $request, JobCard $jobCard): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,diagnosis,approval,working,qc,ready,delivered,on_hold,cancelled'],
        ]);

        $jobCard->update(['status' => $validated['status']]);

        // Set timestamps based on status
        if ($validated['status'] === 'working' && ! $jobCard->started_at) {
            $jobCard->update(['started_at' => now()]);
        } elseif ($validated['status'] === 'ready' && ! $jobCard->completed_at) {
            $jobCard->update(['completed_at' => now()]);
        }

        return $this->successResponse(
            new JobCardResource($jobCard->fresh()),
            'Job card status updated successfully'
        );
    }

    /**
     * Generate a unique job number.
     */
    private function generateJobNumber(): string
    {
        $prefix = 'JOB';
        $year = date('Y');
        $month = date('m');

        $lastJob = JobCard::where('job_number', 'LIKE', "{$prefix}-{$year}{$month}%")
            ->orderBy('job_number', 'desc')
            ->first();

        if ($lastJob) {
            $lastNumber = (int) substr($lastJob->job_number, -4);
            $newNumber = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}-{$year}{$month}{$newNumber}";
    }
}
