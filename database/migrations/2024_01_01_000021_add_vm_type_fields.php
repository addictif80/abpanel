<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plans: qemu = KVM virtual machine, lxc = container
        Schema::table('plans', function (Blueprint $table) {
            $table->string('vm_type')->default('qemu')->after('type'); // qemu|lxc
        });

        // VMs: track type + disk storage + LXC swap
        Schema::table('virtual_machines', function (Blueprint $table) {
            $table->string('vm_type')->default('qemu')->after('proxmox_node');    // qemu|lxc
            $table->string('disk_storage')->nullable()->after('disk_gb');         // e.g. local-lvm
            $table->integer('swap_mb')->nullable()->after('memory_mb');           // LXC only
        });

        // OS Templates: iso = QEMU, ct = LXC container template
        Schema::table('os_templates', function (Blueprint $table) {
            $table->string('template_type')->default('iso')->after('filename');   // iso|ct
        });
    }

    public function down(): void
    {
        Schema::table('plans', fn($t) => $t->dropColumn('vm_type'));
        Schema::table('virtual_machines', fn($t) => $t->dropColumn(['vm_type', 'disk_storage', 'swap_mb']));
        Schema::table('os_templates', fn($t) => $t->dropColumn('template_type'));
    }
};
