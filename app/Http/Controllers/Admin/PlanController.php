<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Setting;
use App\Services\CyberPanelService;
use Illuminate\Http\Request;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::orderBy('sort_order')->orderBy('price')->get();
        return view('admin.plans.index', compact('plans'));
    }

    public function create()
    {
        $allPlans = Plan::orderBy('type')->orderBy('name')->get();
        return view('admin.plans.create', compact('allPlans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'               => 'required|string|max:100',
            'type'               => 'required|in:vm,hosting',
            'vm_type'            => 'nullable|in:qemu,lxc',
            'price'              => 'required|numeric|min:0',
            'billing_period'     => 'required|in:monthly,yearly',
            'description'        => 'nullable|string',
            'features'           => 'nullable|string',
            'cores'              => 'nullable|integer|min:1',
            'memory_mb'          => 'nullable|integer|min:128',
            'disk_gb'            => 'nullable|integer|min:1',
            'cyberpanel_package' => 'nullable|string|max:100',
            'limit_per_client'    => 'nullable|integer|min:1',
            'limit_per_vm'        => 'nullable|integer|min:1',
            'limit_per_hosting'   => 'nullable|integer|min:1',
            'limit_per_domain'    => 'nullable|integer|min:1',
            'limit_per_subdomain' => 'nullable|integer|min:1',
        ]);

        $features         = array_filter(array_map('trim', explode("\n", $request->features ?? '')));
        $planAllowances   = $this->parsePlanAllowances($request->input('plan_allowances_json', ''));

        $stripePrice = null;
        if ($request->price > 0 && Setting::get('stripe_secret_key')) {
            try {
                Stripe::setApiKey(Setting::get('stripe_secret_key'));
                $product = Product::create(['name' => $request->name]);
                $price = Price::create([
                    'product'     => $product->id,
                    'unit_amount' => (int) ($request->price * 100),
                    'currency'    => strtolower(Setting::get('default_currency', 'eur')),
                    'recurring'   => ['interval' => $request->billing_period === 'yearly' ? 'year' : 'month'],
                ]);
                $stripePrice = $price->id;
            } catch (\Exception) {}
        }

        Plan::create([
            'name'               => $request->name,
            'type'               => $request->type,
            'vm_type'            => $request->type === 'vm' ? ($request->vm_type ?? 'qemu') : null,
            'price'              => $request->price,
            'billing_period'     => $request->billing_period,
            'description'        => $request->description,
            'features'           => $features,
            'cores'              => $request->type === 'vm' ? $request->cores : null,
            'memory_mb'          => $request->type === 'vm' ? $request->memory_mb : null,
            'disk_gb'            => $request->type === 'vm' ? $request->disk_gb : null,
            'cyberpanel_package' => $request->type === 'hosting' ? $request->cyberpanel_package : null,
            'stripe_price_id'    => $stripePrice,
            'is_active'          => $request->boolean('is_active', true),
            'limit_per_client'    => $request->limit_per_client ?: null,
            'limit_per_vm'        => $request->limit_per_vm ?: null,
            'limit_per_hosting'   => $request->limit_per_hosting ?: null,
            'limit_per_domain'    => $request->limit_per_domain ?: null,
            'limit_per_subdomain' => $request->limit_per_subdomain ?: null,
            'requires_vm'         => $request->boolean('requires_vm'),
            'requires_hosting'    => $request->boolean('requires_hosting'),
            'plan_allowances'     => $planAllowances ?: null,
        ]);

        return redirect()->route('admin.plans.index')->with('success', 'Plan créé.');
    }

    public function edit(Plan $plan)
    {
        $allPlans = Plan::orderBy('type')->orderBy('name')->get();
        return view('admin.plans.edit', compact('plan', 'allPlans'));
    }

    public function update(Request $request, Plan $plan)
    {
        $request->validate([
            'name'               => 'required|string|max:100',
            'type'               => 'required|in:vm,hosting',
            'vm_type'            => 'nullable|in:qemu,lxc',
            'price'              => 'required|numeric|min:0',
            'description'        => 'nullable|string',
            'features'           => 'nullable|string',
            'cores'              => 'nullable|integer|min:1',
            'memory_mb'          => 'nullable|integer|min:128',
            'disk_gb'            => 'nullable|integer|min:1',
            'cyberpanel_package' => 'nullable|string|max:100',
            'sort_order'         => 'integer|min:0',
            'limit_per_client'    => 'nullable|integer|min:1',
            'limit_per_vm'        => 'nullable|integer|min:1',
            'limit_per_hosting'   => 'nullable|integer|min:1',
            'limit_per_domain'    => 'nullable|integer|min:1',
            'limit_per_subdomain' => 'nullable|integer|min:1',
        ]);

        $features       = array_filter(array_map('trim', explode("\n", $request->features ?? '')));
        $planAllowances = $this->parsePlanAllowances($request->input('plan_allowances_json', ''));

        $plan->update([
            'name'               => $request->name,
            'type'               => $request->type,
            'vm_type'            => $request->type === 'vm' ? ($request->vm_type ?? 'qemu') : null,
            'price'              => $request->price,
            'description'        => $request->description,
            'features'           => $features,
            'cores'              => $request->type === 'vm' ? $request->cores : null,
            'memory_mb'          => $request->type === 'vm' ? $request->memory_mb : null,
            'disk_gb'            => $request->type === 'vm' ? $request->disk_gb : null,
            'cyberpanel_package' => $request->type === 'hosting' ? $request->cyberpanel_package : null,
            'stripe_price_id'    => $request->stripe_price_id ?: $plan->stripe_price_id,
            'is_active'          => $request->boolean('is_active'),
            'sort_order'         => $request->sort_order ?? 0,
            'limit_per_client'    => $request->limit_per_client ?: null,
            'limit_per_vm'        => $request->limit_per_vm ?: null,
            'limit_per_hosting'   => $request->limit_per_hosting ?: null,
            'limit_per_domain'    => $request->limit_per_domain ?: null,
            'limit_per_subdomain' => $request->limit_per_subdomain ?: null,
            'requires_vm'         => $request->boolean('requires_vm'),
            'requires_hosting'    => $request->boolean('requires_hosting'),
            'plan_allowances'     => $planAllowances ?: null,
        ]);

        return back()->with('success', 'Plan mis à jour.');
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();
        return redirect()->route('admin.plans.index')->with('success', 'Plan supprimé.');
    }

    public function cyberpanelPackages()
    {
        try {
            $packages = app(CyberPanelService::class)->listPackages();
            return response()->json(['success' => true, 'packages' => $packages]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function parsePlanAllowances(string $json): array
    {
        if (empty($json)) return [];

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) return [];

        return array_values(array_filter(
            array_map(fn($r) => [
                'plan_id'       => (int) ($r['plan_id'] ?? 0),
                'limit_per_owned' => (int) ($r['limit_per_owned'] ?? 0),
            ], $decoded),
            fn($r) => $r['plan_id'] > 0 && $r['limit_per_owned'] > 0
        ));
    }
}
