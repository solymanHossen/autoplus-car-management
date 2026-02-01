<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
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
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['required', 'date_format:H:i:s', 'after:start_time'],
            'service_type' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:pending,scheduled,confirmed,in_progress,completed,cancelled,no_show'],
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
            'appointment_date.required' => 'Appointment date is required',
            'appointment_date.after_or_equal' => 'Appointment date cannot be in the past',
            'start_time.required' => 'Start time is required',
            'start_time.date_format' => 'Start time must be in format HH:MM:SS',
            'end_time.required' => 'End time is required',
            'end_time.date_format' => 'End time must be in format HH:MM:SS',
            'end_time.after' => 'End time must be after start time',
            'service_type.required' => 'Service type is required',
            'status.required' => 'Status is required',
            'status.in' => 'Invalid status value',
        ];
    }
}
