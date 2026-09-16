@extends('layouts.app')

@section('title', 'Paramètres')

@section('sidebar')
    <x-admin-sidebar />
@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Paramètres</h1>
    <p class="text-gray-500 text-sm mt-1">Configuration de l'ensemble du panel</p>
</div>

<div x-data="{ tab: 'general' }" class="flex gap-6">

    {{-- Tab nav --}}
    <div class="w-48 shrink-0">
        <nav class="space-y-1">
            @foreach([
                ['general',    '⚙️', 'Général'],
                ['company',    '🏢', 'Société'],
                ['quotes',     '📋', 'Devis & Fact.'],
                ['einvoicing', '🧾', 'Fact. électron.'],
                ['proxmox',    '🖥️', 'Proxmox'],
                ['cyberpanel', '🌐', 'CyberPanel'],
                ['ldap',       '🗂️', 'LDAP'],
                ['synology',   '☁️', 'Synology'],
                ['npm',        '🔀', 'Nginx PM'],
                ['stripe',     '💳', 'Stripe'],
                ['mail',       '📧', 'Mail / SMTP'],
                ['tailscale',  '🔒', 'Tailscale'],
            ] as [$key, $icon, $label])
            <button @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-600 hover:bg-gray-50'"
                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-left transition">
                <span>{{ $icon }}</span> {{ $label }}
            </button>
            @endforeach
        </nav>
    </div>

    {{-- Tab content --}}
    <div class="flex-1 min-w-0">

        {{-- Company --}}
        <div x-show="tab === 'company'">
            <form method="POST" action="{{ route('admin.settings.company') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                @csrf
                <h2 class="font-semibold text-gray-800 mb-4">Informations société</h2>
                <p class="text-sm text-gray-500 -mt-2">Ces informations apparaissent sur les devis et factures.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'entreprise <span class="text-red-500">*</span></label>
                        <input type="text" name="company_name" value="{{ $settings['company_name'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            placeholder="Acme SARL, John Doe Consulting...">
                        <p class="text-xs text-gray-400 mt-1">Affiché sur les devis et factures. Différent du nom du panel (onglet Général).</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SIREN / SIRET</label>
                        <input type="text" name="company_siren" value="{{ $settings['company_siren'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            placeholder="123456789 ou 12345678900012">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Forme juridique</label>
                        <input type="text" name="company_legal_form" value="{{ $settings['company_legal_form'] ?? 'Micro-entreprise' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            placeholder="Micro-entreprise, SARL, SAS...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">N° RCS / RM</label>
                        <input type="text" name="company_rcs" value="{{ $settings['company_rcs'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            placeholder="RCS Ville 123456789">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                        <input type="text" name="company_phone" value="{{ $settings['company_phone'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            placeholder="01 23 45 67 89">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Adresse postale</label>
                        <input type="text" name="company_address" value="{{ $settings['company_address'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            placeholder="12 rue de la Paix, 75001 Paris">
                        <p class="text-xs text-gray-400 mt-1">Apparaît sur les devis et factures PDF.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">N° TVA intracommunautaire</label>
                    <input type="text" name="company_vat_number" value="{{ $settings['company_vat_number'] ?? '' }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        placeholder="FR12345678901">
                    <p class="text-xs text-gray-400 mt-1">Obligatoire pour la facturation électronique EN 16931 (B2B assujettis à TVA).</p>
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Coordonnées bancaires (RIB)</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">IBAN</label>
                            <input type="text" name="company_iban" value="{{ $settings['company_iban'] ?? '' }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                placeholder="FR76 XXXX XXXX XXXX XXXX XXXX XXX">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">BIC / SWIFT</label>
                            <input type="text" name="company_bic" value="{{ $settings['company_bic'] ?? '' }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                placeholder="XXXXFRXXXX">
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100">
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>

        {{-- Quotes & Invoicing --}}
        <div x-show="tab === 'quotes'">
            <form method="POST" action="{{ route('admin.settings.quotes') }}" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                @csrf
                <h2 class="font-semibold text-gray-800 mb-4">Devis & Facturation</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Validité devis (jours)</label>
                        <input type="number" name="quote_validity_days" value="{{ $settings['quote_validity_days'] ?? 30 }}"
                            min="1" max="365"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Délai de paiement (jours)</label>
                        <input type="number" name="invoice_payment_days" value="{{ $settings['invoice_payment_days'] ?? 30 }}"
                            min="1" max="365"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Taux pénalités retard (% annuel)</label>
                        <input type="number" name="invoice_late_penalty" value="{{ $settings['invoice_late_penalty'] ?? 10 }}"
                            min="0" max="100" step="0.5"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <p class="text-xs text-gray-400 mt-1">Mention légale obligatoire sur les factures B2B</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Indemnité forfaitaire de recouvrement (€)</label>
                        <input type="number" name="invoice_recovery_fee" value="{{ $settings['invoice_recovery_fee'] ?? 40 }}"
                            min="0" step="1"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <p class="text-xs text-gray-400 mt-1">Minimum légal : 40 € pour les B2B</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mention TVA</label>
                    <input type="text" name="vat_mention" value="{{ $settings['vat_mention'] ?? 'TVA non applicable, art. 293 B du CGI' }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes par défaut sur les devis</label>
                    <textarea name="quote_default_notes" rows="3"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ $settings['quote_default_notes'] ?? '' }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Conditions Générales de Vente (PDF)</label>
                    <input type="file" name="cgv_file" accept=".pdf"
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    @if(!empty($settings['cgv_path']))
                    <p class="text-xs text-green-600 mt-1">CGV actuelles : {{ basename($settings['cgv_path']) }}</p>
                    @endif
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-1">Déclaration URSSAF (auto-entrepreneur)</h3>
                    <p class="text-xs text-gray-500 mb-3">Génère automatiquement chaque mois un PDF récapitulatif du chiffre d'affaires encaissé le mois précédent et l'envoie par email en rappel de la déclaration URSSAF.</p>

                    <div class="flex items-center gap-2 mb-3">
                        <input type="checkbox" name="urssaf_report_enabled" id="urssaf_report_enabled" value="1"
                            {{ ($settings['urssaf_report_enabled'] ?? '0') === '1' ? 'checked' : '' }}
                            class="rounded border-gray-300 text-indigo-600">
                        <label for="urssaf_report_enabled" class="text-sm text-gray-700">Activer l'envoi automatique du rapport mensuel</label>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jour du mois d'envoi</label>
                            <input type="number" name="urssaf_report_day" value="{{ $settings['urssaf_report_day'] ?? 1 }}"
                                min="1" max="28"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <p class="text-xs text-gray-400 mt-1">Le rapport du mois précédent est envoyé ce jour-là (max 28 pour couvrir février)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email destinataire</label>
                            <input type="email" name="urssaf_recipient_email" value="{{ $settings['urssaf_recipient_email'] ?? $settings['support_email'] ?? '' }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100">
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>

        {{-- General --}}
        <div x-show="tab === 'general'">
            <form method="POST" action="{{ route('admin.settings.general') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                @csrf
                <h2 class="font-semibold text-gray-800 mb-4">Paramètres généraux</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom du panel</label>
                        <input type="text" name="app_name" value="{{ $settings['app_name'] ?? config('app.name') }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL de l'application</label>
                        <input type="url" name="app_url" value="{{ $settings['app_url'] ?? config('app.url') }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email support</label>
                        <input type="email" name="support_email" value="{{ $settings['support_email'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Domaine base VMs (ex: vms.mondomaine.fr)</label>
                        <input type="text" name="vms_base_domain" value="{{ $settings['vms_base_domain'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Devise</label>
                        <select name="default_currency" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="EUR" {{ ($settings['default_currency'] ?? 'EUR') === 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                            <option value="USD" {{ ($settings['default_currency'] ?? '') === 'USD' ? 'selected' : '' }}>USD ($)</option>
                            <option value="GBP" {{ ($settings['default_currency'] ?? '') === 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Taux de TVA (%)</label>
                        <input type="number" name="tax_rate" value="{{ $settings['tax_rate'] ?? '20' }}" step="0.1" min="0" max="100"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="registration_open" id="reg_open" value="1"
                        {{ ($settings['registration_open'] ?? '1') === '1' ? 'checked' : '' }}
                        class="rounded border-gray-300 text-indigo-600">
                    <label for="reg_open" class="text-sm text-gray-700">Inscriptions ouvertes au public</label>
                </div>

                <div class="pt-4 border-t border-gray-100">
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>

        {{-- E-invoicing --}}
        <div x-show="tab === 'einvoicing'">
            <form method="POST" action="{{ route('admin.settings.einvoicing') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                @csrf
                <h2 class="font-semibold text-gray-800 mb-1">Facturation électronique (Factur-X EN 16931)</h2>
                <p class="text-sm text-gray-500 -mt-1">Obligatoire pour le B2B français à partir de septembre 2026. Active l'intégration de l'XML Factur-X dans les PDF de factures et prépare la transmission via PPF (Chorus Pro).</p>

                <div class="flex items-center gap-3 py-2 px-4 rounded-lg bg-amber-50 border border-amber-200">
                    <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <p class="text-sm text-amber-700">Avant d'activer, renseignez votre N° TVA intracommunautaire dans l'onglet <strong>Société</strong>.</p>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="einvoicing_enabled" id="einvoicing_enabled" value="1"
                        {{ ($settings['einvoicing_enabled'] ?? '0') === '1' ? 'checked' : '' }}
                        class="rounded border-gray-300 text-indigo-600">
                    <label for="einvoicing_enabled" class="text-sm text-gray-700 font-medium">Activer la facturation électronique (PDF/A-3 + Factur-X EN 16931)</label>
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Intégration PPF / Chorus Pro <span class="text-xs font-normal text-gray-400 ml-2">(transmission obligatoire vers les clients B2B assujettis)</span></h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SIRET émetteur (pour PPF)</label>
                            <input type="text" name="ppf_siret" value="{{ $settings['ppf_siret'] ?? '' }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                placeholder="12345678900012">
                            <p class="text-xs text-gray-400 mt-1">SIRET à 14 chiffres utilisé pour l'authentification PPF.</p>
                        </div>
                    </div>
                    <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-xs text-blue-700">
                            <strong>Prochaine étape :</strong> L'intégration API Chorus Pro (PPF) est préparée. Dès que votre compte Chorus Pro est ouvert, renseignez le SIRET ci-dessus pour activer la transmission automatique des factures B2B.
                        </p>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100">
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>

        {{-- Proxmox --}}
        <div x-show="tab === 'proxmox'" x-data="testConnection('proxmox')">
            <form method="POST" action="{{ route('admin.settings.proxmox') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                @csrf
                <h2 class="font-semibold text-gray-800 mb-4">Configuration Proxmox</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL Proxmox (ex: https://192.168.1.10:8006)</label>
                        <input type="url" name="proxmox_host" value="{{ $settings['proxmox_host'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Utilisateur</label>
                        <input type="text" name="proxmox_user" value="{{ $settings['proxmox_user'] ?? 'root' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                        <input type="password" name="proxmox_password" value="{{ $settings['proxmox_password'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Realm</label>
                        <input type="text" name="proxmox_realm" value="{{ $settings['proxmox_realm'] ?? 'pam' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nœud par défaut</label>
                        <input type="text" name="proxmox_node" value="{{ $settings['proxmox_node'] ?? 'pve' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stockage par défaut</label>
                        <input type="text" name="proxmox_default_storage" value="{{ $settings['proxmox_default_storage'] ?? 'local-lvm' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            placeholder="local-lvm">
                        <p class="mt-1 text-xs text-gray-400">Stockage utilisé lors du provisionnement automatique (ex: local-lvm, ceph-vm…)</p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100 flex items-center gap-3">
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">Enregistrer</button>
                    <button type="button" @click="test()" :disabled="loading"
                        class="px-5 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition disabled:opacity-50">
                        <span x-text="loading ? 'Test...' : 'Tester la connexion'"></span>
                    </button>
                    <span x-show="message" :class="success ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium" x-text="message"></span>
                </div>
            </form>
        </div>

        {{-- CyberPanel --}}
        <div x-show="tab === 'cyberpanel'" x-data="testConnection('cyberpanel')">
            <form method="POST" action="{{ route('admin.settings.cyberpanel') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                @csrf
                <h2 class="font-semibold text-gray-800 mb-4">Configuration CyberPanel</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL CyberPanel (ex: https://192.168.1.20:8090)</label>
                        <input type="url" name="cyberpanel_host" value="{{ $settings['cyberpanel_host'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Utilisateur admin</label>
                        <input type="text" name="cyberpanel_user" value="{{ $settings['cyberpanel_user'] ?? 'admin' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                        <input type="password" name="cyberpanel_password" value="{{ $settings['cyberpanel_password'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">IP Tailscale CyberPanel</label>
                        <input type="text" name="cyberpanel_tailscale_ip" value="{{ $settings['cyberpanel_tailscale_ip'] ?? '' }}"
                            placeholder="100.x.x.x"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                        <p class="text-xs text-gray-400 mt-1">IP Tailscale du serveur CyberPanel, utilisée comme cible des proxy hosts "Hébergement" créés par les clients.</p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100 flex items-center gap-3">
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">Enregistrer</button>
                    <button type="button" @click="test()" :disabled="loading"
                        class="px-5 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition disabled:opacity-50">
                        <span x-text="loading ? 'Test...' : 'Tester la connexion'"></span>
                    </button>
                    <span x-show="message" :class="success ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium" x-text="message"></span>
                </div>
            </form>
        </div>

        {{-- LDAP --}}
        <div x-show="tab === 'ldap'" x-data="ldapSettings('{{ $settings['ldap_users_dn'] ?? '' }}', '{{ $settings['ldap_groups_dn'] ?? '' }}')">
            <form method="POST" action="{{ route('admin.settings.ldap') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                @csrf
                <h2 class="font-semibold text-gray-800 mb-4">Configuration LDAP (Synology LDAP Server)</h2>
                <p class="text-xs text-gray-400 -mt-3 mb-2">Serveur d'annuaire utilisé pour créer les comptes clients et les rattacher à un groupe selon leur abonnement. Nécessite l'extension PHP <code class="bg-gray-100 px-1 rounded">ldap</code> sur le serveur ABPanel.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hôte / IP Tailscale</label>
                        <input type="text" name="ldap_host" value="{{ $settings['ldap_host'] ?? '' }}"
                            placeholder="100.x.x.x"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                        <input type="number" name="ldap_port" value="{{ $settings['ldap_port'] ?? '389' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">DN de bind (compte admin LDAP)</label>
                        <input type="text" name="ldap_bind_dn" value="{{ $settings['ldap_bind_dn'] ?? '' }}"
                            placeholder="uid=admin,cn=users,dc=exemple,dc=com"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe de bind</label>
                        <input type="password" name="ldap_bind_password" value="{{ $settings['ldap_bind_password'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">DN des utilisateurs</label>
                        <input type="text" name="ldap_users_dn" x-model="usersDn"
                            placeholder="cn=users,dc=exemple,dc=com"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                        <p class="text-xs text-gray-400 mt-1">Conteneur dans lequel les comptes clients sont créés (visible dans DSM → LDAP Server → Utilisateurs).</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">DN des groupes</label>
                        <input type="text" name="ldap_groups_dn" x-model="groupsDn"
                            placeholder="cn=groups,dc=exemple,dc=com"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                        <p class="text-xs text-gray-400 mt-1">Conteneur des groupes (posixGroup) — un groupe par abonnement, à créer au préalable dans DSM et à référencer sur chaque plan.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">GID par défaut</label>
                        <input type="number" name="ldap_default_gid" value="{{ $settings['ldap_default_gid'] ?? '100' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <p class="text-xs text-gray-400 mt-1">Groupe primaire POSIX affecté aux nouveaux comptes (l'appartenance à l'abonnement se fait via le groupe secondaire du plan).</p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100 flex items-center gap-3 flex-wrap">
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">Enregistrer</button>
                    <button type="button" @click="test()" :disabled="loading"
                        class="px-5 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition disabled:opacity-50">
                        <span x-text="loading ? 'Test...' : 'Tester la connexion'"></span>
                    </button>
                    <button type="button" @click="discover()" :disabled="discovering"
                        class="px-5 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition disabled:opacity-50">
                        <span x-text="discovering ? 'Recherche...' : '🔍 Détecter les DN'"></span>
                    </button>
                    <span x-show="message" :class="success ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium" x-text="message"></span>
                </div>

                <div x-show="discoverError" class="text-xs text-red-500" x-text="discoverError"></div>

                <div x-show="containers.length > 0" x-cloak class="border border-gray-100 rounded-lg overflow-hidden">
                    <div class="px-4 py-2 bg-gray-50 text-xs text-gray-500">DN de base détecté : <span class="font-mono" x-text="baseDn"></span> — cliquez sur "Utiliser" pour remplir le champ correspondant (puis Enregistrer).</div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 text-xs uppercase bg-gray-50">
                                <th class="px-4 py-2">Conteneur</th>
                                <th class="px-4 py-2">Comptes posix</th>
                                <th class="px-4 py-2">Groupes posix</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="c in containers" :key="c.dn">
                                <tr>
                                    <td class="px-4 py-2 font-mono text-gray-800" x-text="c.dn"></td>
                                    <td class="px-4 py-2 text-gray-600" x-text="c.users_count"></td>
                                    <td class="px-4 py-2 text-gray-600" x-text="c.groups_count"></td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap">
                                        <button type="button" @click="usersDn = c.dn" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium mr-3">Utiliser comme DN utilisateurs</button>
                                        <button type="button" @click="groupsDn = c.dn" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Utiliser comme DN groupes</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>

        {{-- Synology --}}
        <div x-show="tab === 'synology'" x-data="testConnection('synology')">
            <form method="POST" action="{{ route('admin.settings.synology') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                @csrf
                <h2 class="font-semibold text-gray-800 mb-4">Configuration Synology (DSM)</h2>
                <p class="text-xs text-gray-400 -mt-3 mb-2">Utilisé pour appliquer le quota de stockage personnel de chaque client, en fonction du plan souscrit. Distinct du serveur LDAP (identité) ci-dessus.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL DSM (ex: https://100.x.x.x:5001)</label>
                        <input type="url" name="synology_host" value="{{ $settings['synology_host'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Compte admin DSM</label>
                        <input type="text" name="synology_user" value="{{ $settings['synology_user'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                        <input type="password" name="synology_password" value="{{ $settings['synology_password'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dossier partagé "Utilisateur personnel" (Home)</label>
                        <input type="text" name="synology_cloud_shared_folder" value="{{ $settings['synology_cloud_shared_folder'] ?? '' }}"
                            placeholder="/volume1/homes"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                        <p class="text-xs text-gray-400 mt-1">Chemin du dossier partagé du service "Utilisateur personnel" de DSM (Panneau de configuration → Utilisateur personnel), sous lequel chaque client a déjà son propre sous-dossier privé. Le quota est appliqué par utilisateur à l'intérieur, pas à un espace commun — personne n'accède à l'espace d'un autre client.</p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100 flex items-center gap-3">
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">Enregistrer</button>
                    <button type="button" @click="test()" :disabled="loading"
                        class="px-5 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition disabled:opacity-50">
                        <span x-text="loading ? 'Test...' : 'Tester la connexion'"></span>
                    </button>
                    <span x-show="message" :class="success ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium" x-text="message"></span>
                </div>
            </form>
        </div>

        {{-- NPM --}}
        <div x-show="tab === 'npm'" x-data="testConnection('npm')">
            <form method="POST" action="{{ route('admin.settings.npm') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                @csrf
                <h2 class="font-semibold text-gray-800 mb-4">Configuration Nginx Proxy Manager</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL NPM (ex: http://192.168.1.5:81)</label>
                        <input type="url" name="npm_host" value="{{ $settings['npm_host'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="npm_email" value="{{ $settings['npm_email'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                        <input type="password" name="npm_password" value="{{ $settings['npm_password'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cooldown suppression domaine (minutes)</label>
                        <input type="number" name="domain_deletion_cooldown" value="{{ $settings['domain_deletion_cooldown'] ?? 60 }}" min="0"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <p class="text-xs text-gray-400 mt-1">Délai avant qu'un client puisse recréer un domaine qu'il vient de supprimer.</p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100 flex items-center gap-3">
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">Enregistrer</button>
                    <button type="button" @click="test()" :disabled="loading"
                        class="px-5 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition disabled:opacity-50">
                        <span x-text="loading ? 'Test...' : 'Tester la connexion'"></span>
                    </button>
                    <span x-show="message" :class="success ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium" x-text="message"></span>
                </div>
            </form>
        </div>

        {{-- Stripe --}}
        <div x-show="tab === 'stripe'" x-data="testConnection('stripe')">
            <form method="POST" action="{{ route('admin.settings.stripe') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                @csrf
                <h2 class="font-semibold text-gray-800 mb-4">Configuration Stripe</h2>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Clé publique (pk_...)</label>
                    <input type="text" name="stripe_public_key" value="{{ $settings['stripe_public_key'] ?? '' }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Clé secrète (sk_...)</label>
                    <input type="password" name="stripe_secret_key" value="{{ $settings['stripe_secret_key'] ?? '' }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Webhook secret (whsec_...)</label>
                    <input type="password" name="stripe_webhook_secret" value="{{ $settings['stripe_webhook_secret'] ?? '' }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                    <p class="text-xs text-gray-400 mt-1">Endpoint webhook : <code class="bg-gray-100 px-1 rounded">{{ url('/webhooks/stripe') }}</code></p>
                </div>
                <div class="pt-4 border-t border-gray-100 flex items-center gap-3">
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">Enregistrer</button>
                    <button type="button" @click="test()" :disabled="loading"
                        class="px-5 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition disabled:opacity-50">
                        <span x-text="loading ? 'Test...' : 'Tester la connexion'"></span>
                    </button>
                    <span x-show="message" :class="success ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium" x-text="message"></span>
                </div>
            </form>
        </div>

        {{-- Mail --}}
        <div x-show="tab === 'mail'" x-data="{ ...testConnection('mail'), testEmail: '{{ auth()->user()->email }}' }">
            <form method="POST" action="{{ route('admin.settings.mail') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                @csrf
                <h2 class="font-semibold text-gray-800 mb-4">Configuration Mail / SMTP</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Serveur SMTP</label>
                        <input type="text" name="mail_host" value="{{ $settings['mail_host'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                        <input type="number" name="mail_port" value="{{ $settings['mail_port'] ?? '587' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Utilisateur</label>
                        <input type="text" name="mail_username" value="{{ $settings['mail_username'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                        <input type="password" name="mail_password" value="{{ $settings['mail_password'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Chiffrement</label>
                        <select name="mail_encryption" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="tls" {{ ($settings['mail_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ ($settings['mail_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                            <option value="" {{ ($settings['mail_encryption'] ?? '') === '' ? 'selected' : '' }}>Aucun</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expéditeur (email)</label>
                        <input type="email" name="mail_from_address" value="{{ $settings['mail_from_address'] ?? '' }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom expéditeur</label>
                        <input type="text" name="mail_from_name" value="{{ $settings['mail_from_name'] ?? config('app.name') }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100 flex flex-wrap items-center gap-3">
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">Enregistrer</button>
                    <div class="flex items-center gap-2">
                        <input type="email" x-model="testEmail" placeholder="Email de test"
                            class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none w-48">
                        <button type="button" @click="test({ email: testEmail })" :disabled="loading"
                            class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition disabled:opacity-50">
                            <span x-text="loading ? 'Envoi...' : 'Tester'"></span>
                        </button>
                    </div>
                    <span x-show="message" :class="success ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium" x-text="message"></span>
                </div>
            </form>
        </div>

        {{-- Tailscale --}}
        <div x-show="tab === 'tailscale'" x-data="testConnection('tailscale')">
            <form method="POST" action="{{ route('admin.settings.tailscale') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                @csrf
                <h2 class="font-semibold text-gray-800 mb-1">Configuration Tailscale</h2>
                <p class="text-sm text-gray-500 -mt-2">
                    Tailscale permet d'attribuer une IP privée à chaque VPS.
                    L'authentification passe par un <strong>OAuth client</strong> — les credentials n'expirent pas et les auth keys sont générées dynamiquement à chaque provisionnement.
                </p>

                <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-800">
                    <strong>Comment créer un OAuth client :</strong>
                    Allez dans <a href="https://login.tailscale.com/admin/settings/oauth" target="_blank" class="underline">Tailscale → Settings → OAuth clients</a>
                    et créez un client avec les scopes <code class="bg-blue-100 px-1 rounded">devices:read</code> et <code class="bg-blue-100 px-1 rounded">auth_keys:write</code>.
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">OAuth Client ID</label>
                        <input type="text" name="tailscale_oauth_client_id" value="{{ $settings['tailscale_oauth_client_id'] ?? '' }}"
                            placeholder="k123456CNTRL"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">OAuth Client Secret</label>
                        <input type="password" name="tailscale_oauth_client_secret" value="{{ $settings['tailscale_oauth_client_secret'] ?? '' }}"
                            placeholder="tskey-client-..."
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                        <p class="text-xs text-gray-400 mt-1">Non expirant. Permet d'obtenir des access tokens et de générer des auth keys.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tailnet (organisation)</label>
                        <input type="text" name="tailscale_tailnet" value="{{ $settings['tailscale_tailnet'] ?? '-' }}"
                            placeholder="example.com ou -"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                        <p class="text-xs text-gray-400 mt-1">Votre organisation Tailscale. Laissez <code>-</code> pour le tailnet par défaut.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tag des VM clientes</label>
                        <input type="text" name="tailscale_client_vm_tag" value="{{ $settings['tailscale_client_vm_tag'] ?? 'tag:client-vm' }}"
                            placeholder="tag:client-vm"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                        <p class="text-xs text-gray-400 mt-1">Appliqué automatiquement à chaque VM cliente rejoignant le tailnet. <strong>Sans ACL Tailscale configurée pour isoler ce tag, il ne sert à rien</strong> — voir l'encart ci-dessous.</p>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-800">
                    <strong>⚠️ Isolation réseau — à faire une fois dans Tailscale :</strong> le tag seul n'isole rien tant que la politique d'ACL du tailnet (<a href="https://login.tailscale.com/admin/acls" target="_blank" class="underline">Tailscale → Access controls</a>) n'interdit pas explicitement à ce tag de joindre le reste. Sans ACL restrictive, une VM cliente reste sur la politique "tout le monde voit et joint tout le monde" et <code class="bg-amber-100 px-1 rounded">tailscale status</code> y listera votre infra perso.
                </div>

                <div class="pt-2 flex items-center gap-3 flex-wrap">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                        Enregistrer
                    </button>
                    <button type="button" @click="test()" :disabled="loading"
                        class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition disabled:opacity-50">
                        <span x-text="loading ? 'Test...' : 'Tester la connexion'"></span>
                    </button>
                    <span x-show="message" :class="success ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium" x-text="message"></span>
                </div>
            </form>

            {{-- Génération manuelle d'une auth key --}}
            <div class="mt-4 bg-white rounded-xl shadow-sm border border-gray-100 p-5"
                 x-data="{ loading: false, key: '', error: '', expiry: 3600, ephemeral: true, tagged: true }">
                <h3 class="font-semibold text-gray-800 text-sm mb-1">Générer une auth key à la demande</h3>
                <p class="text-sm text-gray-500 mb-3">
                    Pour les templates Proxmox ou les installations manuelles. La clé est générée via l'API et valable pour la durée choisie.
                </p>
                <div class="flex items-center gap-3 flex-wrap">
                    <select x-model="expiry" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="3600">Expire dans 1 heure</option>
                        <option value="86400">Expire dans 24 heures</option>
                        <option value="604800">Expire dans 7 jours</option>
                    </select>
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" x-model="ephemeral" class="rounded border-gray-300 text-indigo-600">
                        Éphémère (device supprimé quand hors ligne)
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" x-model="tagged" class="rounded border-gray-300 text-indigo-600">
                        Tag VM cliente (décochez pour un device infra sans tag)
                    </label>
                    <button type="button" @click="
                        loading = true; key = ''; error = '';
                        fetch('{{ route('admin.settings.tailscale.generate-key') }}', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
                            body: JSON.stringify({expiry, ephemeral, tagged})
                        }).then(r => r.json()).then(d => {
                            if (d.key) key = d.key; else error = d.error || 'Erreur';
                        }).catch(() => error = 'Erreur réseau').finally(() => loading = false)
                    " :disabled="loading"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition disabled:opacity-50">
                        <span x-text="loading ? 'Génération...' : 'Générer'"></span>
                    </button>
                </div>
                <div x-show="key" class="mt-3">
                    <p class="text-xs text-gray-500 mb-1">Auth key générée (copiez-la, elle ne sera pas re-affichée) :</p>
                    <pre class="bg-gray-800 text-green-300 rounded-lg p-3 text-xs font-mono overflow-x-auto whitespace-pre-wrap">#!/bin/bash
curl -fsSL https://tailscale.com/install.sh | sh
tailscale up --authkey=<span x-text="key"></span> --hostname=NOM-VPS --accept-routes</pre>
                </div>
                <p x-show="error" class="mt-2 text-sm text-red-600" x-text="error"></p>
            </div>

            <div class="mt-4 bg-blue-50 rounded-xl border border-blue-200 p-5">
                <h3 class="font-semibold text-blue-900 text-sm mb-2">Synchronisation des IPs</h3>
                <p class="text-sm text-blue-700 mb-3">
                    La commande suivante interroge l'API Tailscale et met à jour les IPs de tous les VPS enregistrés dans le panel.
                    Ajoutez-la à un cron toutes les 5 minutes.
                </p>
                <pre class="bg-blue-900 text-green-300 rounded-lg p-3 text-xs font-mono overflow-x-auto">php artisan vm:sync-tailscale</pre>
                <p class="text-xs text-blue-600 mt-2">Pour un seul VPS : <code>php artisan vm:sync-tailscale --vm=ID</code></p>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function ldapSettings(initialUsersDn, initialGroupsDn) {
    return {
        ...testConnection('ldap'),
        usersDn: initialUsersDn || '',
        groupsDn: initialGroupsDn || '',
        discovering: false,
        discoverError: '',
        baseDn: '',
        containers: [],
        async discover() {
            this.discovering = true;
            this.discoverError = '';
            this.containers = [];
            try {
                const res = await fetch('{{ route('admin.settings.ldap.discover') }}');
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    this.discoverError = `Réponse inattendue du serveur (HTTP ${res.status}). Vérifiez que le serveur a bien été mis à jour (git pull) et que l'extension PHP ldap est installée.`;
                    return;
                }
                if (data.success && data.containers && data.containers.length > 0) {
                    this.baseDn = data.base_dn;
                    this.containers = data.containers;
                } else {
                    this.discoverError = data.message || 'Aucun conteneur trouvé. Enregistrez d\'abord la connexion ci-dessus.';
                }
            } catch (e) {
                this.discoverError = 'Erreur réseau : ' + e.message;
            } finally {
                this.discovering = false;
            }
        }
    };
}

function testConnection(service) {
    return {
        loading: false,
        success: false,
        message: '',
        async test(extra = {}) {
            this.loading = true;
            this.message = '';
            try {
                const res = await fetch('{{ route('admin.settings.test', '__SERVICE__') }}'.replace('__SERVICE__', service), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(extra),
                });
                const data = await res.json();
                this.success = data.success;
                this.message = data.message;
            } catch (e) {
                this.success = false;
                this.message = 'Erreur réseau';
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endpush
