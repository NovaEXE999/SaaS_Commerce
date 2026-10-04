<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Models\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user and their business.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'business_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'in:tienda,papeleria,barberia'],
        ])->validate();

        return DB::transaction(function () use ($input) {
            // 1. Crear el Negocio (Tenant)
            $business = Business::create([
                'name' => $input['business_name'],
                'type' => $input['business_type'],
            ]);

            // 2. Crear el Usuario (Admin) asignado al nuevo negocio
            return User::create([
                'business_id' => $business->id,
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'role' => 'admin', // Quien crea el negocio es el Administrador
            ]);
        });
    }
}
