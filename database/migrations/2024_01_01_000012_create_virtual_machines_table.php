<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_machines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('proxmox_vmid');
            $table->string('proxmox_node');
            $table->string('status')->default('stopped'); // running, stopped, hibernated
            $table->integer('cores')->default(1);
            $table->integer('memory_mb')->default(1024);
            $table->integer('disk_gb')->default(20);
            $table->string('os_template')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('tailscale_ip')->nullable();
            $table->string('subdomain')->nullable(); // vm-id.vms.domain.com
            $table->string('custom_domain')->nullable();
            $table->boolean('domain_active')->default(false);
            $table->string('plan')->nullable();
            $table->decimal('monthly_price', 8, 2)->default(0);
            $table->timestamp('next_renewal_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_machines');
    }
};
