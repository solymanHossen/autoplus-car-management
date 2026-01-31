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
 * @property int $customer_id
 * @property int|null $job_card_id
 * @property int $rating
 * @property int|null $service_quality_rating
 * @property int|null $staff_rating
 * @property int|null $facility_rating
 * @property string|null $comments
 * @property bool $is_public
 * @property string|null $response
 * @property \Illuminate\Support\Carbon|null $responded_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CustomerFeedback extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'job_card_id',
        'rating',
        'service_quality_rating',
        'staff_rating',
        'facility_rating',
        'comments',
        'is_public',
        'response',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'service_quality_rating' => 'integer',
            'staff_rating' => 'integer',
            'facility_rating' => 'integer',
            'is_public' => 'boolean',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant that owns the feedback.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the customer who provided the feedback.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the job card for the feedback.
     */
    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }
}
