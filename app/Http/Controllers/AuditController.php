<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    /**
     * Display the Global Audit tray for administrators.
     */
    public function index(Request $request): Response
    {
        // Solo admins tienen acceso a la bandeja de auditoría
        if ($request->user()->role !== 'admin') {
            abort(403, 'Acceso restringido para administradores.');
        }

        $businessId = $request->user()->business_id;

        // 1. Citas canceladas o reagendadas con justificación
        $appointmentAudits = Appointment::with(['customer', 'user'])
            ->where('business_id', $businessId)
            ->whereIn('status', ['cancelada', 'reagendada'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // 2. Ventas con comprobante de pago por transferencia
        $transferSales = Sale::with('user')
            ->where('business_id', $businessId)
            ->where('payment_method', 'transferencia')
            ->orderBy('created_at', 'desc')
            ->get();

        // 3. Productos inhabilitados (Soft Deletes)
        $deletedProducts = Product::onlyTrashed()
            ->where('business_id', $businessId)
            ->orderBy('deleted_at', 'desc')
            ->get();

        // 4. Empleados inhabilitados (Soft Deletes)
        $disabledEmployees = User::onlyTrashed()
            ->where('business_id', $businessId)
            ->orderBy('deleted_at', 'desc')
            ->get();

        return Inertia::render('Audit/Index', [
            'appointmentAudits' => $appointmentAudits,
            'transferSales' => $transferSales,
            'deletedProducts' => $deletedProducts,
            'disabledEmployees' => $disabledEmployees,
        ]);
    }
}
