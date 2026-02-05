<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the global unique index on email
            $table->dropUnique('users_email_unique');
            
            // Add a composite unique index for tenant_id + email
            // This allows the same email to be used in different tenants (isolated)
            $table->unique(['tenant_id', 'email'], 'users_tenant_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_tenant_email_unique');
            $table->unique('email', 'users_email_unique');
        });
    }
};
