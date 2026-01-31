<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_feedback', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('rating');
            $table->text('review_text')->nullable();
            $table->integer('service_quality_rating')->nullable();
            $table->integer('staff_rating')->nullable();
            $table->integer('facility_rating')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamp('responded_at')->nullable();
            $table->text('response_text')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index('tenant_id');
            $table->index('customer_id');
            $table->index('job_card_id');
            $table->index(['tenant_id', 'rating']);
            $table->index('is_public');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_feedback');
    }
};
