<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PromoCode;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    public function index()
    {
        $codes = PromoCode::orderByDesc('created_at')->paginate(50);
        return view('admin.promo-codes.index', compact('codes'));
    }

    public function create()
    {
        $plans = Plan::where('is_active', true)->orderBy('name')->get();
        return view('admin.promo-codes.create', compact('plans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'           => 'required|string|max:50|unique:promo_codes,code',
            'description'    => 'nullable|string|max:200',
            'discount_type'  => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:0.01|max:100',
            'min_amount'     => 'nullable|numeric|min:0',
            'max_uses'       => 'nullable|integer|min:1',
            'expires_at'     => 'nullable|date|after:today',
            'plan_ids'       => 'nullable|array',
            'plan_ids.*'     => 'integer|exists:plans,id',
        ]);

        PromoCode::create([
            'code'           => strtoupper($request->code),
            'description'    => $request->description,
            'discount_type'  => $request->discount_type,
            'discount_value' => $request->discount_value,
            'min_amount'     => $request->min_amount ?: null,
            'max_uses'       => $request->max_uses ?: null,
            'expires_at'     => $request->expires_at ?: null,
            'is_active'      => true,
            'plan_ids'       => $request->plan_ids ?: null,
        ]);

        return redirect()->route('admin.promo-codes.index')->with('success', 'Code promo créé.');
    }

    public function toggle(PromoCode $promoCode)
    {
        $promoCode->update(['is_active' => !$promoCode->is_active]);
        return back()->with('success', $promoCode->is_active ? 'Code activé.' : 'Code désactivé.');
    }

    public function destroy(PromoCode $promoCode)
    {
        $promoCode->delete();
        return back()->with('success', 'Code promo supprimé.');
    }
}
