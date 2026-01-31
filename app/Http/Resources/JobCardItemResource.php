<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\JobCardItem
 */
class JobCardItemResource extends JsonResource
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
            'job_card_id' => $this->job_card_id,
            'item_type' => $this->item_type,
            'description' => $this->description,
            'quantity' => number_format((float) $this->quantity, 2, '.', ''),
            'unit_price' => number_format((float) $this->unit_price, 2, '.', ''),
            'total_price' => number_format((float) $this->total_price, 2, '.', ''),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
