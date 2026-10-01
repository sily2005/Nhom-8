<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payment_id',
    'gateway',
    'transaction_code',
    'response_code',
    'amount',
    'status',
    'raw_payload',
])]
class PaymentTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'payment_id' => 'integer',
            'amount' => 'decimal:2',
            'raw_payload' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
