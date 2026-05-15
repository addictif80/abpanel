<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('limit_per_client')->nullable()->after('sort_order');
            $table->unsignedInteger('limit_per_vm')->nullable()->after('limit_per_client');
            $table->unsignedInteger('limit_per_hosting')->nullable()->after('limit_per_vm');
            $table->boolean('requires_vm')->default(false)->after('limit_per_hosting');
            $table->boolean('requires_hosting')->default(false)->after('requires_vm');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['limit_per_client', 'limit_per_vm', 'limit_per_hosting', 'requires_vm', 'requires_hosting']);
        });
    }
};
