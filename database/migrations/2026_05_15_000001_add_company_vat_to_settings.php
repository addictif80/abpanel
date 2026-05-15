<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::firstOrCreate(
            ['key' => 'company_vat_number'],
            ['value' => '', 'group' => 'company']
        );
        Setting::firstOrCreate(
            ['key' => 'einvoicing_enabled'],
            ['value' => '0', 'group' => 'company']
        );
        Setting::firstOrCreate(
            ['key' => 'ppf_siret'],
            ['value' => '', 'group' => 'company']
        );
    }

    public function down(): void
    {
        Setting::whereIn('key', ['company_vat_number', 'einvoicing_enabled', 'ppf_siret'])->delete();
    }
};
