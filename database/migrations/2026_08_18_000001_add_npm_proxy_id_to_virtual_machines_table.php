<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_machines', function (Blueprint $table) {
            $table->unsignedInteger('npm_proxy_id')->nullable()->after('subdomain');
        });
    }

    public function down(): void
    {
        Schema::table('virtual_machines', function (Blueprint $table) {
            $table->dropColumn('npm_proxy_id');
        });
    }
};
