<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('plans', 'storage_quota_gb')) {
            Schema::table('plans', function (Blueprint $table) {
                $column = $table->unsignedInteger('storage_quota_gb')->nullable();
                if (Schema::hasColumn('plans', 'ldap_group')) {
                    $column->after('ldap_group');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('storage_quota_gb');
        });
    }
};
