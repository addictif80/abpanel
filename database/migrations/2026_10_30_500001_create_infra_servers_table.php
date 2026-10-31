<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('infra_servers', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // proxmox | cyberpanel | synology
            $table->string('label');
            $table->text('config'); // encrypted JSON: host/user/password/+type-specific fields
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('infra_servers');
    }
};
