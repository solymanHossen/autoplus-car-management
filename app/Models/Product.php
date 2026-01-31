<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $tenant_id
 * @property int|null $supplier_id
 * @property string $sku
 * @property string $name
 * @property array|null $name_local
 * @property string|null $description
 * @property string $type
 * @property string|null $category
 * @property string $unit_price
 * @property string $cost_price
 * @property int $stock_quantity
 * @property int $min_stock_level
 * @property int|null $supplier_id
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Product extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'sku',
        'name',
        'name_local',
        'type',
        'category',
        'unit_price',
        'cost_price',
        'stock_quantity',
        'min_stock_level',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'name_local' => 'array',
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'min_stock_level' => 'integer',
        ];
    }

    /**
     * Get the tenant that owns the product.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the supplier that provides the product.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the inventory transactions for the product.
     */
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Get the job card items for the product.
     */
    public function jobCardItems(): HasMany
    {
        return $this->hasMany(JobCardItem::class);
    }

    /**
     * Get the service template items for the product.
     */
    public function serviceTemplateItems(): HasMany
    {
        return $this->hasMany(ServiceTemplateItem::class);
    }
}
