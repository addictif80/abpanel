<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('reference', 'like', "%{$request->search}%");
            });
        }

        $products = $query->orderBy('sort_order')->orderBy('name')->paginate(25);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.products.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:200',
            'reference'       => 'nullable|string|max:50',
            'description'     => 'nullable|string',
            'unit_price'      => 'required|numeric|min:0',
            'unit'            => 'required|string|max:30',
            'tax_rate'        => 'required|numeric|min:0|max:100',
            'sort_order'      => 'nullable|integer',
        ]);

        Product::create([
            'name'           => $request->name,
            'reference'      => $request->reference,
            'description'    => $request->description,
            'unit_price'     => $request->unit_price,
            'unit'           => $request->unit,
            'tax_rate'       => $request->tax_rate,
            'hidden_from_pro'=> $request->boolean('hidden_from_pro'),
            'is_active'      => $request->boolean('is_active', true),
            'sort_order'     => $request->sort_order ?? 0,
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Produit créé.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name'        => 'required|string|max:200',
            'reference'   => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'unit_price'  => 'required|numeric|min:0',
            'unit'        => 'required|string|max:30',
            'tax_rate'    => 'required|numeric|min:0|max:100',
            'sort_order'  => 'nullable|integer',
        ]);

        $product->update([
            'name'           => $request->name,
            'reference'      => $request->reference,
            'description'    => $request->description,
            'unit_price'     => $request->unit_price,
            'unit'           => $request->unit,
            'tax_rate'       => $request->tax_rate,
            'hidden_from_pro'=> $request->boolean('hidden_from_pro'),
            'is_active'      => $request->boolean('is_active'),
            'sort_order'     => $request->sort_order ?? 0,
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Produit mis à jour.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Produit supprimé.');
    }
}
