<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMatched implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Trade $trade;
    public Order $buyOrder;
    public Order $sellOrder;
    public User $buyer;
    public User $seller;

    public function __construct(Trade $trade, Order $buyOrder, Order $sellOrder, User $buyer, User $seller)
    {
        $this->trade = $trade;
        $this->buyOrder = $buyOrder;
        $this->sellOrder = $sellOrder;
        $this->buyer = $buyer;
        $this->seller = $seller;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->buyer->getKey()),
            new PrivateChannel('user.' . $this->seller->getKey()),
        ];
    }

    public function broadcastAs(): string
    {
        return 'OrderMatched';
    }

    public function broadcastWith(): array
    {
        return [
            'trade' => [
                'id' => $this->trade->id,
                'symbol' => $this->trade->symbol,
                'price' => $this->trade->price,
                'amount' => $this->trade->amount,
                'commission' => $this->trade->commission,
            ],
            'buy_order' => [
                'id' => $this->buyOrder->id,
                'status' => $this->buyOrder->status,
            ],
            'sell_order' => [
                'id' => $this->sellOrder->id,
                'status' => $this->sellOrder->status,
            ],
            'buyer' => [
                'id' => $this->buyer->id,
                'balance' => $this->buyer->balance,
            ],
            'seller' => [
                'id' => $this->seller->id,
                'balance' => $this->seller->balance,
            ],
        ];
    }
}
