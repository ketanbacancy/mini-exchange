<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'side' => $this->side->value ?? $this->side, // specific or string
            'price' => (float) $this->price,
            'amount' => (float) $this->amount,
            'status' => $this->status->value ?? $this->status, // integer or enum
            'locked_usd' => (float) $this->locked_usd,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
