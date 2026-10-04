<?php

use App\Jobs\SendN8nWebhook;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastFactory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

test('appointments index can be rendered for authenticated users', function () {
    $business = Business::factory()->create(['type' => 'barberia']);
    $user = User::factory()->create(['business_id' => $business->id]);

    $response = $this->actingAs($user)->get(route('appointments.index'));

    $response->assertOk();
});

test('appointment creation stores appointment and dispatches webhook and event', function () {
    Event::fake();
    Queue::fake();

    $business = Business::factory()->create(['type' => 'barberia']);
    $user = User::factory()->create(['business_id' => $business->id]);
    $customer = Customer::create([
        'business_id' => $business->id,
        'name' => 'Carlos Andrés',
        'phone' => '3119876543',
    ]);
    $service = Service::create([
        'business_id' => $business->id,
        'name' => 'Corte Clásico',
        'price' => 25000.00,
    ]);

    $response = $this->actingAs($user)->post(route('appointments.store'), [
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'service_id' => $service->id,
        'start_time' => now()->addHours(2)->toDateTimeString(),
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('appointments', [
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'service_id' => $service->id,
        'price' => 25000.00,
        'status' => 'agendada',
    ]);

    Queue::assertPushed(SendN8nWebhook::class);
});

test('cancelling an appointment requires justification for audit', function () {
    Event::fake();
    Queue::fake();

    $business = Business::factory()->create(['type' => 'barberia']);
    $user = User::factory()->create(['business_id' => $business->id]);
    $customer = Customer::create([
        'business_id' => $business->id,
        'name' => 'Carlos Andrés',
    ]);

    $appointment = Appointment::create([
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'start_time' => now()->addHours(2),
        'status' => 'agendada',
    ]);

    // Intento sin justificación
    $response = $this->actingAs($user)->put(route('appointments.status', $appointment), [
        'status' => 'cancelada',
        'justification' => '',
    ]);

    $response->assertSessionHasErrors('justification');

    // Con justificación válida
    $responseSuccess = $this->actingAs($user)->put(route('appointments.status', $appointment), [
        'status' => 'cancelada',
        'justification' => 'Cliente canceló por motivos de fuerza mayor',
    ]);

    $responseSuccess->assertRedirect();
    expect($appointment->fresh()->status)->toBe('cancelada');
    expect($appointment->fresh()->justification)->toBe('Cliente canceló por motivos de fuerza mayor');
});

test('appointment is still created when the websocket server is unavailable', function () {
    Event::fake();
    Queue::fake();

    $this->mock(BroadcastFactory::class, function ($mock) {
        $mock->shouldReceive('event')->andThrow(
            new BroadcastException('Pusher error: 404 Not Found')
        );
    });

    $business = Business::factory()->create(['type' => 'barberia']);
    $user = User::factory()->create(['business_id' => $business->id]);
    $customer = Customer::create([
        'business_id' => $business->id,
        'name' => 'Sin Reverb',
        'phone' => '3001238899',
    ]);
    $service = Service::create([
        'business_id' => $business->id,
        'name' => 'Corte Clásico',
        'price' => 25000.00,
    ]);

    $response = $this->actingAs($user)->post(route('appointments.store'), [
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'service_id' => $service->id,
        'start_time' => now()->addHours(2)->toDateTimeString(),
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('appointments', [
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'service_id' => $service->id,
        'status' => 'agendada',
    ]);
});
