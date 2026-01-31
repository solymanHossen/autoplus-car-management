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
 * @property string $notification_type
 * @property string|null $recipient_type
 * @property int|null $recipient_id
 * @property string $channel
 * @property string $subject
 * @property string $message
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property string $status
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class NotificationLog extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'notification_type',
        'recipient_type',
        'recipient_id',
        'channel',
        'subject',
        'message',
        'sent_at',
        'delivered_at',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant that owns the notification log.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the recipient model (customer, user, etc.).
     */
    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }
}
