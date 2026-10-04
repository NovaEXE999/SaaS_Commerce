<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;

test('customer can redeem points for a future discount', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    $customer = Customer::create([
        'business_id' => $business->id,
        'name' => 'Ana Gómez',
        'phone' => '3001112233',
        'points' => 10,
    ]);

    $response = $this->actingAs($user)->post(route('point-redemptions.store'), [
        'customer_id' => $customer->id,
        'points_to_redeem' => 4,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($customer->fresh()->points)->toBe(6);

    $this->assertDatabaseHas('point_redemptions', [
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'points_redeemed' => 4,
        'discount_amount' => 4000.00,
    ]);
});

test('points redemption fails when customer lacks balance', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    $customer = Customer::create([
        'business_id' => $business->id,
        'name' => 'Luis Díaz',
        'phone' => '3004445566',
        'points' => 2,
    ]);

    $response = $this->actingAs($user)->post(route('point-redemptions.store'), [
        'customer_id' => $customer->id,
        'points_to_redeem' => 5,
    ]);

    $response->assertSessionHasErrors('points_to_redeem');
    expect($customer->fresh()->points)->toBe(2);
    $this->assertDatabaseCount('point_redemptions', 0);
});

test('points redemption is isolated per business', function () {
    $businessA = Business::factory()->create();
    $userA = User::factory()->create(['business_id' => $businessA->id]);

    $businessB = Business::factory()->create();
    $customerB = Customer::create([
        'business_id' => $businessB->id,
        'name' => 'Cliente Otro Negocio',
        'phone' => '3007778899',
        'points' => 20,
    ]);

    $response = $this->actingAs($userA)->post(route('point-redemptions.store'), [
        'customer_id' => $customerB->id,
        'points_to_redeem' => 3,
    ]);

    $response->assertNotFound();
    expect($customerB->fresh()->points)->toBe(20);
    $this->assertDatabaseCount('point_redemptions', 0);
});

test('sale with points redemption applies discount and links redemption to the sale', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    $customer = Customer::create([
        'business_id' => $business->id,
        'name' => 'Marta Ruiz',
        'phone' => '3009990011',
        'points' => 10,
    ]);

    $product = Product::create([
        'business_id' => $business->id,
        'name' => 'Corte de cabello',
        'price' => 20000.00,
        'stock' => 10,
    ]);

    $response = $this->actingAs($user)->post(route('sales.store'), [
        'customer_id' => $customer->id,
        'payment_method' => 'efectivo',
        'points_to_redeem' => 5, // $5.000 de descuento
        'products' => [
            ['id' => $product->id, 'quantity' => 1], // Total bruto 20.000
        ],
    ]);

    $response->assertRedirect(route('dashboard'));

    // Total final: 20.000 - 5.000 = 15.000 -> 3 puntos nuevos (15.000 / 5.000)
    // Balance: 10 - 5 + 3 = 8
    expect($customer->fresh()->points)->toBe(8);

    $this->assertDatabaseHas('sales', [
        'business_id' => $business->id,
        'total_amount' => 15000.00,
    ]);

    $this->assertDatabaseHas('point_redemptions', [
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'points_redeemed' => 5,
        'discount_amount' => 5000.00,
    ]);
});

test('sale redemption fails without enough points', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    $customer = Customer::create([
        'business_id' => $business->id,
        'name' => 'Pedro León',
        'phone' => '3002223344',
        'points' => 1,
    ]);

    $product = Product::create([
        'business_id' => $business->id,
        'name' => 'Afeitadora',
        'price' => 15000.00,
        'stock' => 5,
    ]);

    $response = $this->actingAs($user)->post(route('sales.store'), [
        'customer_id' => $customer->id,
        'payment_method' => 'efectivo',
        'points_to_redeem' => 4,
        'products' => [
            ['id' => $product->id, 'quantity' => 1],
        ],
    ]);

    $response->assertSessionHasErrors('points_to_redeem');
    expect($customer->fresh()->points)->toBe(1);
    expect($product->fresh()->stock)->toBe(5);
    $this->assertDatabaseCount('sales', 0);
});
