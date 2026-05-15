<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('virtual_machine_id')->nullable()->nullOnDelete();
            $table->foreignId('hosting_account_id')->nullable()->nullOnDelete();
            $table->string('domain');
            $table->enum('type', ['vps', 'hosting']);
            $table->string('target_ip');
            $table->unsignedSmallInteger('target_port')->default(80);
            $table->enum('forward_scheme', ['http', 'https'])->default('http');
            $table->boolean('www_redirect')->default(false);
            $table->boolean('ssl_enabled')->default(false);
            $table->unsignedInteger('npm_proxy_id')->nullable();
            $table->unsignedInteger('ssl_certificate_id')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->boolean('dns_ok')->default(false);
            $table->timestamp('dns_checked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_domains');
    }
};
