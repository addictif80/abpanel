<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_domains', function (Blueprint $table) {
            $table->boolean('is_subdomain')->default(false)->after('domain');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('limit_per_domain')->nullable()->after('limit_per_hosting');
            $table->unsignedInteger('limit_per_subdomain')->nullable()->after('limit_per_domain');
        });
    }

    public function down(): void
    {
        Schema::table('client_domains', function (Blueprint $table) {
            $table->dropColumn('is_subdomain');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['limit_per_domain', 'limit_per_subdomain']);
        });
    }
};
