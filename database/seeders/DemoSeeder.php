<?php

namespace Database\Seeders;

use App\Models\HostingAccount;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\VirtualMachine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Demo clients
        $clients = $this->createClients();

        // VMs
        $this->createVirtualMachines($clients);

        // Hosting
        $this->createHostingAccounts($clients);

        // Quotes & invoices
        $this->createQuotesAndInvoices($clients);

        // Projects
        $this->createProjects($clients);

        // Support tickets
        $this->createTickets($clients);

        // Notifications
        $this->createNotifications($clients);
    }

    private function uniqueQuoteNumber(): string
    {
        do {
            $number = Quote::generateNumber();
        } while (Quote::where('number', $number)->exists());
        return $number;
    }

    private function uniqueInvoiceNumber(): string
    {
        do {
            $number = Invoice::generateNumber();
        } while (Invoice::where('number', $number)->exists());
        return $number;
    }

    private function createClients(): array
    {
        $data = [
            [
                'first_name' => 'Alice',
                'last_name'  => 'Martin',
                'email'      => 'alice.martin@demo.test',
                'company'    => 'Agence WebStudio',
                'address'    => '12 rue du Commerce',
                'zip'        => '75015',
                'city'       => 'Paris',
                'country'    => 'France',
                'siret'      => '52398765400021',
            ],
            [
                'first_name' => 'Bruno',
                'last_name'  => 'Dupont',
                'email'      => 'bruno.dupont@demo.test',
                'company'    => 'Dupont Consulting',
                'address'    => '5 allée des Pins',
                'zip'        => '69002',
                'city'       => 'Lyon',
                'country'    => 'France',
                'siret'      => '',
            ],
            [
                'first_name' => 'Camille',
                'last_name'  => 'Lefebvre',
                'email'      => 'camille@demo.test',
                'company'    => null,
                'address'    => '8 avenue Victor Hugo',
                'zip'        => '33000',
                'city'       => 'Bordeaux',
                'country'    => 'France',
                'siret'      => '',
            ],
        ];

        $clients = [];
        foreach ($data as $d) {
            $clients[] = User::firstOrCreate(
                ['email' => $d['email']],
                [
                    'name'       => $d['first_name'] . ' ' . $d['last_name'],
                    'first_name' => $d['first_name'],
                    'last_name'  => $d['last_name'],
                    'password'   => Hash::make('Demo1234!'),
                    'is_admin'   => false,
                    'is_active'  => true,
                    'company'    => $d['company'],
                    'address'    => $d['address'],
                    'zip'        => $d['zip'],
                    'city'       => $d['city'],
                    'country'    => $d['country'],
                    'siret'      => $d['siret'],
                ]
            );
        }

        return $clients;
    }

    private function createVirtualMachines(array $clients): void
    {
        $vms = [
            [
                'user'            => $clients[0],
                'name'            => 'webstudio-prod',
                'proxmox_vmid'    => 101,
                'proxmox_node'    => 'pve-demo',
                'vm_type'         => 'lxc',
                'status'          => 'running',
                'cores'           => 2,
                'memory_mb'       => 2048,
                'disk_gb'         => 20,
                'ip_address'      => '10.0.1.101',
                'plan'            => 'LXC Pro 2Go',
                'monthly_price'   => 9.99,
                'next_renewal_at' => now()->addDays(12),
            ],
            [
                'user'            => $clients[0],
                'name'            => 'webstudio-dev',
                'proxmox_vmid'    => 102,
                'proxmox_node'    => 'pve-demo',
                'vm_type'         => 'lxc',
                'status'          => 'stopped',
                'cores'           => 1,
                'memory_mb'       => 512,
                'disk_gb'         => 10,
                'ip_address'      => '10.0.1.102',
                'plan'            => 'LXC Starter',
                'monthly_price'   => 4.99,
                'next_renewal_at' => now()->addDays(5),
            ],
            [
                'user'            => $clients[1],
                'name'            => 'dupont-vm-1',
                'proxmox_vmid'    => 201,
                'proxmox_node'    => 'pve-demo',
                'vm_type'         => 'qemu',
                'status'          => 'running',
                'cores'           => 4,
                'memory_mb'       => 8192,
                'disk_gb'         => 50,
                'ip_address'      => '10.0.1.201',
                'plan'            => 'VPS Business',
                'monthly_price'   => 24.99,
                'next_renewal_at' => now()->addDays(20),
            ],
        ];

        foreach ($vms as $v) {
            VirtualMachine::firstOrCreate(
                ['proxmox_vmid' => $v['proxmox_vmid'], 'user_id' => $v['user']->id],
                array_merge($v, ['user_id' => $v['user']->id, 'provisioning_status' => 'done', 'root_password' => 'DemoPass123!'])
            );
        }
    }

    private function createHostingAccounts(array $clients): void
    {
        $accounts = [
            [
                'user'               => $clients[0],
                'domain'             => 'webstudio-demo.fr',
                'cyberpanel_username'=> 'webstudio',
                'plan'               => 'Starter 5Go',
                'disk_mb'            => 5120,
                'is_active'          => true,
                'next_renewal_at'    => now()->addDays(15),
            ],
            [
                'user'               => $clients[2],
                'domain'             => 'camille-portfolio.fr',
                'cyberpanel_username'=> 'camille',
                'plan'               => 'Perso 2Go',
                'disk_mb'            => 2048,
                'is_active'          => true,
                'next_renewal_at'    => now()->addDays(7),
            ],
        ];

        foreach ($accounts as $a) {
            HostingAccount::firstOrCreate(
                ['domain' => $a['domain']],
                array_merge($a, ['user_id' => $a['user']->id])
            );
        }
    }

    private function createQuotesAndInvoices(array $clients): void
    {
        $quoteData = [
            [
                'user'    => $clients[0],
                'subject' => 'Création site vitrine + hébergement',
                'status'  => 'accepted',
                'items'   => [
                    ['description' => 'Création site WordPress', 'quantity' => 1, 'unit_price' => 800.00, 'tax_rate' => 0],
                    ['description' => 'Hébergement annuel', 'quantity' => 1, 'unit_price' => 120.00, 'tax_rate' => 0],
                    ['description' => 'Maintenance mensuelle (3 mois)', 'quantity' => 3, 'unit_price' => 50.00, 'tax_rate' => 0],
                ],
                'invoice' => ['status' => 'paid', 'paid_at' => now()->subDays(10)],
            ],
            [
                'user'    => $clients[1],
                'subject' => 'Migration infrastructure vers VPS',
                'status'  => 'sent',
                'items'   => [
                    ['description' => 'Audit infrastructure existante', 'quantity' => 1, 'unit_price' => 300.00, 'tax_rate' => 0],
                    ['description' => 'Migration données et config', 'quantity' => 1, 'unit_price' => 600.00, 'tax_rate' => 0],
                    ['description' => 'Formation administration serveur', 'quantity' => 2, 'unit_price' => 150.00, 'tax_rate' => 0],
                ],
                'invoice' => null,
            ],
            [
                'user'    => $clients[2],
                'subject' => 'Refonte portfolio photographe',
                'status'  => 'draft',
                'items'   => [
                    ['description' => 'Design maquette', 'quantity' => 1, 'unit_price' => 250.00, 'tax_rate' => 0],
                    ['description' => 'Intégration HTML/CSS', 'quantity' => 1, 'unit_price' => 400.00, 'tax_rate' => 0],
                ],
                'invoice' => null,
            ],
            [
                'user'    => $clients[0],
                'subject' => 'Application de réservation en ligne',
                'status'  => 'viewed',
                'items'   => [
                    ['description' => 'Développement backend API REST', 'quantity' => 20, 'unit_price' => 75.00, 'tax_rate' => 0],
                    ['description' => 'Développement frontend Vue.js', 'quantity' => 15, 'unit_price' => 75.00, 'tax_rate' => 0],
                    ['description' => 'Tests et déploiement', 'quantity' => 5, 'unit_price' => 75.00, 'tax_rate' => 0],
                ],
                'invoice' => null,
            ],
        ];

        foreach ($quoteData as $qd) {
            $total    = collect($qd['items'])->sum(fn($i) => $i['quantity'] * $i['unit_price']);
            $existing = Quote::where('user_id', $qd['user']->id)
                             ->where('subject', $qd['subject'])
                             ->first();
            if ($existing) {
                continue;
            }

            $quote = Quote::create([
                'user_id'   => $qd['user']->id,
                'number'    => $this->uniqueQuoteNumber(),
                'subject'   => $qd['subject'],
                'status'    => $qd['status'],
                'currency'  => 'EUR',
                'subtotal'  => $total,
                'tax_amount'=> 0,
                'total'     => $total,
                'notes'     => 'Devis de démonstration généré par le bac à sable ABPanel.',
                'expires_at'=> now()->addDays(30),
            ]);

            foreach ($qd['items'] as $item) {
                QuoteItem::create([
                    'quote_id'   => $quote->id,
                    'description'=> $item['description'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate'   => $item['tax_rate'],
                ]);
            }

            if ($qd['invoice']) {
                Invoice::create([
                    'user_id'   => $qd['user']->id,
                    'quote_id'  => $quote->id,
                    'number'    => $this->uniqueInvoiceNumber(),
                    'status'    => $qd['invoice']['status'],
                    'items'     => collect($qd['items'])->map(fn($i) => [
                        'description' => $i['description'],
                        'quantity'    => $i['quantity'],
                        'unit_price'  => $i['unit_price'],
                        'total'       => round($i['quantity'] * $i['unit_price'], 2),
                    ])->values()->all(),
                    'subtotal'  => $total,
                    'tax'       => 0,
                    'total'     => $total,
                    'currency'  => 'EUR',
                    'paid_at'   => $qd['invoice']['paid_at'] ?? null,
                    'due_at'    => now()->addDays(30),
                ]);
            }
        }

        // A pending overdue invoice (for testing reminders)
        $overdueExists = Invoice::where('user_id', $clients[1]->id)
                                ->where('status', 'pending')
                                ->where('due_at', '<', now())
                                ->exists();
        if (! $overdueExists) {
            Invoice::create([
                'user_id'  => $clients[1]->id,
                'number'   => Invoice::generateNumber(),
                'status'   => 'pending',
                'items'    => [['description' => 'Hébergement VPS — mois précédent', 'quantity' => 1, 'unit_price' => 24.99, 'total' => 24.99]],
                'subtotal' => 24.99,
                'tax'      => 0,
                'total'    => 24.99,
                'currency' => 'EUR',
                'due_at'   => now()->subDays(5),
            ]);
        }
    }

    private function createProjects(array $clients): void
    {
        $projects = [
            [
                'user'        => $clients[0],
                'title'       => 'Site vitrine WebStudio',
                'description' => 'Création du site vitrine WordPress responsive pour l\'agence WebStudio. 5 pages, blog intégré, formulaire de contact.',
                'status'      => 'in_progress',
                'steps'       => [
                    ['title' => 'Recueil du cahier des charges', 'status' => 'completed'],
                    ['title' => 'Maquette graphique (wireframes)', 'status' => 'completed'],
                    ['title' => 'Validation maquette par le client', 'status' => 'completed'],
                    ['title' => 'Intégration HTML/CSS', 'status' => 'in_progress'],
                    ['title' => 'Intégration WordPress + contenu', 'status' => 'pending'],
                    ['title' => 'Tests et optimisations', 'status' => 'pending'],
                    ['title' => 'Mise en production', 'status' => 'pending'],
                ],
                'due_date'    => now()->addDays(21),
                'messages'    => [
                    ['author' => 'admin', 'body' => 'Bonjour Alice, nous avons bien démarré l\'intégration. La page d\'accueil est presque terminée.'],
                    ['author' => 'client', 'body' => 'Super ! Pouvez-vous m\'envoyer un aperçu quand c\'est prêt ? Je voudrais vérifier les couleurs.'],
                    ['author' => 'admin', 'body' => 'Bien sûr, nous vous enverrons un lien de prévisualisation d\'ici demain.'],
                ],
            ],
            [
                'user'        => $clients[2],
                'title'       => 'Portfolio photographe',
                'description' => 'Refonte du portfolio avec galerie dynamique, filtres par catégorie et formulaire de contact.',
                'status'      => 'pending',
                'steps'       => [
                    ['title' => 'Recueil des besoins et photos', 'status' => 'completed'],
                    ['title' => 'Proposition design', 'status' => 'in_progress'],
                    ['title' => 'Validation design', 'status' => 'pending'],
                    ['title' => 'Développement', 'status' => 'pending'],
                    ['title' => 'Intégration des photos', 'status' => 'pending'],
                    ['title' => 'Mise en ligne', 'status' => 'pending'],
                ],
                'due_date'    => now()->addDays(45),
                'messages'    => [
                    ['author' => 'admin', 'body' => 'Bonjour Camille, nous travaillons sur la proposition design. Avez-vous des exemples de sites que vous aimez ?'],
                ],
            ],
        ];

        foreach ($projects as $pd) {
            $existing = Project::where('user_id', $pd['user']->id)
                               ->where('title', $pd['title'])
                               ->first();
            if ($existing) {
                continue;
            }

            $project = Project::create([
                'user_id'     => $pd['user']->id,
                'title'       => $pd['title'],
                'description' => $pd['description'],
                'status'      => $pd['status'],
                'steps'       => $pd['steps'],
                'due_date'    => $pd['due_date'],
            ]);

            $admin = User::where('is_admin', true)->first();
            foreach ($pd['messages'] as $msg) {
                ProjectMessage::create([
                    'project_id' => $project->id,
                    'user_id'    => $msg['author'] === 'admin' ? $admin?->id : $pd['user']->id,
                    'author'     => $msg['author'],
                    'body'       => $msg['body'],
                    'created_at' => now()->subMinutes(rand(10, 300)),
                    'updated_at' => now()->subMinutes(rand(1, 9)),
                ]);
            }
        }
    }

    private function createTickets(array $clients): void
    {
        $tickets = [
            [
                'user'    => $clients[0],
                'subject' => 'Problème de connexion SSH sur webstudio-prod',
                'status'  => 'open',
                'message' => 'Bonjour, depuis ce matin je n\'arrive plus à me connecter en SSH à mon container. L\'erreur est "Connection refused". Pouvez-vous vérifier ?',
                'replies' => [
                    ['user' => null, 'body' => 'Bonjour Alice, nous avons vérifié votre container. Le service SSH s\'était arrêté suite à une mise à jour. Il est maintenant relancé. Pouvez-vous réessayer ?'],
                    ['user' => $clients[0], 'body' => 'Ça fonctionne à nouveau, merci !'],
                ],
            ],
            [
                'user'    => $clients[1],
                'subject' => 'Question sur la facturation récurrente',
                'status'  => 'closed',
                'message' => 'Bonjour, j\'aimerais comprendre comment fonctionne la facturation automatique mensuelle. Est-ce que je reçois une notification avant le prélèvement ?',
                'replies' => [
                    ['user' => null, 'body' => 'Bonjour Bruno, oui vous recevez une notification par email et dans le panel 7 jours avant chaque renouvellement. La facture est générée automatiquement.'],
                ],
            ],
        ];

        $admin = User::where('is_admin', true)->first();

        foreach ($tickets as $td) {
            $existing = Ticket::where('user_id', $td['user']->id)
                              ->where('subject', $td['subject'])
                              ->first();
            if ($existing) {
                continue;
            }

            $ticket = Ticket::create([
                'user_id' => $td['user']->id,
                'number'  => Ticket::generateNumber(),
                'subject' => $td['subject'],
                'status'  => $td['status'],
            ]);

            // First message = opening message from client
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id'   => $td['user']->id,
                'message'   => $td['message'],
                'is_admin'  => false,
            ]);

            foreach ($td['replies'] as $r) {
                TicketMessage::create([
                    'ticket_id' => $ticket->id,
                    'user_id'   => $r['user'] ? $r['user']->id : $admin?->id,
                    'message'   => $r['body'],
                    'is_admin'  => $r['user'] === null,
                ]);
            }
        }
    }

    private function createNotifications(array $clients): void
    {
        $notifs = [
            [
                'user'  => $clients[0],
                'type'  => 'invoice_created',
                'title' => 'Nouvelle facture',
                'body'  => 'Une nouvelle facture vous a été émise.',
                'url'   => '/client/billing',
            ],
            [
                'user'  => $clients[0],
                'type'  => 'vm_renewal_soon',
                'title' => 'Renouvellement dans 7 jours',
                'body'  => 'Votre VM webstudio-dev arrive à renouvellement dans 7 jours.',
                'url'   => '/client/vms',
            ],
            [
                'user'  => $clients[1],
                'type'  => 'invoice_overdue',
                'title' => 'Facture en retard',
                'body'  => 'Une facture est en attente de paiement depuis plus de 5 jours.',
                'url'   => '/client/billing',
            ],
            [
                'user'  => $clients[2],
                'type'  => 'hosting_renewal_soon',
                'title' => 'Renouvellement hébergement dans 7 jours',
                'body'  => 'Votre hébergement camille-portfolio.fr arrive à renouvellement dans 7 jours.',
                'url'   => '/client/hosting',
            ],
        ];

        foreach ($notifs as $n) {
            Notification::firstOrCreate(
                ['user_id' => $n['user']->id, 'type' => $n['type'], 'title' => $n['title']],
                [
                    'body'    => $n['body'],
                    'url'     => $n['url'],
                    'read_at' => null,
                ]
            );
        }
    }
}
