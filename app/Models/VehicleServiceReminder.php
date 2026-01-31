<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $tenant_id
 * @property int $vehicle_id
 * @property string $reminder_type
 * @property string $description
 * @property int|null $due_mileage
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class VehicleServiceReminder extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'vehicle_id',
        'reminder_type',
        'description',
        'due_mileage',
        'due_date',
        'status',
        'sent_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_mileage' => 'integer',
            'due_date' => 'date',
            'sent_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant that owns the service reminder.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the vehicle that owns the service reminder.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
