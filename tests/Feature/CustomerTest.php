<?php

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Customer;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('employees and admins can view customers of their business only', function () {
    $business = Business::factory()->create(['type' => 'barberia']);
    $employee = User::factory()->create(['business_id' => $business->id, 'role' => 'empleado']);

    $customer = Customer::create([
        'business_id' => $business->id,
        'name' => 'Camilo Torres',
        'phone' => '3001112233',
    ]);

    $otherBusiness = Business::factory()->create();
    Customer::create([
        'business_id' => $otherBusiness->id,
        'name' => 'Cliente Ajeno',
        'phone' => '3009998877',
    ]);

    $response = $this->actingAs($employee)->get(route('customers.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Customers/Index')
        ->has('customers.data', 1)
        ->where('customers.data.0.name', 'Camilo Torres')
    );
});

test('user can create a customer in their business', function () {
    $business = Business::factory()->create(['type' => 'barberia']);
    $user = User::factory()->create(['business_id' => $business->id]);

    $response = $this->actingAs($user)->post(route('customers.store'), [
        'name' => 'Sofía Méndez',
        'phone' => '3114445566',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('customers', [
        'business_id' => $business->id,
        'name' => 'Sofía Méndez',
        'phone' => '3114445566',
        'points' => 0,
    ]);
});

test('customer name is required', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    $response = $this->actingAs($user)->post(route('customers.store'), [
        'name' => '',
        'phone' => '3114445566',
    ]);

    $response->assertSessionHasErrors('name');
    $this->assertDatabaseCount('customers', 0);
});

test('user cannot update a customer from another business', function () {
    $businessA = Business::factory()->create();
    $userA = User::factory()->create(['business_id' => $businessA->id]);

    $businessB = Business::factory()->create();
    $customerB = Customer::create([
        'business_id' => $businessB->id,
        'name' => 'Cliente B',
        'phone' => '3002223344',
    ]);

    $response = $this->actingAs($userA)->put(route('customers.update', $customerB), [
        'name' => 'Nombre Modificado',
        'phone' => '3002223344',
    ]);

    $response->assertForbidden();
    expect($customerB->fresh()->name)->toBe('Cliente B');
});

test('user can update a customer of their business', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    $customer = Customer::create([
        'business_id' => $business->id,
        'name' => 'Nombre Viejo',
        'phone' => '3003334455',
    ]);

    $response = $this->actingAs($user)->put(route('customers.update', $customer), [
        'name' => 'Nombre Nuevo',
        'phone' => '3003334455',
    ]);

    $response->assertRedirect();
    expect($customer->fresh()->name)->toBe('Nombre Nuevo');
});

test('customer with appointments cannot be deleted', function () {
    $business = Business::factory()->create(['type' => 'barberia']);
    $barber = User::factory()->create(['business_id' => $business->id]);

    $customer = Customer::create([
        'business_id' => $business->id,
        'name' => 'Con Citas',
        'phone' => '3005556677',
    ]);

    Appointment::create([
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'user_id' => $barber->id,
        'start_time' => now()->addHours(2),
        'status' => 'agendada',
    ]);

    $response = $this->actingAs($barber)->delete(route('customers.destroy', $customer));

    $response->assertSessionHasErrors('error');
    expect($customer->fresh())->not->toBeNull();
});

test('customer without appointments can be deleted', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['business_id' => $business->id]);

    $customer = Customer::create([
        'business_id' => $business->id,
        'name' => 'Sin Citas',
        'phone' => '3006667788',
    ]);

    $response = $this->actingAs($user)->delete(route('customers.destroy', $customer));

    $response->assertRedirect();
    $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
});
