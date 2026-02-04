<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreJobCardItemRequest;
use App\Http\Requests\StoreJobCardRequest;
use App\Http\Requests\UpdateJobCardRequest;
use App\Http\Resources\JobCardResource;
use App\Models\JobCard;
use App\Repositories\JobCardRepository;
use App\Services\JobCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Job Card API Controller
 */
class JobCardController extends ApiController
{
    public function __construct(
        protected JobCardService $jobCardService,
        protected JobCardRepository $jobCardRepository
    ) {}

    /**
     * Display a listing of job cards.
     */
    public function index(): JsonResponse
    {
        $jobCards = $this->jobCardRepository->getPaginatedList();

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
        $jobCard = $this->jobCardService->createJobCard($request->validated());

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

        // Use round() at every step to ensure financial precision to 2 decimal places.
        // This prevents floating point errors (e.g., 19.9999998).
        $quantity = $validated['quantity'];
        $unitPrice = $validated['unit_price'];

        $subtotal = round($quantity * $unitPrice, 2);
        
        $taxRate = $validated['tax_rate'] ?? 0;
        $taxAmount = 0;
        
        if ($taxRate > 0) {
            $taxAmount = round(($subtotal * $taxRate) / 100, 2);
        }

        $discount = isset($validated['discount']) ? round((float) $validated['discount'], 2) : 0;
        
        $validated['total'] = round($subtotal + $taxAmount - $discount, 2);

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
}
