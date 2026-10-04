<?php

use App\Models\Business;
use App\Models\User;

test('employees cannot access employee management', function () {
    $business = Business::factory()->create();
    $employee = User::factory()->create([
        'business_id' => $business->id,
        'role' => 'empleado',
    ]);

    $response = $this->actingAs($employee)->get(route('employees.index'));

    $response->assertForbidden();
});

test('administrators can view employees and create a new employee', function () {
    $business = Business::factory()->create();
    $admin = User::factory()->create([
        'business_id' => $business->id,
        'role' => 'admin',
    ]);

    $response = $this->actingAs($admin)->get(route('employees.index'));
    $response->assertOk();

    $createResponse = $this->actingAs($admin)->post(route('employees.store'), [
        'name' => 'Empleado Nuevo',
        'email' => 'nuevo.empleado@example.com',
        'password' => 'Password123!',
        'role' => 'empleado',
    ]);

    $createResponse->assertRedirect();
    $this->assertDatabaseHas('users', [
        'business_id' => $business->id,
        'email' => 'nuevo.empleado@example.com',
        'role' => 'empleado',
    ]);
});

test('administrator can soft delete and restore an employee', function () {
    $business = Business::factory()->create();
    $admin = User::factory()->create([
        'business_id' => $business->id,
        'role' => 'admin',
    ]);

    $employee = User::factory()->create([
        'business_id' => $business->id,
        'role' => 'empleado',
    ]);

    // Soft delete
    $deleteResponse = $this->actingAs($admin)->delete(route('employees.destroy', $employee));
    $deleteResponse->assertRedirect();
    expect($employee->fresh()->trashed())->toBeTrue();

    // Restore
    $restoreResponse = $this->actingAs($admin)->put(route('employees.restore', $employee));
    $restoreResponse->assertRedirect();
    expect($employee->fresh()->trashed())->toBeFalse();
});
