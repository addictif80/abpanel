<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('newsletter_campaigns', 'message')) {
                $table->longText('message')->nullable()->after('subject');
            }
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_campaigns', function (Blueprint $table) {
            $table->dropColumn('message');
        });
    }
};
