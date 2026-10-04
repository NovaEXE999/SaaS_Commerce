<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    /**
     * Display a listing of the customers for the current business.
     * Available to both administrators and employees.
     */
    public function index(Request $request)
    {
        // Multi-tenant: Only get customers from this user's business
        $businessId = $request->user()->business_id;

        $customers = Customer::where('business_id', $businessId)
            ->withCount('appointments')
            ->latest()
            ->paginate(10);

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
        ]);
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        // Automatically assign to the logged-in user's business
        $request->user()->business->customers()->create($validated);

        return redirect()->back()->with('success', 'Cliente creado exitosamente.');
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        // Multi-tenant Security: ensure the customer belongs to the user's business
        if ($customer->business_id !== $request->user()->business_id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $customer->update($validated);

        return redirect()->back()->with('success', 'Cliente actualizado correctamente.');
    }

    /**
     * Remove the specified customer from storage.
     *
     * Customers with scheduled appointments cannot be deleted to preserve
     * the audit trail (appointments cascade on customer delete).
     */
    public function destroy(Request $request, Customer $customer)
    {
        // Multi-tenant Security: ensure the customer belongs to the user's business
        if ($customer->business_id !== $request->user()->business_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($customer->appointments()->exists()) {
            return redirect()->back()->withErrors([
                'error' => 'No se puede eliminar el cliente porque tiene citas registradas.',
            ]);
        }

        $customer->delete();

        return redirect()->back()->with('success', 'Cliente eliminado exitosamente.');
    }
}
