<?php

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

test('user can create a service in their business', function () {
    $business = Business::factory()->create(['type' => 'barberia']);
    $user = User::factory()->create(['business_id' => $business->id]);

    $response = $this->actingAs($user)->post(route('services.store'), [
        'name' => 'Corte Clásico',
        'description' => 'Tijera o máquina + lavado',
        'price' => 25000,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('services', [
        'business_id' => $business->id,
        'name' => 'Corte Clásico',
        'price' => 25000.00,
    ]);
});

test('services list is isolated per business', function () {
    $business = Business::factory()->create(['type' => 'barberia']);
    $user = User::factory()->create(['business_id' => $business->id]);

    Service::create(['business_id' => $business->id, 'name' => 'Corte', 'price' => 25000]);

    $otherBusiness = Business::factory()->create(['type' => 'barberia']);
    Service::create(['business_id' => $otherBusiness->id, 'name' => 'Ajeno', 'price' => 10000]);

    $this->actingAs($user)->get(route('services.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Services/Index')
            ->has('services.data', 1)
            ->where('services.data.0.name', 'Corte')
        );
});

test('user cannot update a service from another business', function () {
    $businessA = Business::factory()->create(['type' => 'barberia']);
    $userA = User::factory()->create(['business_id' => $businessA->id]);

    $businessB = Business::factory()->create(['type' => 'barberia']);
    $serviceB = Service::create(['business_id' => $businessB->id, 'name' => 'Barba', 'price' => 18000]);

    $response = $this->actingAs($userA)->put(route('services.update', $serviceB), [
        'name' => 'Barba Modificada',
        'price' => 20000,
    ]);

    $response->assertForbidden();
    expect($serviceB->fresh()->name)->toBe('Barba');
});

test('appointment booking requires a service', function () {
    $business = Business::factory()->create(['type' => 'barberia']);
    $user = User::factory()->create(['business_id' => $business->id]);
    $customer = Customer::create(['business_id' => $business->id, 'name' => 'Sin Servicio']);

    $response = $this->actingAs($user)->post(route('appointments.store'), [
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'start_time' => now()->addHours(2)->toDateTimeString(),
    ]);

    $response->assertSessionHasErrors('service_id');
    $this->assertDatabaseCount('appointments', 0);
});

test('appointment booking rejects a service from another business', function () {
    $businessA = Business::factory()->create(['type' => 'barberia']);
    $userA = User::factory()->create(['business_id' => $businessA->id]);
    $customerA = Customer::create(['business_id' => $businessA->id, 'name' => 'Cliente A']);

    $businessB = Business::factory()->create(['type' => 'barberia']);
    $serviceB = Service::create(['business_id' => $businessB->id, 'name' => 'Ajeno', 'price' => 10000]);

    $response = $this->actingAs($userA)->post(route('appointments.store'), [
        'customer_id' => $customerA->id,
        'user_id' => $userA->id,
        'service_id' => $serviceB->id,
        'start_time' => now()->addHours(2)->toDateTimeString(),
    ]);

    $response->assertNotFound();
    $this->assertDatabaseCount('appointments', 0);
});

test('completing an appointment records the sale and awards points', function () {
    Event::fake();
    Queue::fake();

    $business = Business::factory()->create(['type' => 'barberia']);
    $barber = User::factory()->create(['business_id' => $business->id]);
    $customer = Customer::create(['business_id' => $business->id, 'name' => 'Fiel', 'points' => 0]);
    $service = Service::create(['business_id' => $business->id, 'name' => 'Corte + Barba', 'price' => 38000]);

    $appointment = Appointment::create([
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'user_id' => $barber->id,
        'service_id' => $service->id,
        'price' => 38000,
        'start_time' => now()->subHour(),
        'status' => 'agendada',
    ]);

    $response = $this->actingAs($barber)->put(route('appointments.status', $appointment), [
        'status' => 'completada',
        'payment_method' => 'efectivo',
    ]);

    $response->assertRedirect();
    expect($appointment->fresh()->status)->toBe('completada');

    // Venta vinculada a la cita, atribuida al barbero
    $this->assertDatabaseHas('sales', [
        'business_id' => $business->id,
        'user_id' => $barber->id,
        'appointment_id' => $appointment->id,
        'total_amount' => 38000.00,
        'payment_method' => 'efectivo',
    ]);

    // 38.000 / 5.000 = 7 puntos
    expect($customer->fresh()->points)->toBe(7);
});

test('completing an appointment requires a payment method', function () {
    $business = Business::factory()->create(['type' => 'barberia']);
    $barber = User::factory()->create(['business_id' => $business->id]);
    $customer = Customer::create(['business_id' => $business->id, 'name' => 'Sin Pago']);
    $service = Service::create(['business_id' => $business->id, 'name' => 'Corte', 'price' => 25000]);

    $appointment = Appointment::create([
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'user_id' => $barber->id,
        'service_id' => $service->id,
        'price' => 25000,
        'start_time' => now()->subHour(),
        'status' => 'agendada',
    ]);

    $response = $this->actingAs($barber)->put(route('appointments.status', $appointment), [
        'status' => 'completada',
    ]);

    $response->assertSessionHasErrors('payment_method');
    $this->assertDatabaseCount('sales', 0);
    expect($appointment->fresh()->status)->toBe('agendada');
});

test('completing a priceless appointment records no sale', function () {
    $business = Business::factory()->create(['type' => 'barberia']);
    $barber = User::factory()->create(['business_id' => $business->id]);
    $customer = Customer::create(['business_id' => $business->id, 'name' => 'Legado']);

    $appointment = Appointment::create([
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'user_id' => $barber->id,
        'price' => 0,
        'start_time' => now()->subHour(),
        'status' => 'agendada',
    ]);

    $response = $this->actingAs($barber)->put(route('appointments.status', $appointment), [
        'status' => 'completada',
        'payment_method' => 'efectivo',
    ]);

    $response->assertRedirect();
    expect($appointment->fresh()->status)->toBe('completada');
    $this->assertDatabaseCount('sales', 0);
});

test('completed appointment revenue shows up in dashboard metrics', function () {
    Event::fake();
    Queue::fake();

    $business = Business::factory()->create(['type' => 'barberia']);
    $admin = User::factory()->create(['business_id' => $business->id, 'role' => 'admin']);
    $customer = Customer::create(['business_id' => $business->id, 'name' => 'Dashboard']);
    $service = Service::create(['business_id' => $business->id, 'name' => 'Corte', 'price' => 25000]);

    $appointment = Appointment::create([
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'user_id' => $admin->id,
        'service_id' => $service->id,
        'price' => 25000,
        'start_time' => now()->subHour(),
        'status' => 'agendada',
    ]);

    $this->actingAs($admin)->put(route('appointments.status', $appointment), [
        'status' => 'completada',
        'payment_method' => 'tarjeta',
    ]);

    $this->actingAs($admin)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('metrics.todaySales', 25000)
        );
});

test('public booking with a service snapshots its price', function () {
    Event::fake();
    Queue::fake();

    $business = Business::factory()->create(['type' => 'barberia']);
    $barber = User::factory()->create(['business_id' => $business->id]);
    $service = Service::create(['business_id' => $business->id, 'name' => 'Perfilado', 'price' => 18000]);

    $response = $this->post(route('public-booking.store'), [
        'business_id' => $business->id,
        'customer_name' => 'Online Con Servicio',
        'customer_phone' => '3008889900',
        'user_id' => $barber->id,
        'service_id' => $service->id,
        'start_time' => now()->addDays(1)->toDateTimeString(),
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('appointments', [
        'business_id' => $business->id,
        'service_id' => $service->id,
        'price' => 18000.00,
        'status' => 'agendada',
    ]);
});
