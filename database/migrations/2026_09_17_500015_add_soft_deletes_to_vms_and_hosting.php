<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('virtual_machines', 'deleted_at')) {
            Schema::table('virtual_machines', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('hosting_accounts', 'deleted_at')) {
            Schema::table('hosting_accounts', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::table('virtual_machines', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
