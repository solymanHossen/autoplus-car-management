<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
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
        $tenantId = $this->user()->tenant_id;

        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('tenant_id', $tenantId),
            ],
            'job_card_id' => [
                'nullable',
                'integer',
                Rule::exists('job_cards', 'id')->where('tenant_id', $tenantId),
            ],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:draft,sent,paid,partially_paid,overdue,cancelled'],
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
            'job_card_id.exists' => 'Selected job card does not exist',
            'invoice_date.required' => 'Invoice date is required',
            'due_date.required' => 'Due date is required',
            'due_date.after_or_equal' => 'Due date must be on or after the invoice date',
            'subtotal.required' => 'Subtotal is required',
            'subtotal.min' => 'Subtotal cannot be negative',
            'tax_amount.required' => 'Tax amount is required',
            'tax_amount.min' => 'Tax amount cannot be negative',
            'discount_amount.min' => 'Discount amount cannot be negative',
            'total_amount.required' => 'Total amount is required',
            'total_amount.min' => 'Total amount cannot be negative',
            'status.required' => 'Status is required',
            'status.in' => 'Invalid status value',
        ];
    }
}
