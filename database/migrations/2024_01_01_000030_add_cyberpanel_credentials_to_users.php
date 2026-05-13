<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'cyberpanel_username')) {
                $table->string('cyberpanel_username')->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'cyberpanel_password')) {
                $table->text('cyberpanel_password')->nullable()->after('cyberpanel_username');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(array_filter(
                ['cyberpanel_username', 'cyberpanel_password'],
                fn($col) => Schema::hasColumn('users', $col)
            ));
        });
    }
};
