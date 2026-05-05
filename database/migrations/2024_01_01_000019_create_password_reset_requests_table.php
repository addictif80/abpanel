<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Laravel already creates password_reset_tokens in the base migration
        // This is a no-op but keeps migration numbering clean
    }

    public function down(): void {}
};
