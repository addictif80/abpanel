<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (! Schema::hasColumn('plans', 'yearly_price')) {
                $column = $table->decimal('yearly_price', 8, 2)->nullable();
                if (Schema::hasColumn('plans', 'price')) {
                    $column->after('price');
                }
            }
            if (! Schema::hasColumn('plans', 'stripe_yearly_price_id')) {
                $column = $table->string('stripe_yearly_price_id')->nullable();
                if (Schema::hasColumn('plans', 'stripe_price_id')) {
                    $column->after('stripe_price_id');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['yearly_price', 'stripe_yearly_price_id']);
        });
    }
};
