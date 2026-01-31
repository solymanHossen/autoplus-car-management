<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreJobCardRequest;
use App\Http\Requests\UpdateJobCardRequest;
use App\Http\Resources\JobCardResource;
use App\Models\JobCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Job Card API Controller
 */
class JobCardController extends ApiController
{
    /**
     * Display a listing of job cards.
     */
    public function index(): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve job cards: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created job card.
     */
    public function store(StoreJobCardRequest $request): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create job card: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified job card.
     */
    public function show(JobCard $jobCard): JsonResponse
    {
        try {
            $jobCard->load(['customer', 'vehicle', 'assignedTo', 'jobCardItems']);
            
            return $this->successResponse(
                new JobCardResource($jobCard),
                'Job card retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve job card: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified job card.
     */
    public function update(UpdateJobCardRequest $request, JobCard $jobCard): JsonResponse
    {
        try {
            $jobCard->update($request->validated());

            return $this->successResponse(
                new JobCardResource($jobCard->fresh()->load(['customer', 'vehicle', 'assignedTo'])),
                'Job card updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update job card: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified job card.
     */
    public function destroy(JobCard $jobCard): JsonResponse
    {
        try {
            $jobCard->delete();

            return $this->successResponse(
                null,
                'Job card deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete job card: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add an item to the job card.
     */
    public function addItem(Request $request, JobCard $jobCard): JsonResponse
    {
        try {
            $validated = $request->validate([
                'item_type' => ['required', 'string', 'in:service,part'],
                'description' => ['required', 'string'],
                'quantity' => ['required', 'numeric', 'min:0.01'],
                'unit_price' => ['required', 'numeric', 'min:0'],
            ]);

            $validated['total_price'] = $validated['quantity'] * $validated['unit_price'];
            
            $item = $jobCard->jobCardItems()->create($validated);

            // Recalculate job card totals
            $this->recalculateJobCardTotals($jobCard);

            return $this->successResponse(
                $item,
                'Item added to job card successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to add item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update job card status.
     */
    public function updateStatus(Request $request, JobCard $jobCard): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => ['required', 'string', 'in:pending,in_progress,completed,on_hold,cancelled'],
            ]);

            $jobCard->update(['status' => $validated['status']]);

            // Set timestamps based on status
            if ($validated['status'] === 'in_progress' && !$jobCard->started_at) {
                $jobCard->update(['started_at' => now()]);
            } elseif ($validated['status'] === 'completed' && !$jobCard->completed_at) {
                $jobCard->update(['completed_at' => now()]);
            }

            return $this->successResponse(
                new JobCardResource($jobCard->fresh()),
                'Job card status updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update status: ' . $e->getMessage(), 500);
        }
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

    /**
     * Recalculate job card totals.
     */
    private function recalculateJobCardTotals(JobCard $jobCard): void
    {
        $subtotal = $jobCard->jobCardItems()->sum('total_price');
        $taxRate = 0.15; // 15% tax rate
        $taxAmount = $subtotal * $taxRate;
        $totalAmount = $subtotal + $taxAmount - ($jobCard->discount_amount ?? 0);

        $jobCard->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => max(0, $totalAmount),
        ]);
    }
}
