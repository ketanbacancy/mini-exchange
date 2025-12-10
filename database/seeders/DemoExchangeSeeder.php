<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoExchangeSeeder extends Seeder
{
    public function run(): void
    {
        // Buyer: Alice – plenty of USD, no crypto
        $alice = User::firstOrCreate(
            ['email' => 'alice@example.com'],
            [
                'name' => 'Alice Buyer',
                'password' => Hash::make('password'),
                'balance' => 100000, // 100k USD
            ]
        );

        // Seller: Bob – some BTC, small USD
        $bob = User::firstOrCreate(
            ['email' => 'bob@example.com'],
            [
                'name' => 'Bob Seller',
                'password' => Hash::make('password'),
                'balance' => 1000, // just a bit of USD
            ]
        );

        // Bob has BTC to sell
        Asset::updateOrCreate(
            [
                'user_id' => $bob->id,
                'symbol'  => 'BTC',
            ],
            [
                'amount'        => 0.5,  // available BTC
                'locked_amount' => 0,
            ]
        );

        // Optional: give Alice some ETH too
        Asset::updateOrCreate(
            [
                'user_id' => $alice->id,
                'symbol'  => 'ETH',
            ],
            [
                'amount'        => 5,
                'locked_amount' => 0,
            ]
        );
    }
}
