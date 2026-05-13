<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->text('client_comment')->nullable()->after('internal_notes');
            $table->timestamp('cgv_accepted_at')->nullable()->after('client_comment');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['client_comment', 'cgv_accepted_at']);
        });
    }
};
