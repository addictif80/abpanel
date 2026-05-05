<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->nullable()->after('email');
            $table->string('company')->nullable()->after('phone');
            $table->string('address')->nullable()->after('company');
            $table->string('city')->nullable()->after('address');
            $table->string('zip')->nullable()->after('city');
            $table->string('country')->default('FR')->after('zip');
            $table->boolean('is_admin')->default(false)->after('country');
            $table->boolean('is_active')->default(true)->after('is_admin');
            $table->string('cyberpanel_username')->nullable()->after('is_active');
            $table->string('stripe_customer_id')->nullable()->after('cyberpanel_username');
            $table->boolean('newsletter_subscribed')->default(false)->after('stripe_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'last_name', 'phone', 'company',
                'address', 'city', 'zip', 'country',
                'is_admin', 'is_active', 'cyberpanel_username',
                'stripe_customer_id', 'newsletter_subscribed',
            ]);
        });
    }
};
