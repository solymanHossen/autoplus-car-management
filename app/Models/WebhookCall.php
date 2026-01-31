<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $webhook_id
 * @property array $payload
 * @property int|null $response_status
 * @property string|null $response_body
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class WebhookCall extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'webhook_id',
        'payload',
        'response_status',
        'response_body',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response_status' => 'integer',
        ];
    }

    /**
     * Get the webhook for the call.
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }
}
