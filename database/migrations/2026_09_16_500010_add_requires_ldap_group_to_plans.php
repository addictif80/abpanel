<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('plans', 'requires_ldap_group')) {
            Schema::table('plans', function (Blueprint $table) {
                $column = $table->string('requires_ldap_group')->nullable();
                if (Schema::hasColumn('plans', 'storage_quota_gb')) {
                    $column->after('storage_quota_gb');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('requires_ldap_group');
        });
    }
};
