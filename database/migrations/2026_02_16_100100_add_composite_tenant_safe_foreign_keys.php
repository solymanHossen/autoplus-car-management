<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Parent composite unique keys required for composite FK references.
        Schema::table('customers', function (Blueprint $table): void {
            $table->unique(['id', 'tenant_id'], 'customers_id_tenant_unique');
        });

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->unique(['id', 'tenant_id'], 'vehicles_id_tenant_unique');
        });

        Schema::table('job_cards', function (Blueprint $table): void {
            $table->unique(['id', 'tenant_id'], 'job_cards_id_tenant_unique');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->unique(['id', 'tenant_id'], 'invoices_id_tenant_unique');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->unique(['id', 'tenant_id'], 'users_id_tenant_unique');
        });

        // vehicles.customer_id must belong to same tenant as vehicles.tenant_id
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropForeign(['customer_id']);

            $table->foreign(['customer_id', 'tenant_id'], 'vehicles_customer_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->cascadeOnDelete();
        });

        // job_cards.customer_id and vehicle_id must belong to same tenant
        Schema::table('job_cards', function (Blueprint $table): void {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['vehicle_id']);

            $table->foreign(['customer_id', 'tenant_id'], 'job_cards_customer_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->cascadeOnDelete();

            $table->foreign(['vehicle_id', 'tenant_id'], 'job_cards_vehicle_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicles')
                ->cascadeOnDelete();
        });

        // appointments customer/vehicle must belong to same tenant
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['vehicle_id']);

            $table->foreign(['customer_id', 'tenant_id'], 'appointments_customer_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->cascadeOnDelete();

            $table->foreign(['vehicle_id', 'tenant_id'], 'appointments_vehicle_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicles')
                ->cascadeOnDelete();
        });

        // invoices.customer_id and job_card_id (if set) must belong to same tenant
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['job_card_id']);

            $table->foreign(['customer_id', 'tenant_id'], 'invoices_customer_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->cascadeOnDelete();

            $table->foreign(['job_card_id', 'tenant_id'], 'invoices_job_card_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('job_cards')
                ->nullOnDelete();
        });

        // payments.invoice_id and received_by must belong to same tenant
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['received_by']);

            $table->foreign(['invoice_id', 'tenant_id'], 'payments_invoice_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoices')
                ->cascadeOnDelete();

            $table->foreign(['received_by', 'tenant_id'], 'payments_received_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign('payments_invoice_tenant_fk');
            $table->dropForeign('payments_received_by_tenant_fk');

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            $table->foreign('received_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropForeign('invoices_customer_tenant_fk');
            $table->dropForeign('invoices_job_card_tenant_fk');

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('job_card_id')->references('id')->on('job_cards')->nullOnDelete();
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign('appointments_customer_tenant_fk');
            $table->dropForeign('appointments_vehicle_tenant_fk');

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
        });

        Schema::table('job_cards', function (Blueprint $table): void {
            $table->dropForeign('job_cards_customer_tenant_fk');
            $table->dropForeign('job_cards_vehicle_tenant_fk');

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
        });

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropForeign('vehicles_customer_tenant_fk');

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_id_tenant_unique');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropUnique('invoices_id_tenant_unique');
        });

        Schema::table('job_cards', function (Blueprint $table): void {
            $table->dropUnique('job_cards_id_tenant_unique');
        });

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropUnique('vehicles_id_tenant_unique');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique('customers_id_tenant_unique');
        });
    }
};
