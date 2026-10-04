<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PointRedemption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PointRedemptionController extends Controller
{
    /**
     * Canjear puntos de un cliente por un descuento aplicable a una venta futura.
     *
     * Regla: Cada punto equivale a $1.000 COP de descuento.
     * Mínimo 1 punto para canjear, máximo el total de puntos del cliente.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'points_to_redeem' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $businessId = $request->user()->business_id;

        return DB::transaction(function () use ($businessId, $validated) {
            $customer = Customer::where('business_id', $businessId)
                ->lockForUpdate()
                ->findOrFail($validated['customer_id']);

            if ($customer->points < $validated['points_to_redeem']) {
                throw ValidationException::withMessages([
                    'points_to_redeem' => "El cliente solo tiene {$customer->points} puntos disponibles.",
                ]);
            }

            // Cada punto equivale a $1.000 COP de descuento
            $discountAmount = $validated['points_to_redeem'] * 1000;

            // Registrar el canje
            PointRedemption::create([
                'business_id' => $businessId,
                'customer_id' => $customer->id,
                'points_redeemed' => $validated['points_to_redeem'],
                'discount_amount' => $discountAmount,
                'description' => $validated['description'] ?? 'Canje de puntos por descuento',
            ]);

            // Descontar puntos del cliente
            $customer->decrement('points', $validated['points_to_redeem']);

            $formattedDiscount = number_format($discountAmount, 0, ',', '.');

            return redirect()->back()->with(
                'success',
                "¡Canje exitoso! Se descontaron {$validated['points_to_redeem']} puntos. Descuento de \${$formattedDiscount} COP aplicable a la próxima compra."
            );
        });
    }
}
