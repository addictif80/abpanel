<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Setting;
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
        return view('admin.plans.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:100',
            'type'           => 'required|in:vm,hosting',
            'vm_type'        => 'nullable|in:qemu,lxc',
            'price'          => 'required|numeric|min:0',
            'billing_period' => 'required|in:monthly,yearly',
            'description'    => 'nullable|string',
            'features'       => 'nullable|string',
            'cores'          => 'nullable|integer|min:1',
            'memory_mb'      => 'nullable|integer|min:128',
            'disk_gb'        => 'nullable|integer|min:1',
        ]);

        $features = array_filter(array_map('trim', explode("\n", $request->features ?? '')));

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
            'name'            => $request->name,
            'type'            => $request->type,
            'vm_type'         => $request->type === 'vm' ? ($request->vm_type ?? 'qemu') : null,
            'price'           => $request->price,
            'billing_period'  => $request->billing_period,
            'description'     => $request->description,
            'features'        => $features,
            'cores'           => $request->cores,
            'memory_mb'       => $request->memory_mb,
            'disk_gb'         => $request->disk_gb,
            'stripe_price_id' => $stripePrice,
            'is_active'       => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.plans.index')->with('success', 'Plan créé.');
    }

    public function edit(Plan $plan)
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $request->validate([
            'name'           => 'required|string|max:100',
            'type'           => 'required|in:vm,hosting',
            'vm_type'        => 'nullable|in:qemu,lxc',
            'price'          => 'required|numeric|min:0',
            'description'    => 'nullable|string',
            'features'       => 'nullable|string',
            'cores'          => 'nullable|integer|min:1',
            'memory_mb'      => 'nullable|integer|min:128',
            'disk_gb'        => 'nullable|integer|min:1',
            'sort_order'     => 'integer|min:0',
        ]);

        $features = array_filter(array_map('trim', explode("\n", $request->features ?? '')));

        $plan->update([
            'name'            => $request->name,
            'type'            => $request->type,
            'vm_type'         => $request->type === 'vm' ? ($request->vm_type ?? 'qemu') : null,
            'price'           => $request->price,
            'description'     => $request->description,
            'features'        => $features,
            'cores'           => $request->cores,
            'memory_mb'       => $request->memory_mb,
            'disk_gb'         => $request->disk_gb,
            'stripe_price_id' => $request->stripe_price_id ?: $plan->stripe_price_id,
            'is_active'       => $request->boolean('is_active'),
            'sort_order'      => $request->sort_order ?? 0,
        ]);

        return back()->with('success', 'Plan mis à jour.');
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();
        return redirect()->route('admin.plans.index')->with('success', 'Plan supprimé.');
    }
}
