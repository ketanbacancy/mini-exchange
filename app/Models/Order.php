<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'symbol',
        'side',
        'price',
        'amount',
        'status',
        'locked_usd',
    ];

    protected $casts = [
        'side' => \App\Enums\OrderSide::class,
        'status' => \App\Enums\OrderStatus::class,
        'price' => 'decimal:8',
        'amount' => 'decimal:8',
        'locked_usd' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
