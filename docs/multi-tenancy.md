# Multi-Tenancy Guide

## Table of Contents
- [Overview](#overview)
- [Tenant Identification](#tenant-identification)
- [Configuration](#configuration)
- [TenantScoped Trait](#tenantscoped-trait)
- [Setting Up New Tenants](#setting-up-new-tenants)
- [Tenant Isolation](#tenant-isolation)
- [Storage Separation](#storage-separation)
- [Cache Management](#cache-management)
- [Database Queries](#database-queries)
- [Testing Multi-Tenancy](#testing-multi-tenancy)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

---

## Overview

AutoPulse implements a **single-database, shared-schema** multi-tenancy architecture. All tenants share the same database and tables, with data isolation enforced through a `tenant_id` foreign key column.

### Key Features

- ✅ **Automatic tenant scoping** - All queries automatically filtered by tenant
- ✅ **Multiple identification methods** - Domain, subdomain, header, or path-based
- ✅ **Tenant isolation** - Strict data separation between tenants
- ✅ **Storage separation** - Dedicated storage directories per tenant
- ✅ **Cache separation** - Tenant-specific cache prefixes
- ✅ **Subscription management** - Built-in tenant subscription tracking

### Architecture Benefits

| Benefit | Description |
|---------|-------------|
| **Cost-effective** | Single database for all tenants reduces infrastructure costs |
| **Easy maintenance** | Schema changes apply to all tenants simultaneously |
| **Scalable** | Horizontal scaling via database read replicas |
| **Performance** | Shared resources with efficient indexing |
| **Simple backups** | Single database backup covers all tenants |

---

## Tenant Identification

AutoPulse supports four methods for identifying tenants. The identification method is configured in `config/tenant.php`.

### 1. Domain-Based Identification

Each tenant has a unique domain.

**Configuration:**
```php
'identification_method' => 'domain'
```

**Examples:**
- `https://acme-motors.com` → Tenant: Acme Motors
- `https://city-auto.com` → Tenant: City Auto
- `https://speedway-garage.com` → Tenant: Speedway Garage

**Database Setup:**
```sql
INSERT INTO tenants (id, name, domain) VALUES 
('uuid-1', 'Acme Motors', 'acme-motors.com');
```

**DNS Configuration:**
Each domain must point to your application server:
```
acme-motors.com     A    123.45.67.89
city-auto.com       A    123.45.67.89
```

### 2. Subdomain-Based Identification

Tenants use subdomains of your main domain.

**Configuration:**
```php
'identification_method' => 'subdomain'
```

**Examples:**
- `https://acme.autopulse.com` → Tenant: acme
- `https://cityauto.autopulse.com` → Tenant: cityauto
- `https://speedway.autopulse.com` → Tenant: speedway

**Database Setup:**
```sql
INSERT INTO tenants (id, name, subdomain) VALUES 
('uuid-1', 'Acme Motors', 'acme');
```

**DNS Configuration:**
Wildcard subdomain pointing to your server:
```
*.autopulse.com     A    123.45.67.89
```

### 3. Header-Based Identification

Tenant identified via HTTP header (useful for mobile apps and SPAs).

**Configuration:**
```php
'identification_method' => 'header'
```

**Request Example:**
```http
GET /api/v1/customers HTTP/1.1
Host: api.autopulse.com
X-Tenant-ID: uuid-tenant-id
Authorization: Bearer token
```

**Use Cases:**
- Mobile applications
- Single-page applications (SPAs)
- Third-party integrations
- API-only deployments

### 4. Path-Based Identification

Tenant identified via URL path segment.

**Configuration:**
```php
'identification_method' => 'path'
```

**Examples:**
- `https://autopulse.com/acme/customers` → Tenant: acme
- `https://autopulse.com/cityauto/dashboard` → Tenant: cityauto

**Route Configuration:**
```php
Route::prefix('{tenant}')->middleware('identify.tenant')->group(function () {
    // Your routes here
});
```

---

## Configuration

### Tenant Configuration File

**File:** `config/tenant.php`

```php
<?php

return [
    // Tenant identification method: 'domain', 'subdomain', 'header', 'path'
    'identification_method' => env('TENANT_IDENTIFICATION_METHOD', 'domain'),

    // Tenant model class
    'tenant_model' => \App\Models\Tenant::class,

    // Column name for tenant foreign key in all tenant-scoped tables
    'tenant_column' => 'tenant_id',

    // Central domains that should not be treated as tenant domains
    'central_domains' => [
        env('APP_URL'),
        'localhost',
        '127.0.0.1',
    ],

    // Default storage disk for tenant files
    'storage_disk' => env('TENANT_STORAGE_DISK', 'local'),

    // Cache key prefix for tenant-specific cache entries
    'cache_prefix' => 'tenant',

    // Database connection for tenant data
    'database_connection' => env('DB_CONNECTION', 'mysql'),
];
```

### Environment Variables

```env
# Tenant identification method
TENANT_IDENTIFICATION_METHOD=domain

# Tenant storage disk
TENANT_STORAGE_DISK=local

# Database connection
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=autopulse
DB_USERNAME=root
DB_PASSWORD=secret
```

---

## TenantScoped Trait

The `TenantScoped` trait automatically handles tenant filtering and assignment for Eloquent models.

### Implementation

**File:** `app/Traits/TenantScoped.php`

```php
<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait TenantScoped
{
    /**
     * Boot the tenant scoped trait for a model.
     */
    protected static function bootTenantScoped(): void
    {
        // Add global scope to filter all queries by tenant_id
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        // Automatically set tenant_id when creating new records
        static::creating(function (Model $model) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
```

### How It Works

#### 1. Global Scope
All queries on tenant-scoped models automatically include `WHERE tenant_id = ?`:

```php
// This query:
$customers = Customer::all();

// Automatically becomes:
$customers = Customer::where('tenant_id', auth()->user()->tenant_id)->get();
```

#### 2. Automatic Assignment
When creating new records, `tenant_id` is automatically set:

```php
// You write:
$customer = Customer::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// The trait automatically adds:
// 'tenant_id' => auth()->user()->tenant_id
```

### Using the Trait

Add the `TenantScoped` trait to any model that should be tenant-specific:

```php
<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
    ];
}
```

### Bypassing Tenant Scope

In rare cases where you need to query across tenants (e.g., system administration):

```php
// Remove tenant scope for a single query
$allCustomers = Customer::withoutGlobalScope('tenant')->get();

// Remove all global scopes
$allCustomers = Customer::withoutGlobalScopes()->get();
```

⚠️ **Security Warning:** Only use `withoutGlobalScope()` in trusted admin contexts with proper authorization checks.

---

## Setting Up New Tenants

### Manual Setup

#### 1. Create Tenant Record

```php
use App\Models\Tenant;

$tenant = Tenant::create([
    'name' => 'Acme Motors',
    'domain' => 'acme-motors.com',
    'subdomain' => 'acme',
    'subscription_status' => 'active',
    'trial_ends_at' => now()->addDays(30),
]);
```

#### 2. Create Owner User

```php
use App\Models\User;

$owner = User::create([
    'tenant_id' => $tenant->id,
    'name' => 'John Smith',
    'email' => 'john@acme-motors.com',
    'password' => bcrypt('secure-password'),
    'role' => 'owner',
    'phone' => '+1234567890',
]);
```

#### 3. Initialize Settings

```php
use App\Models\Setting;

Setting::create([
    'tenant_id' => $tenant->id,
    'key' => 'company_name',
    'value' => 'Acme Motors',
]);

Setting::create([
    'tenant_id' => $tenant->id,
    'key' => 'currency',
    'value' => 'USD',
]);
```

#### 4. Create Storage Directory

```php
Storage::makeDirectory("tenants/{$tenant->id}");
Storage::makeDirectory("tenants/{$tenant->id}/attachments");
Storage::makeDirectory("tenants/{$tenant->id}/invoices");
Storage::makeDirectory("tenants/{$tenant->id}/reports");
```

### Automated Setup via Command

Create an Artisan command for tenant provisioning:

```php
<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CreateTenantCommand extends Command
{
    protected $signature = 'tenant:create 
                            {name : The tenant name}
                            {domain : The tenant domain}
                            {email : Owner email address}';

    protected $description = 'Create a new tenant with owner account';

    public function handle()
    {
        $name = $this->argument('name');
        $domain = $this->argument('domain');
        $email = $this->argument('email');

        // Create tenant
        $tenant = Tenant::create([
            'name' => $name,
            'domain' => $domain,
            'subscription_status' => 'active',
            'trial_ends_at' => now()->addDays(30),
        ]);

        $this->info("Tenant created: {$tenant->id}");

        // Create owner user
        $password = $this->secret('Enter password for owner');
        
        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => $this->ask('Owner name'),
            'email' => $email,
            'password' => bcrypt($password),
            'role' => 'owner',
        ]);

        $this->info("Owner user created: {$owner->email}");

        // Create storage directories
        Storage::makeDirectory("tenants/{$tenant->id}");
        $this->info("Storage directory created");

        $this->info("✅ Tenant setup complete!");
        $this->table(
            ['Property', 'Value'],
            [
                ['Tenant ID', $tenant->id],
                ['Name', $tenant->name],
                ['Domain', $tenant->domain],
                ['Owner Email', $owner->email],
            ]
        );
    }
}
```

**Usage:**
```bash
php artisan tenant:create "Acme Motors" "acme-motors.com" "owner@acme-motors.com"
```

---

## Tenant Isolation

### Database-Level Isolation

All tenant-scoped tables include a `tenant_id` column with a foreign key constraint:

```php
Schema::create('customers', function (Blueprint $table) {
    $table->id();
    $table->uuid('tenant_id');
    $table->string('name');
    $table->string('email')->nullable();
    // ... other columns
    $table->timestamps();
    $table->softDeletes();

    // Foreign key constraint ensures referential integrity
    $table->foreign('tenant_id')
          ->references('id')
          ->on('tenants')
          ->onDelete('cascade');

    // Composite index for efficient tenant queries
    $table->index(['tenant_id', 'created_at']);
});
```

### Application-Level Isolation

#### Middleware: IdentifyTenant

**File:** `app/Http/Middleware/IdentifyTenant.php`

```php
<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = $this->resolveTenant($request);

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found',
            ], 404);
        }

        // Set tenant in container for easy access
        app()->instance('tenant', $tenant);

        // Store tenant ID in session/cache if needed
        session(['tenant_id' => $tenant->id]);

        return $next($request);
    }

    private function resolveTenant(Request $request): ?Tenant
    {
        $method = config('tenant.identification_method');

        return match($method) {
            'domain' => $this->resolveTenantByDomain($request),
            'subdomain' => $this->resolveTenantBySubdomain($request),
            'header' => $this->resolveTenantByHeader($request),
            'path' => $this->resolveTenantByPath($request),
            default => null,
        };
    }

    private function resolveTenantByDomain(Request $request): ?Tenant
    {
        $host = $request->getHost();
        return Tenant::where('domain', $host)->first();
    }

    private function resolveTenantBySubdomain(Request $request): ?Tenant
    {
        $subdomain = explode('.', $request->getHost())[0];
        return Tenant::where('subdomain', $subdomain)->first();
    }

    private function resolveTenantByHeader(Request $request): ?Tenant
    {
        $tenantId = $request->header('X-Tenant-ID');
        return $tenantId ? Tenant::find($tenantId) : null;
    }

    private function resolveTenantByPath(Request $request): ?Tenant
    {
        $segment = $request->segment(1);
        return Tenant::where('subdomain', $segment)->first();
    }
}
```

### Policy-Based Authorization

Ensure users can only access their tenant's data:

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Customer;

class CustomerPolicy
{
    public function view(User $user, Customer $customer): bool
    {
        return $user->tenant_id === $customer->tenant_id;
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->tenant_id === $customer->tenant_id 
            && $user->hasPermission('edit-customers');
    }
}
```

---

## Storage Separation

Each tenant has a dedicated storage directory for uploaded files.

### Directory Structure

```
storage/app/
├── tenants/
│   ├── uuid-tenant-1/
│   │   ├── attachments/
│   │   │   ├── job-card-photos/
│   │   │   └── vehicle-images/
│   │   ├── invoices/
│   │   │   └── pdf/
│   │   ├── reports/
│   │   └── exports/
│   ├── uuid-tenant-2/
│   │   ├── attachments/
│   │   ├── invoices/
│   │   └── reports/
│   └── ...
```

### Storing Files

Always use the tenant-aware storage path:

```php
use Illuminate\Support\Facades\Storage;

// Store file for current tenant
$path = Storage::putFile(
    "tenants/{$tenant->id}/attachments",
    $request->file('photo')
);

// Store with custom name
$path = Storage::putFileAs(
    "tenants/{$tenant->id}/invoices",
    $pdfFile,
    "INV-2024-0001.pdf"
);
```

### Retrieving Files

```php
// Get file URL
$url = Storage::url("tenants/{$tenant->id}/attachments/photo.jpg");

// Download file
return Storage::download("tenants/{$tenant->id}/invoices/INV-001.pdf");

// Check if file exists
if (Storage::exists("tenants/{$tenant->id}/attachments/photo.jpg")) {
    // File exists
}
```

### Storage Helper

Create a helper for tenant-aware storage:

```php
<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class TenantStorage
{
    public static function path(string $path = ''): string
    {
        $tenant = app('tenant');
        return "tenants/{$tenant->id}/{$path}";
    }

    public static function put(string $path, $contents): string
    {
        return Storage::put(self::path($path), $contents);
    }

    public static function get(string $path): string
    {
        return Storage::get(self::path($path));
    }

    public static function delete(string $path): bool
    {
        return Storage::delete(self::path($path));
    }

    public static function url(string $path): string
    {
        return Storage::url(self::path($path));
    }
}
```

**Usage:**
```php
use App\Helpers\TenantStorage;

// Store file
$path = TenantStorage::put('attachments/photo.jpg', $fileContents);

// Get file URL
$url = TenantStorage::url('attachments/photo.jpg');

// Delete file
TenantStorage::delete('attachments/photo.jpg');
```

---

## Cache Management

Tenant-specific cache entries use prefixed keys to prevent cache collision.

### Cache Key Prefixing

```php
use Illuminate\Support\Facades\Cache;

// Manually prefix cache keys
$cacheKey = "tenant:{$tenant->id}:customers:stats";
Cache::put($cacheKey, $stats, 3600);

// Retrieve
$stats = Cache::get($cacheKey);
```

### Cache Helper

Create a tenant-aware cache helper:

```php
<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class TenantCache
{
    public static function key(string $key): string
    {
        $tenant = app('tenant');
        $prefix = config('tenant.cache_prefix');
        return "{$prefix}:{$tenant->id}:{$key}";
    }

    public static function get(string $key, $default = null)
    {
        return Cache::get(self::key($key), $default);
    }

    public static function put(string $key, $value, $ttl = null): bool
    {
        return Cache::put(self::key($key), $value, $ttl);
    }

    public static function forget(string $key): bool
    {
        return Cache::forget(self::key($key));
    }

    public static function remember(string $key, $ttl, callable $callback)
    {
        return Cache::remember(self::key($key), $ttl, $callback);
    }

    public static function flush(): bool
    {
        $tenant = app('tenant');
        $prefix = config('tenant.cache_prefix');
        $pattern = "{$prefix}:{$tenant->id}:*";
        
        // Flush all keys matching tenant pattern
        $keys = Cache::getRedis()->keys($pattern);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        return true;
    }
}
```

**Usage:**
```php
use App\Helpers\TenantCache;

// Store in cache
TenantCache::put('dashboard:stats', $stats, 3600);

// Retrieve from cache
$stats = TenantCache::get('dashboard:stats');

// Cache with callback
$stats = TenantCache::remember('dashboard:stats', 3600, function () {
    return Dashboard::calculateStats();
});

// Clear tenant cache
TenantCache::flush();
```

---

## Database Queries

### Tenant-Scoped Queries

Models with `TenantScoped` trait automatically filter by tenant:

```php
// Automatically scoped to current tenant
$customers = Customer::all();
$vehicles = Vehicle::where('make', 'Toyota')->get();
$activeJobCards = JobCard::where('status', 'in_progress')->get();
```

### Eager Loading with Tenant Scope

```php
// Eager load relationships (tenant scope applies automatically)
$customers = Customer::with(['vehicles', 'jobCards', 'invoices'])->get();
```

### Counting and Aggregates

```php
// All aggregates respect tenant scope
$totalCustomers = Customer::count();
$totalRevenue = Invoice::sum('total_amount');
$averageJobValue = JobCard::avg('total_amount');
```

### Raw Queries

When using raw queries, manually include tenant filtering:

```php
use Illuminate\Support\Facades\DB;

$tenantId = auth()->user()->tenant_id;

$results = DB::select(
    'SELECT * FROM customers WHERE tenant_id = ? AND city = ?',
    [$tenantId, 'New York']
);
```

### Query Builder

```php
$customers = DB::table('customers')
    ->where('tenant_id', auth()->user()->tenant_id)
    ->where('city', 'New York')
    ->get();
```

---

## Testing Multi-Tenancy

### Feature Tests

```php
<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Customer;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    public function test_users_can_only_see_their_tenant_data()
    {
        // Create two tenants
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create users for each tenant
        $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);
        $user2 = User::factory()->create(['tenant_id' => $tenant2->id]);

        // Create customers for each tenant
        $customer1 = Customer::factory()->create(['tenant_id' => $tenant1->id]);
        $customer2 = Customer::factory()->create(['tenant_id' => $tenant2->id]);

        // User 1 should only see their customer
        $this->actingAs($user1);
        $customers = Customer::all();
        
        $this->assertCount(1, $customers);
        $this->assertEquals($customer1->id, $customers->first()->id);

        // User 2 should only see their customer
        $this->actingAs($user2);
        $customers = Customer::all();
        
        $this->assertCount(1, $customers);
        $this->assertEquals($customer2->id, $customers->first()->id);
    }

    public function test_tenant_id_is_automatically_set_on_create()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
        ]);

        $this->assertEquals($tenant->id, $customer->tenant_id);
    }
}
```

---

## Best Practices

### 1. Always Use TenantScoped Trait

Add `TenantScoped` to all models that should be tenant-specific:

```php
✅ DO:
class Customer extends Model
{
    use TenantScoped;
}

❌ DON'T:
class Customer extends Model
{
    // Missing TenantScoped trait
}
```

### 2. Never Hard-Code Tenant IDs

```php
❌ DON'T:
$customer = Customer::where('tenant_id', 'uuid-123')->first();

✅ DO:
$customer = Customer::first(); // Automatically scoped
```

### 3. Use Tenant-Aware Storage

```php
❌ DON'T:
Storage::put('invoices/file.pdf', $content);

✅ DO:
TenantStorage::put('invoices/file.pdf', $content);
```

### 4. Prefix Cache Keys

```php
❌ DON'T:
Cache::put('stats', $data);

✅ DO:
TenantCache::put('stats', $data);
```

### 5. Test Tenant Isolation

Always include tests to verify tenant data isolation:

```php
public function test_tenant_data_isolation()
{
    // Create data for multiple tenants
    // Verify each tenant can only access their own data
}
```

### 6. Validate Tenant Access in Controllers

```php
public function show(Customer $customer)
{
    // Laravel route model binding respects tenant scope
    // No additional checks needed
    return response()->json($customer);
}
```

### 7. Handle Tenant Context in Jobs

```php
class ProcessInvoice implements ShouldQueue
{
    public function __construct(
        public Invoice $invoice,
        public string $tenantId
    ) {}

    public function handle()
    {
        $tenant = Tenant::find($this->tenantId);
        app()->instance('tenant', $tenant);
        
        // Process invoice in tenant context
    }
}
```

---

## Troubleshooting

### Issue: "Tenant not found" Error

**Symptoms:** 404 error when accessing the application

**Solutions:**
1. Verify DNS configuration points to your server
2. Check tenant record exists in database
3. Verify `domain` or `subdomain` matches exactly
4. Check `central_domains` configuration excludes tenant domains

### Issue: Users Seeing Other Tenants' Data

**Symptoms:** Data leakage between tenants

**Solutions:**
1. Ensure model uses `TenantScoped` trait
2. Verify `tenant_id` column exists in table
3. Check global scope is being applied (enable query logging)
4. Review any raw queries for tenant filtering

**Enable Query Logging:**
```php
DB::enableQueryLog();
$customers = Customer::all();
dd(DB::getQueryLog());
// Verify: WHERE tenant_id = ?
```

### Issue: Tenant ID Not Set on Create

**Symptoms:** `tenant_id` is NULL when creating records

**Solutions:**
1. Verify user is authenticated
2. Check user has `tenant_id` set
3. Ensure `TenantScoped` trait is used on model
4. Confirm `tenant_id` is in `$fillable` array

### Issue: Storage Files Not Found

**Symptoms:** 404 errors when accessing uploaded files

**Solutions:**
1. Verify storage directory exists: `storage/app/tenants/{tenant_id}`
2. Check file permissions (755 for directories, 644 for files)
3. Run `php artisan storage:link` if using public storage
4. Verify tenant ID in file path is correct

### Issue: Cache Collision Between Tenants

**Symptoms:** Wrong data returned from cache

**Solutions:**
1. Use `TenantCache` helper for all tenant-specific caching
2. Manually prefix cache keys with tenant ID
3. Clear cache: `php artisan cache:clear`
4. Verify Redis/Memcached configuration

---

## Additional Resources

- [Entity Relationship Diagram](./erd.md) - Database schema and relationships
- [API Documentation](./api.md) - Complete API reference
- [Contributing Guide](../CONTRIBUTING.md) - Development guidelines

**Laravel Multi-Tenancy Packages:**
- [Tenancy for Laravel](https://tenancyforlaravel.com/)
- [Spatie Multi-Tenancy](https://spatie.be/docs/laravel-multitenancy/)

---

For questions or support, contact the development team or refer to the project documentation.
