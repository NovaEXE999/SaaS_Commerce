<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PointRedemption;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SaleController extends Controller
{
    /**
     * Historial de ventas para el negocio.
     */
    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;

        $sales = Sale::with(['user', 'items.product'])
            ->where('business_id', $businessId)
            ->latest()
            ->paginate(15);

        return Inertia::render('Sales/Index', [
            'sales' => $sales,
        ]);
    }

    /**
     * Muestra la interfaz del Punto de Venta (POS)
     */
    public function create(Request $request)
    {
        $businessId = $request->user()->business_id;

        // Solo cargar productos con stock disponible
        $products = Product::where('business_id', $businessId)
            ->where('stock', '>', 0)
            ->get();

        // Cargar clientes para asignarles puntos
        $customers = Customer::where('business_id', $businessId)->get();

        return Inertia::render('Sales/Create', [
            'products' => $products,
            'customers' => $customers,
        ]);
    }

    /**
     * Procesa la venta, reduce stock, aplica canje de puntos y asigna puntos nuevos.
     *
     * Reglas de puntos:
     * - Cada punto canjeado equivale a $1.000 COP de descuento sobre el total.
     * - El descuento no puede dejar el total en negativo (se topa al total).
     * - Tras el canje, el cliente acumula 1 punto por cada $5.000 COP del total final.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'payment_method' => 'required|in:efectivo,transferencia,tarjeta',
            'payment_proof' => 'required_if:payment_method,transferencia|image|max:5120', // max 5MB
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'points_to_redeem' => 'nullable|integer|min:0',
        ]);

        $businessId = $request->user()->business_id;
        $totalAmount = 0;
        $itemsData = [];

        DB::beginTransaction();

        try {
            // 1. Verificar stock y calcular el total
            foreach ($validated['products'] as $item) {
                $product = Product::where('business_id', $businessId)
                    ->where('id', $item['id'])
                    ->lockForUpdate() // Evita inconsistencias de stock si hay compras simultáneas
                    ->firstOrFail();

                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Stock insuficiente para el producto: {$product->name}");
                }

                // Reducir stock
                $product->stock -= $item['quantity'];
                $product->save();

                $subtotal = $product->price * $item['quantity'];
                $totalAmount += $subtotal;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal,
                ];
            }

            // 2. Canje de puntos (opcional, solo si hay cliente seleccionado)
            $pointsRedeemed = (int) ($validated['points_to_redeem'] ?? 0);
            $discountAmount = 0;
            $customer = null;

            if ($pointsRedeemed > 0) {
                if (empty($validated['customer_id'])) {
                    throw ValidationException::withMessages([
                        'points_to_redeem' => 'Seleccione un cliente para canjear puntos.',
                    ]);
                }

                $customer = Customer::where('business_id', $businessId)
                    ->lockForUpdate()
                    ->findOrFail($validated['customer_id']);

                if ($customer->points < $pointsRedeemed) {
                    throw ValidationException::withMessages([
                        'points_to_redeem' => "El cliente solo tiene {$customer->points} puntos disponibles.",
                    ]);
                }

                // 1 punto = $1.000 COP, topado al total de la venta
                $discountAmount = min($pointsRedeemed * 1000, $totalAmount);
                $totalAmount -= $discountAmount;
            } elseif (! empty($validated['customer_id'])) {
                $customer = Customer::where('business_id', $businessId)->find($validated['customer_id']);
            }

            // 3. Subir comprobante de pago si es transferencia
            $proofPath = null;
            if ($request->hasFile('payment_proof')) {
                $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
            }

            // 4. Registrar la venta y sus ítems detallados
            $sale = Sale::create([
                'business_id' => $businessId,
                'user_id' => $request->user()->id,
                'total_amount' => $totalAmount,
                'payment_method' => $validated['payment_method'],
                'payment_proof_path' => $proofPath,
            ]);

            $sale->items()->createMany($itemsData);

            // 5. Registrar el canje vinculado a la venta y descontar puntos
            if ($pointsRedeemed > 0 && $customer) {
                PointRedemption::create([
                    'business_id' => $businessId,
                    'customer_id' => $customer->id,
                    'sale_id' => $sale->id,
                    'points_redeemed' => $pointsRedeemed,
                    'discount_amount' => $discountAmount,
                    'description' => "Canje aplicado en venta #{$sale->id}",
                ]);

                $customer->decrement('points', $pointsRedeemed);
                $customer->refresh();
            }

            // 6. Lógica de Puntos: 1 punto por cada 5.000 COP del total final
            if ($customer) {
                $pointsEarned = (int) floor($totalAmount / 5000);
                if ($pointsEarned > 0) {
                    $customer->increment('points', $pointsEarned);
                }
            }

            DB::commit();

            // Retornamos con mensaje de éxito (podemos redirigir al dashboard o limpiar la caja)
            return redirect()->route('dashboard')->with('success', 'Venta registrada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($e instanceof ValidationException) {
                throw $e;
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
