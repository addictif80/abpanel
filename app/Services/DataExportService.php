<?php

namespace App\Services;

use App\Models\User;

/**
 * GDPR right of access / portability (Art. 15 & 20): builds a structured,
 * machine-readable (JSON) export of everything ABPanel holds about a
 * client. Deliberately excludes secrets (passwords, LDAP/CyberPanel
 * credentials, root passwords) — those are access credentials to the
 * account/resources, not personal data the client needs a copy of, and
 * exporting them would just create another place they can leak from.
 */
class DataExportService
{
    public function export(User $user): array
    {
        $user->loadMissing([
            'virtualMachines', 'hostingAccounts', 'clientDomains',
            'invoices', 'creditNotes',
            'quotes.messages', 'tickets.messages', 'projects.messages',
        ]);

        return [
            'exported_at' => now()->toIso8601String(),
            'profile' => [
                'id'                    => $user->id,
                'first_name'            => $user->first_name,
                'last_name'             => $user->last_name,
                'email'                 => $user->email,
                'phone'                 => $user->phone,
                'company'               => $user->company,
                'address'               => $user->address,
                'city'                  => $user->city,
                'zip'                   => $user->zip,
                'country'               => $user->country,
                'siret'                 => $user->siret,
                'vat_number'            => $user->vat_number,
                'newsletter_subscribed' => $user->newsletter_subscribed,
                'created_at'            => $user->created_at?->toIso8601String(),
            ],
            'virtual_machines' => $user->virtualMachines->map(fn($vm) => [
                'name' => $vm->name, 'status' => $vm->status, 'cores' => $vm->cores,
                'memory_mb' => $vm->memory_mb, 'disk_gb' => $vm->disk_gb,
                'monthly_price' => $vm->monthly_price, 'created_at' => $vm->created_at?->toIso8601String(),
            ])->all(),
            'hosting_accounts' => $user->hostingAccounts->map(fn($h) => [
                'domain' => $h->domain, 'is_active' => $h->is_active,
                'monthly_price' => $h->monthly_price, 'created_at' => $h->created_at?->toIso8601String(),
            ])->all(),
            'domains' => $user->clientDomains->map(fn($d) => [
                'domain' => $d->domain, 'type' => $d->type, 'created_at' => $d->created_at?->toIso8601String(),
            ])->all(),
            'invoices' => $user->invoices->map(fn($i) => [
                'number' => $i->number, 'status' => $i->status, 'total' => $i->total,
                'currency' => $i->currency, 'paid_at' => $i->paid_at?->toIso8601String(),
                'created_at' => $i->created_at?->toIso8601String(),
            ])->all(),
            'credit_notes' => $user->creditNotes->map(fn($c) => [
                'number' => $c->number, 'amount' => $c->amount, 'status' => $c->status,
                'created_at' => $c->created_at?->toIso8601String(),
            ])->all(),
            'quotes' => $user->quotes->map(fn($q) => [
                'number' => $q->number, 'subject' => $q->subject, 'status' => $q->status,
                'total' => $q->total, 'created_at' => $q->created_at?->toIso8601String(),
                'messages' => $q->messages->map(fn($m) => [
                    'author' => $m->author, 'body' => $m->body, 'created_at' => $m->created_at?->toIso8601String(),
                ])->all(),
            ])->all(),
            'tickets' => $user->tickets->map(fn($t) => [
                'number' => $t->number, 'subject' => $t->subject, 'status' => $t->status,
                'created_at' => $t->created_at?->toIso8601String(),
                'messages' => $t->messages->map(fn($m) => [
                    'message' => $m->message, 'created_at' => $m->created_at?->toIso8601String(),
                ])->all(),
            ])->all(),
            'projects' => $user->projects->map(fn($p) => [
                'title' => $p->title, 'status' => $p->status,
                'created_at' => $p->created_at?->toIso8601String(),
                'messages' => $p->messages->map(fn($m) => [
                    'body' => $m->body, 'created_at' => $m->created_at?->toIso8601String(),
                ])->all(),
            ])->all(),
        ];
    }
}
