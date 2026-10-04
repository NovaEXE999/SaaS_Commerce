<?php

use App\Jobs\SendN8nWebhook;
use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

test('public home page renders with barbershop details and available barbers', function () {
    $business = Business::factory()->create(['type' => 'barberia', 'name' => 'Barbería Élite']);
    $barber = User::factory()->create(['business_id' => $business->id, 'name' => 'Mateo Barber']);

    $response = $this->withCookie('last_business_id', (string) $business->id)->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Welcome')
        ->has('barbershop')
        ->has('barbers', 1)
        ->where('barbershop.name', 'Barbería Élite')
        ->where('barbers.0.name', 'Mateo Barber')
    );
});

test('first-time visitors see the generic NovaSaaS platform landing', function () {
    Business::factory()->create(['type' => 'barberia']);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('WelcomePlatform')
        ->has('stats.businesses')
        ->has('stats.appointments')
        ->has('stats.points')
    );
});

test('visitors with an unknown business cookie see the platform landing', function () {
    $response = $this->withCookie('last_business_id', '999999')->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('WelcomePlatform')
    );
});

test('visitors returning from a store see its storefront landing', function () {
    $business = Business::factory()->create(['type' => 'tienda', 'name' => 'MiniMarket Don José']);
    Product::create([
        'business_id' => $business->id,
        'name' => 'Arroz Libra',
        'price' => 2800.00,
        'stock' => 20,
    ]);

    $response = $this->withCookie('last_business_id', (string) $business->id)->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('WelcomeBusiness')
        ->where('business.name', 'MiniMarket Don José')
        ->where('business.type', 'tienda')
        ->has('products', 1)
    );
});

test('authenticated users see their own business landing', function () {
    $business = Business::factory()->create(['type' => 'papeleria', 'name' => 'Papelería Central']);
    $user = User::factory()->create(['business_id' => $business->id]);

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('WelcomeBusiness')
        ->where('business.name', 'Papelería Central')
    );
});

test('authenticated requests remember the last business in a cookie', function () {
    $business = Business::factory()->create(['type' => 'tienda']);
    $user = User::factory()->create(['business_id' => $business->id]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertCookie('last_business_id', (string) $business->id);
});

test('public client can book an appointment online without account and trigger n8n webhook', function () {
    Event::fake();
    Queue::fake();

    $business = Business::factory()->create(['type' => 'barberia']);
    $barber = User::factory()->create(['business_id' => $business->id]);

    $startTime = now()->addDays(2)->setHour(14)->setMinute(0)->format('Y-m-d H:i:s');

    $response = $this->post(route('public-booking.store'), [
        'business_id' => $business->id,
        'customer_name' => 'David Ramírez',
        'customer_phone' => '3007654321',
        'user_id' => $barber->id,
        'start_time' => $startTime,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Cliente debe haberse creado automáticamente
    $this->assertDatabaseHas('customers', [
        'business_id' => $business->id,
        'name' => 'David Ramírez',
        'phone' => '3007654321',
    ]);

    // Cita debe haberse registrado
    $this->assertDatabaseHas('appointments', [
        'business_id' => $business->id,
        'user_id' => $barber->id,
        'status' => 'agendada',
    ]);

    // Webhook hacia n8n despachado
    Queue::assertPushed(SendN8nWebhook::class);
});
