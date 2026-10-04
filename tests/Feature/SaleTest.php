<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('sales create page renders with available products and customers', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    $product = Product::create([
        'business_id' => $business->id,
        'name' => 'Bolígrafo Azul',
        'price' => 1200.00,
        'stock' => 15,
    ]);

    $response = $this->actingAs($user)->get(route('sales.create'));

    $response->assertOk();
    $response->assertSee('Bolígrafo Azul');
});

test('sales index lists sales belonging to the current business', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    $sale = Sale::create([
        'business_id' => $business->id,
        'user_id' => $user->id,
        'total_amount' => 15000.00,
        'payment_method' => 'efectivo',
    ]);

    $otherBusiness = Business::factory()->create();
    $otherSale = Sale::create([
        'business_id' => $otherBusiness->id,
        'user_id' => User::factory()->create(['business_id' => $otherBusiness->id])->id,
        'total_amount' => 99000.00,
        'payment_method' => 'tarjeta',
    ]);

    $response = $this->actingAs($user)->get(route('sales.index'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Sales/Index')
        ->has('sales.data', 1)
        ->where('sales.data.0.id', $sale->id)
    );
});

test('user can register a cash sale and customer accumulates points', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    $customer = Customer::create([
        'business_id' => $business->id,
        'name' => 'Juan Pérez',
        'phone' => '3001234567',
        'points' => 0,
    ]);

    $product = Product::create([
        'business_id' => $business->id,
        'name' => 'Resma Papel Carta',
        'price' => 20000.00,
        'stock' => 10,
    ]);

    $response = $this->actingAs($user)->post(route('sales.store'), [
        'customer_id' => $customer->id,
        'payment_method' => 'efectivo',
        'products' => [
            ['id' => $product->id, 'quantity' => 2], // Total = 40.000 COP -> 8 puntos
        ],
    ]);

    $response->assertRedirect(route('dashboard'));

    // Verificar que el stock se redujo
    expect($product->fresh()->stock)->toBe(8);

    // Verificar que la venta fue registrada
    $this->assertDatabaseHas('sales', [
        'business_id' => $business->id,
        'user_id' => $user->id,
        'total_amount' => 40000.00,
        'payment_method' => 'efectivo',
    ]);

    // Verificar que los ítems individuales de la venta fueron registrados
    $this->assertDatabaseHas('sale_items', [
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 20000.00,
        'subtotal' => 40000.00,
    ]);

    // Verificar que el cliente acumuló 8 puntos (40.000 / 5.000)
    expect($customer->fresh()->points)->toBe(8);
});
