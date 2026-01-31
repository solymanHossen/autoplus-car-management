<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Customer
 */
class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_local' => $this->name_local,
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_alt' => $this->phone_alt,
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'national_id' => $this->national_id,
            'company_name' => $this->company_name,
            'preferred_language' => $this->preferred_language,
            'notes' => $this->notes,
            'vehicles_count' => $this->whenCounted('vehicles'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
