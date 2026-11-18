<?php
// À copier dans abpanel : app/Http/Controllers/PublicPlanController.php
// Expose les offres ACTIVES en lecture seule (aucune donnée sensible : pas d'ID Stripe ni de serveur).
// NON TESTÉ dans abpanel (code écrit d'après app/Models/Plan.php) — à essayer en local avant déploiement.

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\JsonResponse;

class PublicPlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Plan::active()->get()->map(function (Plan $p) {
            try {
                $order = route('client.checkout.checkout', $p); // ajuster au nom de route réel si besoin
            } catch (\Throwable $e) {
                $order = url('/');
            }

            return [
                'name'            => $p->name,
                'slug'            => $p->slug,
                'type'            => $p->type,            // vm | hosting | autres
                'description'     => $p->description,
                'price'           => (float) $p->price,
                'yearly_price'    => $p->yearly_price !== null ? (float) $p->yearly_price : null,
                'currency'        => $p->currency,
                'billing_period'  => $p->billing_period,
                'features'        => $p->features ?? [],
                'cores'           => $p->cores,
                'memory_mb'       => $p->memory_mb,
                'disk_gb'         => $p->disk_gb,
                'storage_quota_gb' => $p->storage_quota_gb,
                'sort_order'      => $p->sort_order,
                'order_url'       => $order,
            ];
        })->values();

        return response()->json(['plans' => $plans, 'generated_at' => now()->toIso8601String()])
            ->header('Cache-Control', 'public, max-age=60')
            ->header('Access-Control-Allow-Origin', '*');
    }
}
