<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'balance',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'balance' => 'decimal:8',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function trades()
    {
        return $this->hasMany(Trade::class, 'buyer_id')
            ->orWhere('seller_id', $this->getKey());
    }
}
