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
                $table->string('ldap_username')->nullable()->after('cyberpanel_password');
            }
            if (! Schema::hasColumn('users', 'ldap_password')) {
                $table->text('ldap_password')->nullable()->after('ldap_username');
            }
            if (! Schema::hasColumn('users', 'ldap_dn')) {
                $table->string('ldap_dn')->nullable()->after('ldap_password');
            }
            if (! Schema::hasColumn('users', 'ldap_group')) {
                $table->string('ldap_group')->nullable()->after('ldap_dn');
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
