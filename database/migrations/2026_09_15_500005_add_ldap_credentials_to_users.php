<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'ldap_username')) {
                $column = $table->string('ldap_username')->nullable();
                if (Schema::hasColumn('users', 'cyberpanel_password')) {
                    $column->after('cyberpanel_password');
                }
            }
            if (! Schema::hasColumn('users', 'ldap_password')) {
                $table->text('ldap_password')->nullable();
            }
            if (! Schema::hasColumn('users', 'ldap_dn')) {
                $table->string('ldap_dn')->nullable();
            }
            if (! Schema::hasColumn('users', 'ldap_group')) {
                $table->string('ldap_group')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ldap_username', 'ldap_password', 'ldap_dn', 'ldap_group']);
        });
    }
};
