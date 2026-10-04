<?php

use App\Jobs\SendN8nWebhook;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

test('reminder command dispatches webhooks for appointments starting in one hour', function () {
    Queue::fake();

    $business = Business::factory()->create(['type' => 'barberia']);
    $barber = User::factory()->create(['business_id' => $business->id]);
    $customer = Customer::create([
        'business_id' => $business->id,
        'name' => 'Recordatorio Uno',
        'phone' => '3001002003',
    ]);

    Appointment::create([
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'user_id' => $barber->id,
        'start_time' => now()->addHour(),
        'status' => 'agendada',
    ]);

    $this->artisan('appointments:notify-reminders')
        ->expectsOutputToContain('recordatorio')
        ->assertSuccessful();

    Queue::assertPushed(SendN8nWebhook::class, function ($job) {
        return $job->event === '1_hour_reminder';
    });
});

test('reminder command ignores cancelled appointments and distant dates', function () {
    Queue::fake();

    $business = Business::factory()->create(['type' => 'barberia']);
    $barber = User::factory()->create(['business_id' => $business->id]);
    $customer = Customer::create([
        'business_id' => $business->id,
        'name' => 'Sin Recordatorio',
        'phone' => '3004005006',
    ]);

    Appointment::create([
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'user_id' => $barber->id,
        'start_time' => now()->addHour(),
        'status' => 'cancelada',
    ]);

    Appointment::create([
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'user_id' => $barber->id,
        'start_time' => now()->addDays(5),
        'status' => 'agendada',
    ]);

    $this->artisan('appointments:notify-reminders')->assertSuccessful();

    Queue::assertNotPushed(SendN8nWebhook::class);
});

test('product min_stock is persisted and dashboard flags custom threshold', function () {
    $business = Business::factory()->create(['type' => 'tienda']);
    $user = User::factory()->create(['business_id' => $business->id, 'role' => 'admin']);

    $this->actingAs($user)->post(route('products.store'), [
        'name' => 'Borrador',
        'price' => 1000,
        'stock' => 8,
        'min_stock' => 10,
    ])->assertRedirect();

    $this->assertDatabaseHas('products', [
        'business_id' => $business->id,
        'name' => 'Borrador',
        'stock' => 8,
        'min_stock' => 10,
    ]);

    // Stock (8) <= min_stock (10): debe aparecer como crítico en el dashboard
    $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('lowStockProducts', 1)
            ->where('lowStockProducts.0.name', 'Borrador')
        );
});

test('product above its min_stock is not flagged as low stock', function () {
    $business = Business::factory()->create(['type' => 'tienda']);
    $user = User::factory()->create(['business_id' => $business->id, 'role' => 'admin']);

    Product::create([
        'business_id' => $business->id,
        'name' => 'Regla 30cm',
        'price' => 2500,
        'stock' => 20,
        'min_stock' => 5,
    ]);

    $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('lowStockProducts', 0)
        );
});
