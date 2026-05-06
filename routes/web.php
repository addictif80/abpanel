<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Client;
use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Installation wizard
Route::middleware('not.installed')->prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');
    Route::get('/database', [InstallController::class, 'database'])->name('database');
    Route::post('/database', [InstallController::class, 'saveDatabase'])->name('database.save');
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
        });

        // Tickets
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/', [Client\TicketController::class, 'index'])->name('index');
            Route::get('/create', [Client\TicketController::class, 'create'])->name('create');
            Route::post('/', [Client\TicketController::class, 'store'])->name('store');
            Route::get('/{ticket}', [Client\TicketController::class, 'show'])->name('show');
            Route::post('/{ticket}/reply', [Client\TicketController::class, 'reply'])->name('reply');
        });

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
            Route::post('/proxmox', [Admin\SettingsController::class, 'saveProxmox'])->name('proxmox');
            Route::post('/cyberpanel', [Admin\SettingsController::class, 'saveCyberpanel'])->name('cyberpanel');
            Route::post('/npm', [Admin\SettingsController::class, 'saveNpm'])->name('npm');
            Route::post('/stripe', [Admin\SettingsController::class, 'saveStripe'])->name('stripe');
            Route::post('/mail', [Admin\SettingsController::class, 'saveMail'])->name('mail');
            Route::post('/test/{service}', [Admin\SettingsController::class, 'testConnection'])->name('test');
        });

        // Clients
        Route::resource('clients', Admin\ClientController::class);
        Route::post('/clients/{client}/reset-password', [Admin\ClientController::class, 'resetPassword'])->name('clients.reset-password');

        // VMs
        Route::resource('vms', Admin\VmController::class);
        Route::get('/vms/disk-storages', [Admin\VmController::class, 'diskStorages'])->name('vms.disk-storages');
        Route::prefix('vms/import')->name('vms.import.')->group(function () {
            Route::get('/', [Admin\VmController::class, 'importIndex'])->name('index');
            Route::get('/{node}/{vmid}', [Admin\VmController::class, 'importShow'])->name('show');
            Route::post('/{node}/{vmid}', [Admin\VmController::class, 'importStore'])->name('store');
        });

        // Mail templates
        Route::prefix('mail-templates')->name('mail-templates.')->group(function () {
            Route::get('/', [Admin\MailTemplateController::class, 'index'])->name('index');
            Route::get('/{template}/edit', [Admin\MailTemplateController::class, 'edit'])->name('edit');
            Route::put('/{template}', [Admin\MailTemplateController::class, 'update'])->name('update');
            Route::post('/{template}/test', [Admin\MailTemplateController::class, 'sendTest'])->name('test');
        });

        // Newsletter
        Route::prefix('newsletter')->name('newsletter.')->group(function () {
            Route::get('/', [Admin\NewsletterController::class, 'index'])->name('index');
            Route::resource('lists', Admin\NewsletterListController::class);
            Route::resource('campaigns', Admin\NewsletterCampaignController::class);
            Route::post('/campaigns/{campaign}/send', [Admin\NewsletterController::class, 'send'])->name('campaigns.send');
        });

        // Plans
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

        // Tickets
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/', [Admin\TicketController::class, 'index'])->name('index');
            Route::get('/{ticket}', [Admin\TicketController::class, 'show'])->name('show');
            Route::post('/{ticket}/reply', [Admin\TicketController::class, 'reply'])->name('reply');
            Route::post('/{ticket}/status', [Admin\TicketController::class, 'updateStatus'])->name('status');
        });
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
