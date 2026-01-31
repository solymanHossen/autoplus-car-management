<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $tenant_id
 * @property int $customer_id
 * @property string $registration_number
 * @property string $make
 * @property string $model
 * @property int $year
 * @property string|null $color
 * @property string|null $vin
 * @property string|null $engine_number
 * @property int $current_mileage
 * @property \Illuminate\Support\Carbon|null $last_service_date
 * @property \Illuminate\Support\Carbon|null $next_service_date
 * @property \Illuminate\Support\Carbon|null $purchase_date
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Vehicle extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'registration_number',
        'make',
        'model',
        'year',
        'color',
        'vin',
        'engine_number',
        'current_mileage',
        'last_service_date',
        'next_service_date',
        'purchase_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'current_mileage' => 'integer',
            'last_service_date' => 'date',
            'next_service_date' => 'date',
            'purchase_date' => 'date',
        ];
    }

    /**
     * Get the tenant that owns the vehicle.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the customer that owns the vehicle.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the job cards for the vehicle.
     */
    public function jobCards(): HasMany
    {
        return $this->hasMany(JobCard::class);
    }

    /**
     * Get the service reminders for the vehicle.
     */
    public function serviceReminders(): HasMany
    {
        return $this->hasMany(VehicleServiceReminder::class);
    }

    /**
     * Get the attachments for the vehicle.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get the appointments for the vehicle.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the AI diagnostics for the vehicle.
     */
    public function aiDiagnostics(): HasMany
    {
        return $this->hasMany(AiDiagnostic::class);
    }
}
