<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProfileController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);

    // Public orderbook for a symbol
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/trades', [OrderController::class, 'trades']);

    // Manage own orders
    Route::get('/my-orders', [OrderController::class, 'myOrders']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
});
