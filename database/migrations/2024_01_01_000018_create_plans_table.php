<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('vm'); // vm, hosting
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2)->default(0);
            $table->string('currency')->default('EUR');
            $table->string('billing_period')->default('monthly'); // monthly, yearly
            $table->string('stripe_price_id')->nullable();
            $table->json('features')->nullable();
            $table->integer('cores')->nullable();
            $table->integer('memory_mb')->nullable();
            $table->integer('disk_gb')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
