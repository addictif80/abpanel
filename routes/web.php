<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Client;
use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicQuoteController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Installation wizard
Route::middleware('not.installed')->prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');
    Route::get('/database', [InstallController::class, 'database'])->name('database');
    Route::post('/database', [InstallController::class, 'saveDatabase'])->name('database.save');
    Route::get('/mail', [InstallController::class, 'mail'])->name('mail');
    Route::post('/mail', [InstallController::class, 'saveMail'])->name('mail.save');
    Route::post('/mail/test', [InstallController::class, 'testSmtp'])->name('mail.test');
    Route::get('/stripe', [InstallController::class, 'stripe'])->name('stripe');
    Route::post('/stripe', [InstallController::class, 'saveStripe'])->name('stripe.save');
    Route::get('/admin', [InstallController::class, 'admin'])->name('admin');
    Route::post('/admin', [InstallController::class, 'saveAdmin'])->name('admin.save');
    Route::get('/complete', [InstallController::class, 'complete'])->name('complete');
});

// Public routes (require app to be installed)
Route::middleware('installed')->group(function () {
    // Auth
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
    Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register')->middleware('guest');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('guest');

    // Password reset
    Route::middleware('guest')->group(function () {
        Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
        Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
        Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
    });

    // Public quote access (token-based, no auth required)
    Route::prefix('quotes')->name('quotes.')->middleware('throttle:30,1')->group(function () {
        Route::get('/{token}', [PublicQuoteController::class, 'show'])->name('public');
        Route::post('/{token}/accept', [PublicQuoteController::class, 'accept'])->name('accept')->middleware('throttle:5,1');
        Route::post('/{token}/refuse', [PublicQuoteController::class, 'refuse'])->name('refuse')->middleware('throttle:5,1');
        Route::get('/{token}/download', [PublicQuoteController::class, 'downloadPdf'])->name('download-pdf');
    });

    // Client portal
    Route::middleware(['auth', 'active.user'])->prefix('client')->name('client.')->group(function () {
        Route::get('/dashboard', [Client\DashboardController::class, 'index'])->name('dashboard');

        // VMs
        Route::prefix('vms')->name('vms.')->group(function () {
            Route::get('/', [Client\VmController::class, 'index'])->name('index');
            Route::get('/{vm}', [Client\VmController::class, 'show'])->name('show');
            Route::post('/{vm}/start', [Client\VmController::class, 'start'])->name('start');
            Route::post('/{vm}/stop', [Client\VmController::class, 'stop'])->name('stop');
            Route::post('/{vm}/hibernate', [Client\VmController::class, 'hibernate'])->name('hibernate');
            Route::post('/{vm}/resume', [Client\VmController::class, 'resume'])->name('resume');
            Route::post('/{vm}/reboot', [Client\VmController::class, 'reboot'])->name('reboot');
            Route::get('/{vm}/terminal', [Client\VmController::class, 'terminal'])->name('terminal');
            Route::post('/{vm}/domain', [Client\VmController::class, 'updateDomain'])->name('domain');
            Route::post('/{vm}/password', [Client\VmController::class, 'changeRootPassword'])->name('password');
            Route::post('/{vm}/force-stop', [Client\VmController::class, 'forceStop'])->name('forceStop');
            Route::get('/{vm}/reinstall', [Client\VmController::class, 'reinstall'])->name('reinstall');
            Route::post('/{vm}/reinstall', [Client\VmController::class, 'doReinstall'])->name('doReinstall');
            Route::get('/{vm}/cancel', [Client\VmController::class, 'cancelRequest'])->name('cancel');
            Route::post('/{vm}/cancel', [Client\VmController::class, 'cancelConfirm'])->name('cancelConfirm');
        });

        // Hosting
        Route::prefix('hosting')->name('hosting.')->group(function () {
            Route::get('/', [Client\HostingController::class, 'index'])->name('index');
            Route::get('/{hosting}/cancel', [Client\HostingController::class, 'cancelRequest'])->name('cancel');
            Route::post('/{hosting}/cancel', [Client\HostingController::class, 'cancelConfirm'])->name('cancelConfirm');
        });

        // Domains
        Route::prefix('domains')->name('domains.')->group(function () {
            Route::get('/', [Client\DomainController::class, 'index'])->name('index');
            Route::get('/create', [Client\DomainController::class, 'create'])->name('create');
            Route::post('/', [Client\DomainController::class, 'store'])->name('store');
            Route::post('/check-dns', [Client\DomainController::class, 'checkDnsAjax'])->name('check-dns');
            Route::post('/{domain}/ssl', [Client\DomainController::class, 'enableSsl'])->name('enable-ssl');
            Route::delete('/{domain}', [Client\DomainController::class, 'destroy'])->name('destroy');
        });

        // Plans & Checkout
        Route::prefix('checkout')->name('checkout.')->group(function () {
            Route::get('/plans', [Client\CheckoutController::class, 'plans'])->name('plans');
            Route::get('/plans/{plan}', [Client\CheckoutController::class, 'checkout'])->name('checkout');
            Route::post('/plans/{plan}/intent', [Client\CheckoutController::class, 'createIntent'])->name('intent');
            Route::get('/success', [Client\CheckoutController::class, 'success'])->name('success');
        });

        // Billing
        Route::prefix('billing')->name('billing.')->group(function () {
            Route::get('/', [Client\BillingController::class, 'index'])->name('index');
            Route::get('/invoices/{invoice}', [Client\BillingController::class, 'show'])->name('invoice');
            Route::get('/invoices/{invoice}/download', [Client\BillingController::class, 'download'])->name('invoice.download');
            Route::get('/invoices/{invoice}/facturx.xml', [Client\BillingController::class, 'downloadXml'])->name('invoice.facturx');
            Route::post('/invoices/{invoice}/pay-intent', [Client\BillingController::class, 'createPayIntent'])->name('invoice.pay-intent');
            Route::get('/invoices/{invoice}/pay', [Client\BillingController::class, 'payPage'])->name('invoice.pay');
            Route::get('/invoices/{invoice}/pay/success', [Client\BillingController::class, 'paySuccess'])->name('invoice.pay-success');
        });

        // Quotes
        Route::prefix('quotes')->name('quotes.')->group(function () {
            Route::get('/', [Client\QuoteController::class, 'index'])->name('index');
            Route::get('/{quote}', [Client\QuoteController::class, 'show'])->name('show');
            Route::post('/{quote}/accept', [Client\QuoteController::class, 'accept'])->name('accept');
            Route::post('/{quote}/refuse', [Client\QuoteController::class, 'refuse'])->name('refuse');
            Route::post('/{quote}/message', [Client\QuoteController::class, 'addMessage'])->name('message');
            Route::get('/{quote}/download', [Client\QuoteController::class, 'downloadPdf'])->name('download-pdf');
        });

        // Tickets
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/', [Client\TicketController::class, 'index'])->name('index');
            Route::get('/create', [Client\TicketController::class, 'create'])->name('create');
            Route::post('/', [Client\TicketController::class, 'store'])->name('store');
            Route::get('/{ticket}', [Client\TicketController::class, 'show'])->name('show');
            Route::post('/{ticket}/reply', [Client\TicketController::class, 'reply'])->name('reply');
        });

        // Projects
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('/', [Client\ProjectController::class, 'index'])->name('index');
            Route::get('/{project}', [Client\ProjectController::class, 'show'])->name('show');
            Route::post('/{project}/message', [Client\ProjectController::class, 'addMessage'])->name('message');
        });

        // Stop impersonating
        Route::get('/impersonate/stop', [Admin\ClientController::class, 'stopImpersonating'])->name('impersonate.stop');

        // Profile
        Route::get('/profile', [Client\ProfileController::class, 'index'])->name('profile');
        Route::put('/profile', [Client\ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [Client\ProfileController::class, 'updatePassword'])->name('profile.password');
    });

    // Admin panel
    Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [Admin\SettingsController::class, 'index'])->name('index');
            Route::post('/general', [Admin\SettingsController::class, 'saveGeneral'])->name('general');
            Route::post('/company', [Admin\SettingsController::class, 'saveCompany'])->name('company');
            Route::post('/quotes', [Admin\SettingsController::class, 'saveQuotes'])->name('quotes');
            Route::post('/einvoicing', [Admin\SettingsController::class, 'saveEinvoicing'])->name('einvoicing');
            Route::post('/proxmox', [Admin\SettingsController::class, 'saveProxmox'])->name('proxmox');
            Route::post('/cyberpanel', [Admin\SettingsController::class, 'saveCyberpanel'])->name('cyberpanel');
            Route::post('/npm', [Admin\SettingsController::class, 'saveNpm'])->name('npm');
            Route::post('/stripe', [Admin\SettingsController::class, 'saveStripe'])->name('stripe');
            Route::post('/mail', [Admin\SettingsController::class, 'saveMail'])->name('mail');
            Route::post('/tailscale', [Admin\SettingsController::class, 'saveTailscale'])->name('tailscale');
            Route::post('/test/{service}', [Admin\SettingsController::class, 'testConnection'])->name('test');
        });

        // Clients
        Route::resource('clients', Admin\ClientController::class);
        Route::post('/clients/{client}/reset-password', [Admin\ClientController::class, 'resetPassword'])->name('clients.reset-password');
        Route::post('/clients/{client}/impersonate', [Admin\ClientController::class, 'impersonate'])->name('clients.impersonate');

        // Products catalog
        Route::resource('products', Admin\ProductController::class)->except(['show']);

        // Quotes
        Route::prefix('quotes')->name('quotes.')->group(function () {
            Route::get('/', [Admin\QuoteController::class, 'index'])->name('index');
            Route::get('/templates', [Admin\QuoteController::class, 'templates'])->name('templates');
            Route::get('/create', [Admin\QuoteController::class, 'create'])->name('create');
            Route::post('/', [Admin\QuoteController::class, 'store'])->name('store');
            Route::get('/{quote}', [Admin\QuoteController::class, 'show'])->name('show');
            Route::get('/{quote}/edit', [Admin\QuoteController::class, 'edit'])->name('edit');
            Route::put('/{quote}', [Admin\QuoteController::class, 'update'])->name('update');
            Route::post('/{quote}/send', [Admin\QuoteController::class, 'send'])->name('send');
            Route::post('/{quote}/reminder', [Admin\QuoteController::class, 'reminder'])->name('reminder');
            Route::post('/{quote}/convert', [Admin\QuoteController::class, 'convert'])->name('convert');
            Route::post('/{quote}/cancel', [Admin\QuoteController::class, 'cancel'])->name('cancel');
            Route::post('/{quote}/save-template', [Admin\QuoteController::class, 'saveAsTemplate'])->name('save-template');
            Route::get('/{quote}/download-pdf', [Admin\QuoteController::class, 'downloadPdf'])->name('download-pdf');
            Route::post('/{quote}/message', [Admin\QuoteController::class, 'addMessage'])->name('message');
            Route::delete('/{quote}', [Admin\QuoteController::class, 'destroy'])->name('destroy');
        });

        // Credit notes (avoirs)
        Route::prefix('credit-notes')->name('credit-notes.')->group(function () {
            Route::get('/', [Admin\CreditNoteController::class, 'index'])->name('index');
            Route::get('/create', [Admin\CreditNoteController::class, 'create'])->name('create');
            Route::get('/invoices', [Admin\CreditNoteController::class, 'getInvoices'])->name('invoices');
            Route::post('/', [Admin\CreditNoteController::class, 'store'])->name('store');
            Route::get('/{creditNote}', [Admin\CreditNoteController::class, 'show'])->name('show');
            Route::post('/{creditNote}/issue', [Admin\CreditNoteController::class, 'issue'])->name('issue');
            Route::post('/{creditNote}/apply', [Admin\CreditNoteController::class, 'apply'])->name('apply');
        });

        // VMs — specific routes must come before the resource to avoid {vm} catching them
        Route::get('/vms/disk-storages', [Admin\VmController::class, 'diskStorages'])->name('vms.disk-storages');
        Route::prefix('vms/import')->name('vms.import.')->group(function () {
            Route::get('/', [Admin\VmController::class, 'importIndex'])->name('index');
            Route::get('/{node}/{vmid}', [Admin\VmController::class, 'importShow'])->name('show');
            Route::post('/{node}/{vmid}', [Admin\VmController::class, 'importStore'])->name('store');
        });
        Route::resource('vms', Admin\VmController::class);

        // Mail logs
        Route::prefix('mail-logs')->name('mail-logs.')->group(function () {
            Route::get('/', [Admin\MailLogController::class, 'index'])->name('index');
            Route::get('/{mailLog}', [Admin\MailLogController::class, 'show'])->name('show');
        });

        // Mail templates
        Route::prefix('mail-templates')->name('mail-templates.')->group(function () {
            Route::get('/', [Admin\MailTemplateController::class, 'index'])->name('index');
            Route::get('/create', [Admin\MailTemplateController::class, 'create'])->name('create');
            Route::post('/', [Admin\MailTemplateController::class, 'store'])->name('store');
            Route::get('/{template}/edit', [Admin\MailTemplateController::class, 'edit'])->name('edit');
            Route::put('/{template}', [Admin\MailTemplateController::class, 'update'])->name('update');
            Route::post('/{template}/test', [Admin\MailTemplateController::class, 'sendTest'])->name('test');
        });

        // Newsletter
        Route::prefix('newsletter')->name('newsletter.')->group(function () {
            Route::get('/', [Admin\NewsletterController::class, 'index'])->name('index');
            Route::resource('lists', Admin\NewsletterListController::class);
            Route::post('/lists/{list}/subscribers', [Admin\NewsletterListController::class, 'addSubscriber'])->name('lists.subscribers.store');
            Route::resource('campaigns', Admin\NewsletterCampaignController::class);
            Route::post('/campaigns/{campaign}/send', [Admin\NewsletterController::class, 'send'])->name('campaigns.send');
        });

        // Hosting accounts
        Route::prefix('hosting')->name('hosting.')->group(function () {
            Route::get('/', [Admin\HostingController::class, 'index'])->name('index');
            Route::prefix('import')->name('import.')->group(function () {
                Route::get('/', [Admin\HostingController::class, 'importIndex'])->name('index');
                Route::get('/{domain}', [Admin\HostingController::class, 'importShow'])->name('show');
                Route::post('/{domain}', [Admin\HostingController::class, 'importStore'])->name('store');
            });
        });

        // Domains
        Route::prefix('domains')->name('domains.')->group(function () {
            Route::get('/', [Admin\DomainController::class, 'index'])->name('index');
            Route::delete('/{domain}', [Admin\DomainController::class, 'destroy'])->name('destroy');
        });

        // Plans
        Route::get('/plans/cyberpanel-packages', [Admin\PlanController::class, 'cyberpanelPackages'])->name('plans.cyberpanel-packages');
        Route::resource('plans', Admin\PlanController::class);

        // OS Templates
        Route::prefix('os-templates')->name('os-templates.')->group(function () {
            Route::get('/', [Admin\OsTemplateController::class, 'index'])->name('index');
            Route::post('/', [Admin\OsTemplateController::class, 'store'])->name('store');
            Route::get('/storages', [Admin\OsTemplateController::class, 'storages'])->name('storages');
            Route::get('/{osTemplate}/status', [Admin\OsTemplateController::class, 'taskStatus'])->name('status');
            Route::post('/{osTemplate}/toggle', [Admin\OsTemplateController::class, 'toggle'])->name('toggle');
            Route::delete('/{osTemplate}', [Admin\OsTemplateController::class, 'destroy'])->name('destroy');
        });

        // Invoices
        Route::resource('invoices', Admin\InvoiceController::class)->only(['index', 'show', 'create', 'store', 'destroy']);
        Route::post('/invoices/{invoice}/mark-paid', [Admin\InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
        Route::get('/invoices/{invoice}/download', [Admin\InvoiceController::class, 'download'])->name('invoices.download');
        Route::get('/invoices/{invoice}/facturx.xml', [Admin\InvoiceController::class, 'downloadXml'])->name('invoices.facturx');

        // Projects
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('/', [Admin\ProjectController::class, 'index'])->name('index');
            Route::get('/create', [Admin\ProjectController::class, 'create'])->name('create');
            Route::post('/', [Admin\ProjectController::class, 'store'])->name('store');
            Route::get('/{project}', [Admin\ProjectController::class, 'show'])->name('show');
            Route::put('/{project}', [Admin\ProjectController::class, 'update'])->name('update');
            Route::delete('/{project}', [Admin\ProjectController::class, 'destroy'])->name('destroy');
            Route::post('/{project}/message', [Admin\ProjectController::class, 'addMessage'])->name('message');
        });

        // Tickets
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/', [Admin\TicketController::class, 'index'])->name('index');
            Route::get('/{ticket}', [Admin\TicketController::class, 'show'])->name('show');
            Route::post('/{ticket}/reply', [Admin\TicketController::class, 'reply'])->name('reply');
            Route::post('/{ticket}/status', [Admin\TicketController::class, 'updateStatus'])->name('status');
        });

        // Sandbox
        Route::prefix('sandbox')->name('sandbox.')->group(function () {
            Route::get('/', [Admin\SandboxController::class, 'index'])->name('index');
            Route::post('/seed', [Admin\SandboxController::class, 'seed'])->name('seed');
            Route::delete('/reset', [Admin\SandboxController::class, 'reset'])->name('reset');
            Route::post('/command', [Admin\SandboxController::class, 'runCommand'])->name('command');
        });
    });

    // Notifications (any authenticated user)
    Route::middleware('auth')->prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
        Route::get('/{notification}/read', [NotificationController::class, 'markRead'])->name('read');
    });

    // Root redirect
    Route::get('/', function () {
        if (auth()->check()) {
            return redirect(auth()->user()->is_admin ? route('admin.dashboard') : route('client.dashboard'));
        }
        return redirect()->route('login');
    });
});

// Newsletter unsubscribe (public, no auth required)
Route::get('/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// Stripe webhook (public, no CSRF)
Route::post('/webhooks/stripe', [WebhookController::class, 'stripe'])
    ->name('webhooks.stripe')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
