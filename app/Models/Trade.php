<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'price',
        'amount',
        'buyer_id',
        'seller_id',
        'commission',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'amount' => 'decimal:8',
        'commission' => 'decimal:8',
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
