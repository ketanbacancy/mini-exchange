<?php

namespace App\Services;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Events\OrderMatched;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderService
{
    public function placeOrder(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            /** @var User $user */
            $user = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $side = OrderSide::from($data['side']);
            $symbol = strtoupper($data['symbol']);
            $price = (string) $data['price'];
            $amount = (string) $data['amount'];


            if (bccomp($price, '0', 8) <= 0 || bccomp($amount, '0', 8) <= 0) {
                throw new InvalidArgumentException('Price and amount must be positive');
            }

            $lockedUsd = '0';

            if ($side === OrderSide::BUY) {
                // Lock 1.5% extra for commission
                $notional = bcmul($price, $amount, 8);
                $commission = bcmul($notional, '0.015', 8);
                $lockedUsd = bcadd($notional, $commission, 8);

                if (bccomp($user->balance, $lockedUsd, 8) < 0) {
                    throw new InvalidArgumentException('Insufficient USD balance');
                }

                $user->balance = bcsub($user->balance, $lockedUsd, 8);
                $user->save();
            } else {
                // SELL
                $asset = Asset::where('user_id', $user->getKey())
                    ->where('symbol', $symbol)
                    ->lockForUpdate()
                    ->first();

                if (! $asset) {
                    throw new InvalidArgumentException('No asset balance');
                }

                if (bccomp($asset->amount, $amount, 8) < 0) {
                    throw new InvalidArgumentException('Insufficient asset amount');
                }

                $asset->amount = bcsub($asset->amount, $amount, 8);
                $asset->locked_amount = bcadd($asset->locked_amount, $amount, 8);
                $asset->save();
            }

            $order = new Order();
            $order->user_id = $user->getKey();
            $order->symbol = $symbol;
            $order->side = $side;
            $order->price = $price;
            $order->amount = $amount;
            $order->status = OrderStatus::OPEN;
            $order->locked_usd = $lockedUsd;
            $order->save();

            // Try to match immediately
            $this->matchOrder($order);

            return $order->fresh();
        });
    }

    public function cancelOrder(User $user, Order $order): Order
    {
        return DB::transaction(function () use ($user, $order) {
            /** @var Order $order */
            $order = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($order->user_id !== $user->getKey()) {
                throw new InvalidArgumentException('Cannot cancel order you do not own');
            }

            if ($order->status !== OrderStatus::OPEN) {
                throw new InvalidArgumentException('Only open orders can be cancelled');
            }

            if ($order->side === OrderSide::BUY) {
                $user = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();
                $user->balance = bcadd($user->balance, $order->locked_usd, 8);
                $user->save();
                $order->locked_usd = '0';
            } else {
                $asset = Asset::where('user_id', $user->getKey())
                    ->where('symbol', $order->symbol)
                    ->lockForUpdate()
                    ->first();

                if ($asset) {
                    $asset->amount = bcadd($asset->amount, $order->amount, 8);
                    $asset->locked_amount = bcsub($asset->locked_amount, $order->amount, 8);
                    $asset->save();
                }
            }

            $order->status = OrderStatus::CANCELLED;
            $order->save();

            return $order->fresh();
        });
    }

    /**
     * Full match only – if the first counter order has a different amount,
     * we do nothing and keep the order open.
     */
    public function matchOrder(Order $order): ?Trade
    {
        return DB::transaction(function () use ($order) {
            /** @var Order $order */
            $order = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($order->status !== OrderStatus::OPEN) {
                return null;
            }

            $symbol = $order->symbol;
            $amount = (string) $order->amount;
            $side = $order->side;

            if ($side === OrderSide::BUY) {
                $counterQuery = Order::where('symbol', $symbol)
                    ->where('side', OrderSide::SELL)
                    ->where('status', OrderStatus::OPEN)
                    ->where('price', '<=', $order->price)
                    ->orderBy('created_at')
                    ->lockForUpdate();
            } else {
                $counterQuery = Order::where('symbol', $symbol)
                    ->where('side', OrderSide::BUY)
                    ->where('status', OrderStatus::OPEN)
                    ->where('price', '>=', $order->price)
                    ->orderBy('created_at')
                    ->lockForUpdate();
            }

            $counter = $counterQuery->first();

            if (! $counter) {
                return null;
            }

            // Full match only
            if (bccomp($counter->amount, $amount, 8) !== 0) {
                return null;
            }

            // Determine buyer/seller
            if ($side === OrderSide::BUY) {
                $buyOrder = $order;
                $sellOrder = $counter;
            } else {
                $buyOrder = $counter;
                $sellOrder = $order;
            }

            $buyer = User::whereKey($buyOrder->user_id)->lockForUpdate()->firstOrFail();
            $seller = User::whereKey($sellOrder->user_id)->lockForUpdate()->firstOrFail();

            $tradePrice = $sellOrder->price; // simple rule: trade at maker (sell) price
            $notional = bcmul($tradePrice, $amount, 8);
            $commissionRate = '0.015';
            $commission = bcmul($notional, $commissionRate, 8);

            // Buyer: locked_usd was already deducted; we now:
            // cost = notional + commission, refund the difference (if any)
            $cost = bcadd($notional, $commission, 8);
            $refund = bcsub($buyOrder->locked_usd, $cost, 8);

            if (bccomp($refund, '0', 8) < 0) {
                // Should not happen if validation correct, but be safe
                throw new \RuntimeException('Locked USD is less than cost');
            }

            $buyer->balance = bcadd($buyer->balance, $refund, 8);
            $buyer->save();

            $buyOrder->locked_usd = '0';
            $buyOrder->status = OrderStatus::FILLED;
            $buyOrder->save();

            // Give buyer the asset
            $buyerAsset = Asset::where('user_id', $buyer->getKey())
                ->where('symbol', $symbol)
                ->lockForUpdate()
                ->first();

            if (! $buyerAsset) {
                $buyerAsset = new Asset([
                    'user_id' => $buyer->getKey(),
                    'symbol' => $symbol,
                    'amount' => '0',
                    'locked_amount' => '0',
                ]);
            }

            $buyerAsset->amount = bcadd($buyerAsset->amount, $amount, 8);
            $buyerAsset->save();

            // Seller: unlock and remove asset (it was moved to locked_amount earlier)
            $sellerAsset = Asset::where('user_id', $seller->getKey())
                ->where('symbol', $symbol)
                ->lockForUpdate()
                ->first();

            if (! $sellerAsset) {
                throw new \RuntimeException('Seller asset not found during match');
            }

            $sellerAsset->locked_amount = bcsub($sellerAsset->locked_amount, $amount, 8);
            $sellerAsset->save();

            // Seller receives notional (no fee on seller side in this design)
            $seller->balance = bcadd($seller->balance, $notional, 8);
            $seller->save();

            $sellOrder->status = OrderStatus::FILLED;
            $sellOrder->save();

            // Record trade
            $trade = new Trade();
            $trade->symbol = $symbol;
            $trade->price = $tradePrice;
            $trade->amount = $amount;
            $trade->buyer_id = $buyer->getKey();
            $trade->seller_id = $seller->getKey();
            $trade->commission = $commission;
            $trade->save();

            // Broadcast real-time event
            event(new OrderMatched($trade, $buyOrder, $sellOrder, $buyer, $seller));

            return $trade;
        });
    }
}
