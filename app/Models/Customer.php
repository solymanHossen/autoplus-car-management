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
 * @property string $name
 * @property array|null $name_local
 * @property string|null $email
 * @property string $phone
 * @property string|null $phone_alt
 * @property string|null $address
 * @property string|null $city
 * @property string|null $postal_code
 * @property string|null $national_id
 * @property string|null $company_name
 * @property string $preferred_language
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Customer extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'name',
        'name_local',
        'email',
        'phone',
        'phone_alt',
        'address',
        'city',
        'postal_code',
        'national_id',
        'company_name',
        'preferred_language',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'name_local' => 'array',
        ];
    }

    /**
     * Get the tenant that owns the customer.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the vehicles for the customer.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get the job cards for the customer.
     */
    public function jobCards(): HasMany
    {
        return $this->hasMany(JobCard::class);
    }

    /**
     * Get the invoices for the customer.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the communications for the customer.
     */
    public function communications(): HasMany
    {
        return $this->hasMany(CustomerCommunication::class);
    }

    /**
     * Get the feedback for the customer.
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(CustomerFeedback::class);
    }

    /**
     * Get the appointments for the customer.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
