<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'user_id', 'payment_method', 'transaction_id', 'amount', 'status', 'paid_at'])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'user_id' => 'integer',
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}