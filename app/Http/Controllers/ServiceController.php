<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceController extends Controller
{
    /**
     * Display a listing of the services for the current business.
     * Available to both administrators and employees.
     */
    public function index(Request $request)
    {
        // Multi-tenant: Only get services from this user's business
        $businessId = $request->user()->business_id;

        $services = Service::where('business_id', $businessId)
            ->withCount('appointments')
            ->latest()
            ->paginate(10);

        return Inertia::render('Services/Index', [
            'services' => $services,
        ]);
    }

    /**
     * Store a newly created service in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
        ]);

        // Automatically assign to the logged-in user's business
        $request->user()->business->services()->create($validated);

        return redirect()->back()->with('success', 'Servicio creado exitosamente.');
    }

    /**
     * Update the specified service in storage.
     *
     * Past appointments keep their price snapshot, so price changes
     * only affect future bookings.
     */
    public function update(Request $request, Service $service)
    {
        // Multi-tenant Security: ensure the service belongs to the user's business
        if ($service->business_id !== $request->user()->business_id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
        ]);

        $service->update($validated);

        return redirect()->back()->with('success', 'Servicio actualizado correctamente.');
    }

    /**
     * Remove the specified service from storage.
     *
     * Past appointments keep their data (service_id is set to null).
     */
    public function destroy(Request $request, Service $service)
    {
        // Multi-tenant Security: ensure the service belongs to the user's business
        if ($service->business_id !== $request->user()->business_id) {
            abort(403, 'Unauthorized action.');
        }

        $service->delete();

        return redirect()->back()->with('success', 'Servicio eliminado exitosamente.');
    }
}
