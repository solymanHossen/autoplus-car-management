<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Identification
    |--------------------------------------------------------------------------
    |
    | Define how tenants are identified in your application.
    | Options: 'domain', 'subdomain', 'header', 'path'
    |
    */
    'identification_method' => env('TENANT_IDENTIFICATION_METHOD', 'domain'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    |
    | The model class used for tenant identification and scoping.
    |
    */
    'tenant_model' => \App\Models\Tenant::class,

    /*
    |--------------------------------------------------------------------------
    | Tenant Column
    |--------------------------------------------------------------------------
    |
    | The column name used for tenant identification in all tenant-scoped tables.
    |
    */
    'tenant_column' => 'tenant_id',

    /*
    |--------------------------------------------------------------------------
    | Central Domains
    |--------------------------------------------------------------------------
    |
    | Domains that should not be treated as tenant domains.
    | These are typically your main application domain and admin panel.
    |
    */
    'central_domains' => [
        parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost',
        'localhost',
        '127.0.0.1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | Default storage disk for tenant files.
    | Tenant files will be stored in: storage/app/tenants/{tenant_id}/
    |
    */
    'storage_disk' => env('TENANT_STORAGE_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Cache Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for tenant-specific cache keys to prevent cache collision.
    |
    */
    'cache_prefix' => 'tenant',

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | Database connection to use for tenant data.
    | Defaults to the default database connection.
    |
    */
    'database_connection' => env('DB_CONNECTION', 'mysql'),
];
