<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_machines', function (Blueprint $table) {
            $table->foreignId('proxmox_server_id')->nullable()->after('user_id')->constrained('infra_servers')->nullOnDelete();
        });

        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->foreignId('cyberpanel_server_id')->nullable()->after('user_id')->constrained('infra_servers')->nullOnDelete();
        });

        Schema::table('os_templates', function (Blueprint $table) {
            $table->foreignId('proxmox_server_id')->nullable()->after('id')->constrained('infra_servers')->nullOnDelete();
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->foreignId('proxmox_server_id')->nullable()->after('vm_type')->constrained('infra_servers')->nullOnDelete();
            $table->foreignId('cyberpanel_server_id')->nullable()->after('cyberpanel_package')->constrained('infra_servers')->nullOnDelete();
            $table->foreignId('synology_server_id')->nullable()->after('storage_quota_gb')->constrained('infra_servers')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('cyberpanel_server_id')->nullable()->after('cyberpanel_username')->constrained('infra_servers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('virtual_machines', fn (Blueprint $table) => $table->dropConstrainedForeignId('proxmox_server_id'));
        Schema::table('hosting_accounts', fn (Blueprint $table) => $table->dropConstrainedForeignId('cyberpanel_server_id'));
        Schema::table('os_templates', fn (Blueprint $table) => $table->dropConstrainedForeignId('proxmox_server_id'));
        Schema::table('plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proxmox_server_id');
            $table->dropConstrainedForeignId('cyberpanel_server_id');
            $table->dropConstrainedForeignId('synology_server_id');
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('cyberpanel_server_id'));
    }
};
