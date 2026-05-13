<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('reference', 50)->nullable();
            $table->text('description')->nullable();
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->string('unit', 30)->default('forfait');
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('hidden_from_pro')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
