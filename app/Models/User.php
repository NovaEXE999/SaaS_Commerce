<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

// NOTA (fase de pruebas): verificación de email desactivada temporalmente.
// Para reactivarla: implementar `MustVerifyEmail` y habilitar
// `Features::emailVerification()` en config/fortify.php.
class User extends Authenticatable implements PasskeyUser
{
    use HasFactory, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable;

    protected $fillable = [
        'business_id',
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'user_id');
    }
}

