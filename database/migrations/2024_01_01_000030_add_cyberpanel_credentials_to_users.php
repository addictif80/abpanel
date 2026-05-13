<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cyberpanel_username')->nullable()->after('email');
            $table->text('cyberpanel_password')->nullable()->after('cyberpanel_username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cyberpanel_username', 'cyberpanel_password']);
        });
    }
};
