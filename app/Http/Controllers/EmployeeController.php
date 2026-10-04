<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees for the current business.
     */
    public function index(Request $request): Response
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Solo los administradores pueden gestionar empleados.');
        }

        $businessId = $request->user()->business_id;

        // Listar empleados activos e inhabilitados del negocio (excluyendo al admin actual para seguridad)
        $employees = User::withTrashed()
            ->where('business_id', $businessId)
            ->where('id', '!=', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'role' => $employee->role,
                    'is_active' => is_null($employee->deleted_at),
                    'deleted_at' => $employee->deleted_at?->toIso8601String(),
                    'created_at' => $employee->created_at->toIso8601String(),
                ];
            });

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
        ]);
    }

    /**
     * Store a newly created employee in the business.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Acción no autorizada.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', 'in:empleado,admin'],
        ]);

        $request->user()->business->users()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return redirect()->back()->with('success', 'Empleado registrado exitosamente.');
    }

    /**
     * Inhabilitar la cuenta del empleado (Soft Delete).
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Acción no autorizada.');
        }

        $employee = User::where('business_id', $request->user()->business_id)
            ->where('id', '!=', $request->user()->id)
            ->findOrFail($id);

        $employee->delete();

        return redirect()->back()->with('success', 'Cuenta de empleado inhabilitada.');
    }

    /**
     * Reactivar la cuenta del empleado.
     */
    public function restore(Request $request, int $id): RedirectResponse
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Acción no autorizada.');
        }

        $employee = User::onlyTrashed()
            ->where('business_id', $request->user()->business_id)
            ->findOrFail($id);

        $employee->restore();

        return redirect()->back()->with('success', 'Cuenta de empleado reactivada exitosamente.');
    }
}
