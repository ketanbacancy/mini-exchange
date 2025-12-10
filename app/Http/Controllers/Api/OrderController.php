<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Http\Requests\Api\GetOrdersRequest;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Services\OrderService;
use Exception;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    public function index(GetOrdersRequest $request)
    {
        $symbol = strtoupper($request->query('symbol'));

        $orders = Order::where('symbol', $symbol)
            ->where('status', OrderStatus::OPEN)
            ->orderBy('price', 'desc')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => $orders,
        ]);
    }

    public function store(StoreOrderRequest $request)
    {
        try {
            $order = $this->orderService->placeOrder($request->user(), $request->all());

            return response()->json([
                'data' => $order,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function cancel(Request $request, Order $order)
    {
        try {
            $order = $this->orderService->cancelOrder($request->user(), $order);

            return response()->json([
                'data' => $order,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function myOrders(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return OrderResource::collection(
            Order::where('user_id', $request->user()->id)
                ->orderByDesc('created_at')
                ->get()
        );
    }

    public function trades(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $symbol = $request->query('symbol', 'BTC');

        return \Illuminate\Http\Resources\Json\JsonResource::collection(
            \App\Models\Trade::where('symbol', $symbol)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
        );
    }
}
