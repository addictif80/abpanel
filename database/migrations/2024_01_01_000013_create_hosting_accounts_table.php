<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosting_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('cyberpanel_username');
            $table->string('domain');
            $table->string('plan')->nullable();
            $table->integer('disk_mb')->default(1024);
            $table->boolean('is_active')->default(true);
            $table->decimal('monthly_price', 8, 2)->default(0);
            $table->timestamp('next_renewal_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosting_accounts');
    }
};
