<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * On at least one production DB, cyberpanel_password was dropped outside of
 * migrations after 2024_01_01_000030 ran, so that migration's "already ran"
 * record no longer matches the real schema. Re-add it defensively.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'cyberpanel_password')) {
            Schema::table('users', function (Blueprint $table) {
                $column = $table->text('cyberpanel_password')->nullable();
                if (Schema::hasColumn('users', 'cyberpanel_username')) {
                    $column->after('cyberpanel_username');
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally left as a no-op: this is a repair migration, and the
        // column is actively used by hosting provisioning.
    }
};
