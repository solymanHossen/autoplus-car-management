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
 * @property string $url
 * @property string $event_type
 * @property string|null $secret
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Webhook extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'name',
        'url',
        'event_type',
        'secret',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the tenant that owns the webhook.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the webhook calls for the webhook.
     */
    public function webhookCalls(): HasMany
    {
        return $this->hasMany(WebhookCall::class);
    }
}
