<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string|null $tenant_id
 * @property int|null $user_id
 * @property string $endpoint
 * @property int $requests_count
 * @property \Illuminate\Support\Carbon $window_start
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ApiRateLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'endpoint',
        'requests_count',
        'window_start',
    ];

    protected function casts(): array
    {
        return [
            'requests_count' => 'integer',
            'window_start' => 'datetime',
        ];
    }

    /**
     * Get the tenant for the rate limit.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user for the rate limit.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
