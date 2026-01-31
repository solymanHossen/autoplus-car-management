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
 * @property int $job_card_id
 * @property int $vehicle_id
 * @property int $created_by
 * @property string|null $image_url
 * @property string $diagnosis_text
 * @property string|null $confidence_score
 * @property array|null $visual_markings
 * @property array|null $suggested_services
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AiDiagnostic extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'job_card_id',
        'vehicle_id',
        'created_by',
        'image_url',
        'diagnosis_text',
        'confidence_score',
        'visual_markings',
        'suggested_services',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'decimal:2',
            'visual_markings' => 'array',
            'suggested_services' => 'array',
        ];
    }

    /**
     * Get the tenant that owns the AI diagnostic.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the job card for the AI diagnostic.
     */
    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    /**
     * Get the vehicle for the AI diagnostic.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the user who created the AI diagnostic.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
