<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
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
            'invoice_id' => [
                'required',
                'integer',
                Rule::exists('invoices', 'id')->where('tenant_id', $tenantId),
            ],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'in:cash,card,bank_transfer,mobile_payment,other'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'received_by' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('tenant_id', $tenantId),
            ],
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
            'invoice_id.required' => 'Invoice is required',
            'invoice_id.exists' => 'Selected invoice does not exist',
            'payment_date.required' => 'Payment date is required',
            'amount.required' => 'Payment amount is required',
            'amount.min' => 'Payment amount must be at least 0.01',
            'payment_method.required' => 'Payment method is required',
            'payment_method.in' => 'Invalid payment method',
            'received_by.exists' => 'Selected user does not exist',
        ];
    }
}
