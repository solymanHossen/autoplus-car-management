<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $price_monthly
 * @property string $price_yearly
 * @property int|null $max_users
 * @property int|null $max_vehicles
 * @property int|null $max_storage_gb
 * @property array|null $features
 * @property bool $is_active
 * @property int $display_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price_monthly',
        'price_yearly',
        'max_users',
        'max_vehicles',
        'max_storage_gb',
        'features',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'max_users' => 'integer',
            'max_vehicles' => 'integer',
            'max_storage_gb' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    /**
     * Get the tenant subscriptions for the package.
     */
    public function tenantSubscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }
}
