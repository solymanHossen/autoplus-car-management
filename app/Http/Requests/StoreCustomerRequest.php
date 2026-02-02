<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'name_local' => ['nullable', 'array'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers')->where(fn ($query) => $query->where('tenant_id', $this->user()->tenant_id)),
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('customers')->where(fn ($query) => $query->where('tenant_id', $this->user()->tenant_id)),
            ],
            'phone_alt' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'preferred_language' => ['nullable', 'string', 'in:en,ar'],
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
            'name.required' => 'Customer name is required',
            'phone.required' => 'Phone number is required',
            'email.email' => 'Please provide a valid email address',
            'preferred_language.in' => 'Preferred language must be either en or ar',
        ];
    }
}
