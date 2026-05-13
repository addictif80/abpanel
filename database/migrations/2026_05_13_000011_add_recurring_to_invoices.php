<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false)->after('type');
            $table->string('recurrence_period')->nullable()->after('is_recurring'); // monthly, quarterly, yearly
            $table->date('next_billing_at')->nullable()->after('recurrence_period');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['is_recurring', 'recurrence_period', 'next_billing_at']);
        });
    }
};
