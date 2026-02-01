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
            return $this->errorResponse('Failed to retrieve job cards: '.$e->getMessage(), 500);
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
            return $this->errorResponse('Failed to create job card: '.$e->getMessage(), 500);
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
            return $this->errorResponse('Failed to retrieve job card: '.$e->getMessage(), 500);
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
            return $this->errorResponse('Failed to update job card: '.$e->getMessage(), 500);
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
            return $this->errorResponse('Failed to delete job card: '.$e->getMessage(), 500);
        }
    }

    /**
     * Add an item to the job card.
     */
    public function addItem(Request $request, JobCard $jobCard): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id' => ['nullable', 'integer', 'exists:products,id'],
                'item_type' => ['required', 'string', 'in:service,part'],
                'quantity' => ['required', 'numeric', 'min:0.01'],
                'unit_price' => ['required', 'numeric', 'min:0'],
                'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'discount' => ['nullable', 'numeric', 'min:0'],
                'notes' => ['nullable', 'string'],
            ]);

            // Calculate total
            $subtotal = $validated['quantity'] * $validated['unit_price'];
            $taxAmount = isset($validated['tax_rate']) ? ($subtotal * $validated['tax_rate'] / 100) : 0;
            $discount = $validated['discount'] ?? 0;
            $validated['total'] = $subtotal + $taxAmount - $discount;

            $item = $jobCard->jobCardItems()->create($validated);

            // Recalculate job card totals
            $this->recalculateJobCardTotals($jobCard);

            return $this->successResponse(
                $item,
                'Item added to job card successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to add item: '.$e->getMessage(), 500);
        }
    }

    /**
     * Update job card status.
     */
    public function updateStatus(Request $request, JobCard $jobCard): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update status: '.$e->getMessage(), 500);
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
        // Sum all item totals (which already include tax calculations)
        $total = $jobCard->jobCardItems()->sum('total');

        // Calculate subtotal (without tax)
        $subtotal = $jobCard->jobCardItems()
            ->get()
            ->sum(function ($item) {
                return $item->quantity * $item->unit_price;
            });

        // Calculate total tax amount
        $taxAmount = $jobCard->jobCardItems()
            ->get()
            ->sum(function ($item) {
                $subtotal = $item->quantity * $item->unit_price;

                return $item->tax_rate ? ($subtotal * $item->tax_rate / 100) : 0;
            });

        $totalAmount = $total - ($jobCard->discount_amount ?? 0);

        $jobCard->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => max(0, $totalAmount),
        ]);
    }
}
