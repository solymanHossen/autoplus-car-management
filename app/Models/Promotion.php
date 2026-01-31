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
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $discount_type
 * @property string $discount_value
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property int|null $max_uses
 * @property int $used_count
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Promotion extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'max_uses',
        'used_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'max_uses' => 'integer',
            'used_count' => 'integer',
        ];
    }

    /**
     * Get the tenant that owns the promotion.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
