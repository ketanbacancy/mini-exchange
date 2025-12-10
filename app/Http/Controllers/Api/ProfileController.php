<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('assets');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'balance' => $user->balance,
            'assets' => $user->assets->map(function ($asset) {
                return [
                    'symbol' => $asset->symbol,
                    'amount' => $asset->amount,
                    'locked_amount' => $asset->locked_amount,
                ];
            }),
        ]);
    }
}
