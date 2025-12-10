<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Services\OrderService;
use App\Enums\OrderSide;

class BobSellSeeder extends Seeder
{
    public function run(): void
    {
        $bob = User::where('email', 'bob@example.com')->firstOrFail();

        // Ensure Bob has assets
        $asset = \App\Models\Asset::firstOrCreate(
            ['user_id' => $bob->id, 'symbol' => 'BTC'],
            ['amount' => 10, 'locked_amount' => 0]
        );
        $asset->amount = 10;
        $asset->save();

        $service = new OrderService();
        $order = $service->placeOrder($bob, [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => 60000,
            'amount' => 0.1
        ]);

        echo "Placed Sell Order #{$order->id} for Bob\n";
    }
}
