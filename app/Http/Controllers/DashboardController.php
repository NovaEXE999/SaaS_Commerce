<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Render the multi-tenant bento-grid dashboard with dynamic metrics and financial reports.
     */
    public function index(Request $request): Response
    {
        $business = $request->user()->business;
        $businessId = $business->id;

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // 1. Métricas de Ventas de Hoy vs Ayer
        $todaySales = (float) Sale::where('business_id', $businessId)
            ->whereDate('created_at', $today)
            ->sum('total_amount');

        $yesterdaySales = (float) Sale::where('business_id', $businessId)
            ->whereDate('created_at', $yesterday)
            ->sum('total_amount');

        $salesChange = 0;
        if ($yesterdaySales > 0) {
            $salesChange = round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 1);
        } elseif ($todaySales > 0) {
            $salesChange = 100.0;
        }

        // 2. Gráfico Financiero de los últimos 7 días (ApexCharts)
        $chartCategories = [];
        $chartData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayLabel = $date->translatedFormat('D d');
            $dayTotal = (float) Sale::where('business_id', $businessId)
                ->whereDate('created_at', $date)
                ->sum('total_amount');

            $chartCategories[] = ucfirst($dayLabel);
            $chartData[] = $dayTotal;
        }

        // 3. Clientes y Puntos
        $newCustomersCount = Customer::where('business_id', $businessId)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();

        $totalPoints = (int) Customer::where('business_id', $businessId)
            ->sum('points');

        // 4. Datos específicos por tipo de negocio
        $upcomingAppointments = [];
        $lowStockProducts = [];
        $lowStockCount = 0;

        if ($business->type === 'barberia') {
            $upcomingAppointments = Appointment::with(['customer', 'user'])
                ->where('business_id', $businessId)
                ->whereIn('status', ['agendada', 'adelantada'])
                ->orderBy('start_time', 'asc')
                ->limit(4)
                ->get();
        } else {
            // Tienda / Papelería: Alertas de stock mínimo configurables
            // Productos cuyo stock está en o por debajo de su umbral min_stock configurado,
            // o bien por debajo de 5 si no tienen umbral configurado (min_stock = 0).
            $lowStockProducts = Product::where('business_id', $businessId)
                ->where(function ($query) {
                    $query->where(function ($q) {
                        $q->where('min_stock', '>', 0)
                            ->whereColumn('stock', '<=', 'min_stock');
                    })->orWhere(function ($q) {
                        $q->where('min_stock', 0)
                            ->where('stock', '<=', 5);
                    });
                })
                ->orderBy('stock', 'asc')
                ->limit(5)
                ->get();

            $lowStockCount = Product::where('business_id', $businessId)
                ->where(function ($query) {
                    $query->where(function ($q) {
                        $q->where('min_stock', '>', 0)
                            ->whereColumn('stock', '<=', 'min_stock');
                    })->orWhere(function ($q) {
                        $q->where('min_stock', 0)
                            ->where('stock', '<=', 5);
                    });
                })
                ->count();
        }

        // 5. Conteo para bandeja de auditoría
        $auditPendingCount = Appointment::where('business_id', $businessId)
            ->whereIn('status', ['cancelada', 'reagendada'])
            ->whereNotNull('justification')
            ->count() + Sale::where('business_id', $businessId)
            ->where('payment_method', 'transferencia')
            ->whereNotNull('payment_proof_path')
            ->count();

        return Inertia::render('Dashboard', [
            'metrics' => [
                'todaySales' => $todaySales,
                'yesterdaySales' => $yesterdaySales,
                'salesChange' => $salesChange,
                'newCustomersCount' => $newCustomersCount,
                'totalPoints' => $totalPoints,
                'lowStockCount' => $lowStockCount,
                'auditPendingCount' => $auditPendingCount,
                'chart' => [
                    'categories' => $chartCategories,
                    'series' => [
                        [
                            'name' => 'Ventas (COP)',
                            'data' => $chartData,
                        ],
                    ],
                ],
            ],
            'upcomingAppointments' => $upcomingAppointments,
            'lowStockProducts' => $lowStockProducts,
        ]);
    }
}
