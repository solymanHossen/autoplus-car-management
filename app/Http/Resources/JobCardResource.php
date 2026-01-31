<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\JobCard
 */
class JobCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_number' => $this->job_number,
            'customer_id' => $this->customer_id,
            'vehicle_id' => $this->vehicle_id,
            'assigned_to' => $this->assigned_to,
            'status' => $this->status,
            'priority' => $this->priority,
            'mileage_in' => $this->mileage_in,
            'mileage_out' => $this->mileage_out,
            'customer_notes' => $this->customer_notes,
            'internal_notes' => $this->internal_notes,
            'diagnosis_notes' => $this->diagnosis_notes,
            'estimated_completion' => $this->estimated_completion?->toIso8601String(),
            'actual_completion' => $this->actual_completion?->toIso8601String(),
            'subtotal' => number_format((float) $this->subtotal, 2, '.', ''),
            'tax_amount' => number_format((float) $this->tax_amount, 2, '.', ''),
            'discount_amount' => number_format((float) $this->discount_amount, 2, '.', ''),
            'total_amount' => number_format((float) $this->total_amount, 2, '.', ''),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'assigned_user' => new UserResource($this->whenLoaded('assignedTo')),
            'items' => JobCardItemResource::collection($this->whenLoaded('jobCardItems')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
