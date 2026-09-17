<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A legal invoice must keep the buyer's details exactly as they were at
 * issuance for the statutory retention period (Code de commerce / CGI,
 * 6-10 years in France) — it cannot be silently rewritten just because the
 * client's live profile was later anonymized (GDPR right to erasure, Art.
 * 17§3(b): erasure does not apply to data needed for legal compliance).
 * This column freezes that snapshot at creation time so anonymizing a
 * client's User row never alters a past invoice's displayed billing info.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->json('billing_snapshot')->nullable()->after('items');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('billing_snapshot');
        });
    }
};
