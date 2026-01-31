<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $tenant_id
 * @property string $name
 * @property string|null $description
 * @property int|null $estimated_duration
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ServiceTemplate extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'estimated_duration',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'estimated_duration' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the tenant that owns the service template.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the items for the service template.
     */
    public function serviceTemplateItems(): HasMany
    {
        return $this->hasMany(ServiceTemplateItem::class);
    }
}
