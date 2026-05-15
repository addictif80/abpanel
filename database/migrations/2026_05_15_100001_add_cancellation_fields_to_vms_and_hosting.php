<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('virtual_machines', function (Blueprint $table) {
            $table->string('cancellation_code', 6)->nullable()->after('next_renewal_at');
            $table->timestamp('cancellation_code_expires_at')->nullable()->after('cancellation_code');
        });

        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->string('cancellation_code', 6)->nullable()->after('next_renewal_at');
            $table->timestamp('cancellation_code_expires_at')->nullable()->after('cancellation_code');
        });
    }

    public function down(): void
    {
        Schema::table('virtual_machines', function (Blueprint $table) {
            $table->dropColumn(['cancellation_code', 'cancellation_code_expires_at']);
        });
        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->dropColumn(['cancellation_code', 'cancellation_code_expires_at']);
        });
    }
};
