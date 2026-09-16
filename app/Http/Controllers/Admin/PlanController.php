<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Setting;
use App\Services\CyberPanelService;
use App\Services\LdapService;
use App\Services\StripeService;
use Illuminate\Http\Request;

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
            'type'               => 'required|in:vm,hosting,service',
            'vm_type'            => 'nullable|in:qemu,lxc',
            'price'              => 'required|numeric|min:0',
            'yearly_price'       => 'nullable|numeric|min:0',
            'billing_period'     => 'required|in:monthly,yearly',
            'description'        => 'nullable|string',
            'features'           => 'nullable|string',
            'cores'              => 'nullable|integer|min:1',
            'memory_mb'          => 'nullable|integer|min:128',
            'disk_gb'            => 'nullable|integer|min:1',
            'cyberpanel_package' => 'nullable|string|max:100',
            'ldap_group'         => 'nullable|string|max:100',
            'storage_quota_gb'   => 'nullable|integer|min:1',
            'requires_ldap_group' => 'nullable|string|max:100',
            'limit_per_client'    => 'nullable|integer|min:1',
            'limit_per_vm'        => 'nullable|integer|min:1',
            'limit_per_hosting'   => 'nullable|integer|min:1',
            'limit_per_domain'    => 'nullable|integer|min:1',
            'limit_per_subdomain' => 'nullable|integer|min:1',
        ]);

        $features         = array_filter(array_map('trim', explode("\n", $request->features ?? '')));
        $planAllowances   = $this->parsePlanAllowances($request->input('plan_allowances_json', ''));

        $plan = Plan::create([
            'name'               => $request->name,
            'type'               => $request->type,
            'vm_type'            => $request->type === 'vm' ? ($request->vm_type ?? 'qemu') : null,
            'price'              => $request->price,
            'yearly_price'       => $request->yearly_price !== null && $request->yearly_price !== '' ? $request->yearly_price : null,
            'billing_period'     => $request->billing_period,
            'description'        => $request->description,
            'features'           => $features,
            'cores'              => $request->type === 'vm' ? $request->cores : null,
            'memory_mb'          => $request->type === 'vm' ? $request->memory_mb : null,
            'disk_gb'            => $request->type === 'vm' ? $request->disk_gb : null,
            'cyberpanel_package' => $request->type === 'hosting' ? $request->cyberpanel_package : null,
            'ldap_group'         => $request->ldap_group ?: null,
            'storage_quota_gb'   => $request->storage_quota_gb ?: null,
            'requires_ldap_group' => $request->requires_ldap_group ?: null,
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

        $redirect = redirect()->route('admin.plans.index')->with('success', 'Plan créé.');

        if ($stripeWarning = $this->syncStripe($plan)) {
            $redirect->with('stripe_warning', $stripeWarning);
        }

        return $redirect;
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
            'type'               => 'required|in:vm,hosting,service',
            'vm_type'            => 'nullable|in:qemu,lxc',
            'price'              => 'required|numeric|min:0',
            'yearly_price'       => 'nullable|numeric|min:0',
            'description'        => 'nullable|string',
            'features'           => 'nullable|string',
            'cores'              => 'nullable|integer|min:1',
            'memory_mb'          => 'nullable|integer|min:128',
            'disk_gb'            => 'nullable|integer|min:1',
            'cyberpanel_package' => 'nullable|string|max:100',
            'ldap_group'         => 'nullable|string|max:100',
            'storage_quota_gb'   => 'nullable|integer|min:1',
            'requires_ldap_group' => 'nullable|string|max:100',
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
            'yearly_price'       => $request->yearly_price !== null && $request->yearly_price !== '' ? $request->yearly_price : null,
            'description'        => $request->description,
            'features'           => $features,
            'cores'              => $request->type === 'vm' ? $request->cores : null,
            'memory_mb'          => $request->type === 'vm' ? $request->memory_mb : null,
            'disk_gb'            => $request->type === 'vm' ? $request->disk_gb : null,
            'cyberpanel_package' => $request->type === 'hosting' ? $request->cyberpanel_package : null,
            'ldap_group'         => $request->ldap_group ?: null,
            'storage_quota_gb'   => $request->storage_quota_gb ?: null,
            'requires_ldap_group' => $request->requires_ldap_group ?: null,
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

        $redirect = back()->with('success', 'Plan mis à jour.');

        if ($stripeWarning = $this->syncStripe($plan)) {
            $redirect->with('stripe_warning', $stripeWarning);
        }

        return $redirect;
    }

    /** Returns a user-facing warning string on failure, null on success/skip. */
    private function syncStripe(Plan $plan): ?string
    {
        try {
            app(StripeService::class)->syncPlan($plan->fresh());
            return null;
        } catch (\Exception $e) {
            return 'Le plan a été enregistré mais la synchronisation Stripe a échoué : ' . $e->getMessage();
        }
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

    public function ldapGroups()
    {
        try {
            $groups = app(LdapService::class)->listGroups();
            return response()->json(['success' => true, 'groups' => $groups]);
        } catch (\Throwable $e) {
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
