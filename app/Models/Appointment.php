<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'customer_id',
        'user_id',
        'service_id',
        'price',
        'start_time',
        'status',
        'justification',
        'client_approved',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'start_time' => 'datetime',
            'client_approved' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class);
    }
}
