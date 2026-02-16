<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $vehicleId = $this->route('vehicle');
        $vehicleId = is_object($vehicleId) ? $vehicleId->id : $vehicleId;
        $tenantId = $this->user()->tenant_id;

        return [
            'customer_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('tenant_id', $tenantId),
            ],
            'registration_number' => ['sometimes', 'required', 'string', 'max:50'],
            'make' => ['sometimes', 'required', 'string', 'max:100'],
            'model' => ['sometimes', 'required', 'string', 'max:100'],
            'year' => ['sometimes', 'required', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'color' => ['nullable', 'string', 'max:50'],
            'vin' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('vehicles', 'vin')
                    ->ignore($vehicleId)
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'engine_number' => ['nullable', 'string', 'max:100'],
            'current_mileage' => ['sometimes', 'required', 'integer', 'min:0'],
            'last_service_date' => ['nullable', 'date'],
            'next_service_date' => ['nullable', 'date', 'after_or_equal:today'],
            'purchase_date' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer is required',
            'customer_id.exists' => 'Selected customer does not exist',
            'registration_number.required' => 'Registration number is required',
            'make.required' => 'Vehicle make is required',
            'model.required' => 'Vehicle model is required',
            'year.required' => 'Year is required',
            'year.min' => 'Year must be 1900 or later',
            'year.max' => 'Year cannot be in the future',
            'vin.unique' => 'This VIN is already registered',
            'current_mileage.required' => 'Current mileage is required',
            'current_mileage.min' => 'Mileage cannot be negative',
            'next_service_date.after_or_equal' => 'Next service date cannot be in the past',
            'purchase_date.before_or_equal' => 'Purchase date cannot be in the future',
        ];
    }
}
