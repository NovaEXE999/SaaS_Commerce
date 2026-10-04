<?php

namespace App\Http\Controllers;

use App\Events\TurnUpdated;
use App\Jobs\SendN8nWebhook;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    /**
     * Panel de control de Citas para los barberos/admins.
     */
    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;

        // Cargar citas del día y futuras
        $appointments = Appointment::with(['customer', 'user', 'service'])
            ->where('business_id', $businessId)
            ->orderBy('start_time', 'asc')
            ->get();

        $customers = Customer::where('business_id', $businessId)->get();
        // Cargamos todos los usuarios que pueden ser barberos
        $barbers = User::where('business_id', $businessId)->get();
        // Catálogo de servicios con precio para el agendamiento
        $services = Service::where('business_id', $businessId)->orderBy('name')->get();

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'customers' => $customers,
            'barbers' => $barbers,
            'services' => $services,
        ]);
    }

    /**
     * Agendar una nueva cita.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'user_id' => 'required|exists:users,id', // El barbero asignado
            'service_id' => 'required|exists:services,id',
            'start_time' => 'required|date',
        ]);

        $businessId = $request->user()->business_id;

        // El servicio debe pertenecer al negocio (el precio se congela en la cita)
        $service = Service::where('business_id', $businessId)
            ->findOrFail($validated['service_id']);

        $appointment = Appointment::create([
            'business_id' => $businessId,
            'customer_id' => $validated['customer_id'],
            'user_id' => $validated['user_id'],
            'service_id' => $service->id,
            'price' => $service->price,
            'start_time' => $validated['start_time'],
            'status' => 'agendada',
        ]);

        // Emitir evento por WebSockets para actualizar pantallas si la cita es para hoy.
        // Best-effort: si el servidor Reverb no está disponible, la cita igual queda creada.
        try {
            broadcast(new TurnUpdated($request->user()->business_id, $appointment->load('customer', 'user')));
        } catch (\Exception $e) {
            Log::warning('[Appointments] No se pudo emitir TurnUpdated (¿Reverb detenido?).', [
                'appointment_id' => $appointment->id,
                'business_id' => $appointment->business_id,
                'error' => $e->getMessage(),
            ]);
        }

        // Disparar Webhook hacia n8n para orquestar recordatorios/notificaciones por WhatsApp o email
        SendN8nWebhook::dispatch('appointment_created', [
            'appointment_id' => $appointment->id,
            'customer_name' => $appointment->customer->name,
            'customer_phone' => $appointment->customer->phone ?? 'N/A',
            'start_time' => $appointment->start_time,
            'business_id' => $appointment->business_id,
        ]);

        return redirect()->back()->with('success', 'Cita agendada.');
    }

    /**
     * Actualizar estado de la cita (Cancelar, Reagendar, Completar, Adelantar)
     */
    public function updateStatus(Request $request, Appointment $appointment)
    {
        if ($appointment->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:agendada,adelantada,reagendada,cancelada,completada',
            'justification' => 'required_if:status,cancelada,reagendada|nullable|string|min:5',
            'client_approved' => 'required_if:status,adelantada|boolean',
            'payment_method' => 'required_if:status,completada|nullable|in:efectivo,transferencia,tarjeta',
        ]);

        // Validación estricta de Auditoría
        if (in_array($validated['status'], ['cancelada', 'reagendada']) && empty($validated['justification'])) {
            return back()->withErrors(['justification' => 'La justificación es obligatoria para cancelar o reagendar (Auditoría).']);
        }

        // Validación de Adelanto
        if ($validated['status'] === 'adelantada' && empty($validated['client_approved'])) {
            return back()->withErrors(['client_approved' => 'El cliente debe aprobar explícitamente el adelanto.']);
        }

        $previousStatus = $appointment->getOriginal('status');

        $appointment->update([
            'status' => $validated['status'],
            'justification' => $validated['justification'] ?? $appointment->justification,
            'client_approved' => $validated['client_approved'] ?? $appointment->client_approved,
        ]);

        // Al completar la cita se genera la venta (métricas del Dashboard) y el cliente acumula puntos
        if ($validated['status'] === 'completada' && $previousStatus !== 'completada') {
            $this->recordAppointmentSale($appointment, $validated['payment_method']);
        }

        // Disparar WebSocket al Televisor y app web de clientes (best-effort).
        try {
            broadcast(new TurnUpdated($appointment->business_id, $appointment->load('customer', 'user')));
        } catch (\Exception $e) {
            Log::warning('[Appointments] No se pudo emitir TurnUpdated (¿Reverb detenido?).', [
                'appointment_id' => $appointment->id,
                'business_id' => $appointment->business_id,
                'error' => $e->getMessage(),
            ]);
        }

        // Avisar a n8n para cambios importantes (cancelaciones o reagendamientos)
        if (in_array($validated['status'], ['cancelada', 'reagendada'])) {
            SendN8nWebhook::dispatch('appointment_status_changed', [
                'appointment_id' => $appointment->id,
                'status' => $validated['status'],
                'customer_name' => $appointment->customer->name,
                'customer_phone' => $appointment->customer->phone ?? 'N/A',
                'justification' => $validated['justification'] ?? null,
            ]);
        }

        return redirect()->back()->with('success', 'Estado de la cita actualizado correctamente.');
    }

    /**
     * Registra la venta correspondiente a una cita completada.
     *
     * La venta queda vinculada a la cita (una cita = máximo una venta) y el
     * cliente acumula 1 punto por cada $5.000 COP, igual que en el POS.
     * Las citas sin precio registrado (legadas o sin servicio) no generan venta.
     */
    private function recordAppointmentSale(Appointment $appointment, string $paymentMethod): void
    {
        if ((float) $appointment->price <= 0) {
            return;
        }

        if (Sale::where('appointment_id', $appointment->id)->exists()) {
            return;
        }

        DB::transaction(function () use ($appointment, $paymentMethod) {
            Sale::create([
                'business_id' => $appointment->business_id,
                'user_id' => $appointment->user_id, // El barbero que prestó el servicio
                'appointment_id' => $appointment->id,
                'total_amount' => $appointment->price,
                'payment_method' => $paymentMethod,
            ]);

            $customer = Customer::where('business_id', $appointment->business_id)
                ->lockForUpdate()
                ->find($appointment->customer_id);

            if ($customer) {
                $pointsEarned = (int) floor((float) $appointment->price / 5000);
                if ($pointsEarned > 0) {
                    $customer->increment('points', $pointsEarned);
                }
            }
        });
    }

    /**
     * Vista Pública del Turnero para la Barbería (Televisor)
     */
    public function turnScreen(Request $request)
    {
        $businessId = $request->user()->business_id;

        $currentAppointments = Appointment::with(['customer', 'user'])
            ->where('business_id', $businessId)
            ->whereIn('status', ['agendada', 'adelantada'])
            ->orderBy('start_time', 'asc')
            ->limit(6)
            ->get();

        return Inertia::render('Appointments/TurnScreen', [
            'businessId' => $businessId,
            'initialAppointments' => $currentAppointments,
        ]);
    }
}
