<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that hold tenant_id foreign keys.
     *
     * @return list<string>
     */
    private function tenantScopedTables(): array
    {
        return [
            'users',
            'settings',
            'customers',
            'vehicles',
            'vehicle_service_reminders',
            'suppliers',
            'products',
            'inventory_transactions',
            'tax_rates',
            'promotions',
            'appointments',
            'service_templates',
            'job_cards',
            'ai_diagnostics',
            'invoices',
            'payments',
            'expenses',
            'customer_communications',
            'notification_logs',
            'customer_feedback',
            'tenant_subscriptions',
            'subscription_invoices',
            'audit_logs',
            'attachments',
            'api_rate_limits',
            'webhooks',
        ];
    }

    public function up(): void
    {
        foreach ($this->tenantScopedTables() as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table): void {
                $table->dropForeign(['tenant_id']);
                $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tenantScopedTables() as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table): void {
                $table->dropForeign(['tenant_id']);
                $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            });
        }
    }
};
