<?php

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('employees cannot access the audit tray', function () {
    $business = Business::factory()->create();
    $employee = User::factory()->create([
        'business_id' => $business->id,
        'role' => 'empleado',
    ]);

    $response = $this->actingAs($employee)->get(route('audit.index'));

    $response->assertForbidden();
});

test('administrators can access the audit tray and view justified cancellations', function () {
    $business = Business::factory()->create(['type' => 'barberia']);
    $admin = User::factory()->create([
        'business_id' => $business->id,
        'role' => 'admin',
    ]);

    $customer = Customer::create([
        'business_id' => $business->id,
        'name' => 'Felipe Gómez',
    ]);

    Appointment::create([
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'user_id' => $admin->id,
        'start_time' => now()->addDay(),
        'status' => 'cancelada',
        'justification' => 'Motivo de salud del barbero',
    ]);

    Sale::create([
        'business_id' => $business->id,
        'user_id' => $admin->id,
        'total_amount' => 150000,
        'payment_method' => 'transferencia',
        'payment_proof_path' => 'payment_proofs/sample.png',
    ]);

    $response = $this->actingAs($admin)->get(route('audit.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Audit/Index')
        ->has('appointmentAudits', 1)
        ->has('transferSales', 1)
        ->where('appointmentAudits.0.justification', 'Motivo de salud del barbero')
    );
});
