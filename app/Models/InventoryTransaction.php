<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $tenant_id
 * @property int $product_id
 * @property string $transaction_type
 * @property int $quantity
 * @property string $unit_price
 * @property string $total_amount
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property int|null $performed_by
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class InventoryTransaction extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'transaction_type',
        'quantity',
        'unit_price',
        'total_amount',
        'reference_type',
        'reference_id',
        'performed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * Get the tenant that owns the transaction.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the product for the transaction.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who performed the transaction.
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get the reference model (job card, purchase order, etc.).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
