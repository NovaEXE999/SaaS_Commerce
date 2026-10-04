<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Display a listing of the products for the current business.
     */
    public function index(Request $request)
    {
        // Multi-tenant: Only get products from this user's business
        $businessId = $request->user()->business_id;

        $products = Product::where('business_id', $businessId)
            ->latest()
            ->paginate(10);

        return Inertia::render('Products/Index', [
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
        ]);

        // Automatically assign to the logged-in user's business
        $request->user()->business->products()->create($validated);

        return redirect()->back()->with('success', 'Producto creado exitosamente.');
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product)
    {
        // Multi-tenant Security: ensure the product belongs to the user's business
        if ($product->business_id !== $request->user()->business_id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
        ]);

        $product->update($validated);

        return redirect()->back()->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Remove the specified product from storage (Soft Delete).
     */
    public function destroy(Request $request, Product $product)
    {
        // Multi-tenant Security: ensure the product belongs to the user's business
        if ($product->business_id !== $request->user()->business_id) {
            abort(403, 'Unauthorized action.');
        }

        $product->delete(); // This triggers a Soft Delete because of the trait in the Model

        return redirect()->back()->with('success', 'Producto inhabilitado exitosamente.');
    }
}
