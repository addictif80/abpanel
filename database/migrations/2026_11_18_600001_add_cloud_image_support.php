<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // template_type 'qemu' = cloud image (qcow2) downloaded on the fly at
        // deployment time instead of being kept on the node.
        Schema::table('os_templates', function (Blueprint $table) {
            $table->string('checksum')->nullable()->after('filename');
            $table->string('checksum_algorithm', 10)->nullable()->after('checksum');
            $table->string('ci_user', 50)->nullable()->after('checksum_algorithm');
            $table->unsignedInteger('retention_minutes')->default(0)->after('ci_user'); // 0 = delete right after use
        });

        // Images currently present on a node's storage (one row per template + node),
        // with a lease counter so concurrent deployments share one download.
        Schema::create('os_template_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('os_template_id')->constrained('os_templates')->cascadeOnDelete();
            $table->string('proxmox_node');
            $table->string('volume')->nullable();
            $table->string('download_upid')->nullable();
            $table->unsignedInteger('active_deployments')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->unique(['os_template_id', 'proxmox_node']);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->foreignId('os_template_id')->nullable()->after('proxmox_server_id')
                ->constrained('os_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('os_template_id');
        });
        Schema::dropIfExists('os_template_images');
        Schema::table('os_templates', function (Blueprint $table) {
            $table->dropColumn(['checksum', 'checksum_algorithm', 'ci_user', 'retention_minutes']);
        });
    }
};
