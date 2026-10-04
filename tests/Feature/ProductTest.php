<?php

use App\Models\Business;
use App\Models\Product;
use App\Models\User;

test('authenticated user can view products belonging to their business', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    $productInBusiness = Product::create([
        'business_id' => $business->id,
        'name' => 'Cuaderno Cuadriculado',
        'description' => 'Cuaderno 100 hojas',
        'price' => 3500.00,
        'stock' => 20,
    ]);

    $otherBusiness = Business::factory()->create();
    $productOtherBusiness = Product::create([
        'business_id' => $otherBusiness->id,
        'name' => 'Tijeras',
        'price' => 2000.00,
        'stock' => 10,
    ]);

    $response = $this->actingAs($user)->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('Cuaderno Cuadriculado');
    $response->assertDontSee('Tijeras');
});

test('user can create a product in their business', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    $response = $this->actingAs($user)->post(route('products.store'), [
        'name' => 'Lápiz HB',
        'description' => 'Lápiz de grafito',
        'price' => 1500,
        'stock' => 50,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('products', [
        'business_id' => $business->id,
        'name' => 'Lápiz HB',
        'stock' => 50,
    ]);
});

test('user cannot update a product from another business', function () {
    $businessA = Business::factory()->create();
    $userA = User::factory()->create(['business_id' => $businessA->id]);

    $businessB = Business::factory()->create();
    $productB = Product::create([
        'business_id' => $businessB->id,
        'name' => 'Pomada Fijadora',
        'price' => 25000,
        'stock' => 5,
    ]);

    $response = $this->actingAs($userA)->put(route('products.update', $productB), [
        'name' => 'Pomada Modificada',
        'price' => 30000,
        'stock' => 10,
    ]);

    $response->assertForbidden();
});

test('user can soft delete a product', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    $product = Product::create([
        'business_id' => $business->id,
        'name' => 'Cera para cabello',
        'price' => 20000,
        'stock' => 8,
    ]);

    $response = $this->actingAs($user)->delete(route('products.destroy', $product));

    $response->assertRedirect();
    expect($product->fresh()?->trashed())->toBeTrue();
});
