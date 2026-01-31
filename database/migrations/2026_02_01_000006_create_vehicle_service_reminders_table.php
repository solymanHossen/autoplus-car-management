<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_reminders', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->enum('reminder_type', ['mileage', 'date', 'both']);
            $table->integer('due_mileage')->nullable();
            $table->date('due_date')->nullable();
            $table->string('service_description');
            $table->enum('status', ['pending', 'sent', 'completed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index('tenant_id');
            $table->index('vehicle_id');
            $table->index(['tenant_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_reminders');
    }
};
