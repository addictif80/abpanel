<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('os_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');                        // e.g. "Debian 12 Bookworm"
            $table->text('description')->nullable();
            $table->string('url');                         // source download URL
            $table->string('filename');                    // e.g. debian-12-amd64.iso
            $table->string('proxmox_node');                // node where it's stored
            $table->string('proxmox_storage');             // e.g. "local"
            $table->string('proxmox_volume')->nullable();  // full path, e.g. local:iso/debian-12.iso
            $table->string('proxmox_task_id')->nullable(); // UPID for polling download progress
            $table->string('status')->default('pending');  // pending|downloading|ready|error
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('os_templates');
    }
};
