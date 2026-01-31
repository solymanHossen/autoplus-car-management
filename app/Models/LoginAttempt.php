<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $email
 * @property string $ip_address
 * @property bool $successful
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class LoginAttempt extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'email',
        'ip_address',
        'successful',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
        ];
    }
}
