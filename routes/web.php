<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Middleware\RememberLastBusiness;
use App\Http\Controllers\PointRedemptionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ServiceController;

// Página pública de presentación y agendamiento online
Route::get('/', [PublicBookingController::class, 'index'])->name('home');
Route::post('public-booking', [PublicBookingController::class, 'store'])->name('public-booking.store');

Route::middleware(['auth', 'verified', RememberLastBusiness::class])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Módulo Tienda/Inventario
    Route::resource('products', ProductController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('sales', SaleController::class)->only(['index', 'create', 'store']);

    // Módulo Clientes (Administradores y Empleados)
    Route::resource('customers', CustomerController::class)->only(['index', 'store', 'update', 'destroy']);

    // Módulo Fidelización: canje manual de puntos por descuento futuro
    Route::post('point-redemptions', [PointRedemptionController::class, 'store'])->name('point-redemptions.store');

    // Módulo Barbería (Citas y Turnero)
    Route::resource('appointments', AppointmentController::class)->only(['index', 'store']);
    Route::put('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
    Route::get('turnero', [AppointmentController::class, 'turnScreen'])->name('turnero');

    // Catálogo de Servicios con precio (Administradores y Empleados)
    Route::resource('services', ServiceController::class)->only(['index', 'store', 'update', 'destroy']);

    // Módulo Gestión de Personal / Empleados (Solo Administrador)
    Route::resource('employees', EmployeeController::class)->only(['index', 'store', 'destroy']);
    Route::put('employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');

    // Módulo Auditoría Global (Solo Administrador)
    Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
});

require __DIR__.'/settings.php';
