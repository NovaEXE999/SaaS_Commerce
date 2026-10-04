<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'customer_id',
        'sale_id',
        'points_redeemed',
        'discount_amount',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'points_redeemed' => 'integer',
            'discount_amount' => 'decimal:2',
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

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
