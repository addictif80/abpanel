<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('plans', 'stripe_product_id')) {
            Schema::table('plans', function (Blueprint $table) {
                $column = $table->string('stripe_product_id')->nullable();
                if (Schema::hasColumn('plans', 'stripe_price_id')) {
                    $column->after('stripe_price_id');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('stripe_product_id');
        });
    }
};
