<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('quote_id')->nullable()->after('plan_id')->constrained()->nullOnDelete();
            $table->enum('type', ['subscription', 'manual', 'deposit', 'balance'])->default('manual')->after('quote_id');
            $table->unsignedBigInteger('deposit_invoice_id')->nullable()->after('type');
            $table->foreign('deposit_invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['quote_id']);
            $table->dropForeign(['deposit_invoice_id']);
            $table->dropColumn(['quote_id', 'type', 'deposit_invoice_id']);
        });
    }
};
