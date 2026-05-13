<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            ['key' => 'company_siren',          'value' => '',   'group' => 'company'],
            ['key' => 'company_legal_form',      'value' => 'Micro-entreprise', 'group' => 'company'],
            ['key' => 'company_rcs',             'value' => '',   'group' => 'company'],
            ['key' => 'company_iban',            'value' => '',   'group' => 'company'],
            ['key' => 'company_bic',             'value' => '',   'group' => 'company'],
            ['key' => 'quote_validity_days',     'value' => '30', 'group' => 'quotes'],
            ['key' => 'invoice_payment_days',    'value' => '30', 'group' => 'quotes'],
            ['key' => 'invoice_late_penalty',    'value' => '10', 'group' => 'quotes'],
            ['key' => 'invoice_recovery_fee',    'value' => '40', 'group' => 'quotes'],
            ['key' => 'vat_mention',             'value' => 'TVA non applicable, art. 293 B du CGI', 'group' => 'quotes'],
            ['key' => 'quote_default_notes',     'value' => '', 'group' => 'quotes'],
            ['key' => 'cgv_path',                'value' => '', 'group' => 'quotes'],
        ];

        foreach ($defaults as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }

    public function down(): void
    {
        Setting::whereIn('group', ['company', 'quotes'])->delete();
    }
};
