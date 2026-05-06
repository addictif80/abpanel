<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_machines', function (Blueprint $table) {
            $table->text('root_password')->nullable()->after('status');         // encrypted
            $table->string('provisioning_status')->default('active')->after('root_password'); // pending|provisioning|active|failed
            $table->text('provisioning_error')->nullable()->after('provisioning_status');
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete()->after('provisioning_error');
        });
    }

    public function down(): void
    {
        Schema::table('virtual_machines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn(['root_password', 'provisioning_status', 'provisioning_error']);
        });
    }
};
