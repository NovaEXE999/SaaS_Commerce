<?php

use App\Models\Business;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard and receive multi-tenant metrics', function () {
    $business = Business::factory()->create(['type' => 'tienda']);
    $user = User::factory()->create(['business_id' => $business->id, 'role' => 'admin']);

    // Crear una venta de hoy
    Sale::create([
        'business_id' => $business->id,
        'user_id' => $user->id,
        'total_amount' => 50000.00,
        'payment_method' => 'efectivo',
    ]);

    // Crear un producto con stock crítico
    Product::create([
        'business_id' => $business->id,
        'name' => 'Marcador Permanente',
        'price' => 3000,
        'stock' => 3,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('metrics.todaySales')
        ->has('metrics.chart')
        ->has('lowStockProducts', 1)
        ->where('metrics.todaySales', 50000)
    );
});
