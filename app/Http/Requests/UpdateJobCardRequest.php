<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobCardRequest extends FormRequest
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
        return [
            'customer_id' => ['sometimes', 'required', 'integer', 'exists:customers,id'],
            'vehicle_id' => ['sometimes', 'required', 'integer', 'exists:vehicles,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['sometimes', 'required', 'string', 'in:pending,in_progress,completed,on_hold,cancelled'],
            'priority' => ['sometimes', 'required', 'string', 'in:low,medium,high,urgent'],
            'mileage_in' => ['nullable', 'integer', 'min:0'],
            'mileage_out' => ['nullable', 'integer', 'min:0'],
            'customer_notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'diagnosis_notes' => ['nullable', 'string'],
            'estimated_completion' => ['nullable', 'date'],
            'actual_completion' => ['nullable', 'date'],
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
            'vehicle_id.required' => 'Vehicle is required',
            'vehicle_id.exists' => 'Selected vehicle does not exist',
            'assigned_to.exists' => 'Selected user does not exist',
            'status.required' => 'Status is required',
            'status.in' => 'Invalid status value',
            'priority.required' => 'Priority is required',
            'priority.in' => 'Invalid priority value',
            'mileage_in.min' => 'Mileage in cannot be negative',
            'mileage_out.min' => 'Mileage out cannot be negative',
        ];
    }
}
