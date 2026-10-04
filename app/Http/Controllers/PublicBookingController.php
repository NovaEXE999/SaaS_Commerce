<?php

namespace App\Http\Controllers;

use App\Events\TurnUpdated;
use App\Jobs\SendN8nWebhook;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PublicBookingController extends Controller
{
    /**
     * Muestra la página pública según el contexto:
     * - Usuario autenticado: landing de su propio negocio.
     * - Invitado con cookie `last_business_id`: landing del último negocio usado.
     * - Sin contexto (primera visita): landing genérica de la plataforma NovaSaaS.
     */
    public function index(Request $request): Response
    {
        $business = null;

        if ($request->user()) {
            $business = $request->user()->business;
        } elseif ($request->cookie('last_business_id')) {
            $business = Business::find($request->cookie('last_business_id'));
        }

        if (! $business) {
            return $this->platformLanding();
        }

        if ($business->type === 'barberia') {
            return $this->barbershopLanding($business);
        }

        return $this->storefrontLanding($business);
    }

    /**
     * Landing genérica de la plataforma NovaSaaS (primera visita).
     */
    private function platformLanding(): Response
    {
        return Inertia::render('WelcomePlatform', [
            'stats' => [
                'businesses' => Business::count(),
                'appointments' => Appointment::count(),
                'points' => (int) Customer::sum('points'),
            ],
        ]);
    }

    /**
     * Landing de barbería con agendamiento online.
     */
    private function barbershopLanding(Business $business): Response
    {
        $barbers = User::where('business_id', $business->id)->get(['id', 'name']);
        $services = Service::where('business_id', $business->id)
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'price']);

        return Inertia::render('Welcome', [
            'barbershop' => $business,
            'barbers' => $barbers,
            'services' => $services,
        ]);
    }

    /**
     * Vitrina pública para tiendas y papelerías (catálogo destacado).
     */
    private function storefrontLanding(Business $business): Response
    {
        $products = Product::where('business_id', $business->id)
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'description', 'price', 'stock']);

        return Inertia::render('WelcomeBusiness', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'type' => $business->type,
            ],
            'products' => $products,
        ]);
    }

    /**
     * Agendar cita desde la página pública para clientes sin necesidad de registro previo.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_id' => ['required', 'exists:businesses,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'user_id' => ['required', 'exists:users,id'],
            'service_id' => ['nullable', 'exists:services,id'],
            'start_time' => ['required', 'date', 'after:now'],
        ]);

        // 1. Obtener o crear el cliente automáticamente por su número de teléfono
        $customer = Customer::firstOrCreate(
            [
                'business_id' => $validated['business_id'],
                'phone' => $validated['customer_phone'],
            ],
            [
                'name' => $validated['customer_name'],
                'points' => 0,
            ]
        );

        // 1b. Servicio elegido (opcional) con su precio congelado en la cita
        $service = null;
        $price = 0;
        if (! empty($validated['service_id'])) {
            $service = Service::where('business_id', $validated['business_id'])
                ->findOrFail($validated['service_id']);
            $price = $service->price;
        }

        // 2. Crear la cita
        $appointment = Appointment::create([
            'business_id' => $validated['business_id'],
            'customer_id' => $customer->id,
            'user_id' => $validated['user_id'],
            'service_id' => $service?->id,
            'price' => $price,
            'start_time' => $validated['start_time'],
            'status' => 'agendada',
            'client_approved' => true,
        ]);

        // 3. Emitir evento por WebSockets para pantalla física/TV (best-effort).
        try {
            broadcast(new TurnUpdated($validated['business_id'], $appointment->load('customer', 'user')));
        } catch (\Exception $e) {
            Log::warning('[PublicBooking] No se pudo emitir TurnUpdated (¿Reverb detenido?).', [
                'appointment_id' => $appointment->id,
                'business_id' => $appointment->business_id,
                'error' => $e->getMessage(),
            ]);
        }

        // 4. Disparar Webhook hacia n8n para programar recordatorios de 1 día, 1 hora y 10 minutos
        SendN8nWebhook::dispatch('appointment_created', [
            'appointment_id' => $appointment->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'start_time' => $appointment->start_time,
            'business_id' => $appointment->business_id,
        ]);

        return redirect()->back()->with('success', '¡Tu cita ha sido agendada con éxito! Te esperamos.');
    }
}
