<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JobCard;
use App\Models\Payment;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttachmentRequest extends FormRequest
{
    /**
     * Allowed polymorphic targets.
     *
     * @return array<string, class-string>
     */
    public static function attachableMap(): array
    {
        return [
            'job_card' => JobCard::class,
            'vehicle' => Vehicle::class,
            'customer' => Customer::class,
            'invoice' => Invoice::class,
            'payment' => Payment::class,
            'appointment' => Appointment::class,
        ];
    }

    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'attachable_type' => ['required', 'string', Rule::in(array_keys(self::attachableMap()))],
            'attachable_id' => ['required', 'integer', 'min:1'],
            'file' => [
                'required',
                'file',
                'max:5120', // 5MB
                'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt',
            ],
            'file_type' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $alias = (string) $this->input('attachable_type');
            $attachableId = (int) $this->input('attachable_id');
            $tenantId = (string) $this->user()->tenant_id;

            $modelClass = self::attachableMap()[$alias] ?? null;

            if (! $modelClass || $attachableId < 1) {
                return;
            }

            $exists = $modelClass::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($attachableId)
                ->exists();

            if (! $exists) {
                $validator->errors()->add('attachable_id', 'Selected attachable record does not exist in your tenant.');
            }
        });
    }
}
