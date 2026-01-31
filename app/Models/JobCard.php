<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $tenant_id
 * @property string $job_number
 * @property int $customer_id
 * @property int $vehicle_id
 * @property int|null $assigned_to
 * @property string $status
 * @property string $priority
 * @property int|null $mileage_in
 * @property int|null $mileage_out
 * @property string|null $customer_notes
 * @property string|null $internal_notes
 * @property string|null $diagnosis_notes
 * @property \Illuminate\Support\Carbon|null $estimated_completion
 * @property \Illuminate\Support\Carbon|null $actual_completion
 * @property string $subtotal
 * @property string $tax_amount
 * @property string $discount_amount
 * @property string $total_amount
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class JobCard extends Model
{
    use HasFactory, TenantScoped, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'job_number',
        'customer_id',
        'vehicle_id',
        'assigned_to',
        'status',
        'priority',
        'mileage_in',
        'mileage_out',
        'customer_notes',
        'internal_notes',
        'diagnosis_notes',
        'estimated_completion',
        'actual_completion',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'started_at',
        'completed_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'mileage_in' => 'integer',
            'mileage_out' => 'integer',
            'estimated_completion' => 'datetime',
            'actual_completion' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant that owns the job card.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the customer for the job card.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the vehicle for the job card.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the user assigned to the job card.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the items for the job card.
     */
    public function jobCardItems(): HasMany
    {
        return $this->hasMany(JobCardItem::class);
    }

    /**
     * Get the AI diagnostics for the job card.
     */
    public function aiDiagnostics(): HasMany
    {
        return $this->hasMany(AiDiagnostic::class);
    }

    /**
     * Get the invoice for the job card.
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * Get the attachments for the job card.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get the feedback for the job card.
     */
    public function feedback(): HasOne
    {
        return $this->hasOne(CustomerFeedback::class);
    }
}
