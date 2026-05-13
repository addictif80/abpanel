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
                ['proxmox',    '🖥️', 'Proxmox'],
                ['cyberpanel', '🌐', 'CyberPanel'],
                ['npm',        '🔀', 'Nginx PM'],
                ['stripe',     '💳', 'Stripe'],
                ['mail',       '📧', 'Mail / SMTP'],
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

    </div>
</div>
@endsection

@push('scripts')
<script>
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
