<?php

namespace App\Models;

use App\Enums\TransactionPaymentType;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'stripe_customer_id',
        'stripe_payment_method_id',
        'payment_type',
        'amount',
        'currency',
        'status',
        'stripe_response',
        'metadata',
        'failure_code',
        'failure_message',
        'processed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'stripe_response',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_type' => TransactionPaymentType::class,
            'amount' => 'decimal:2',
            'status' => TransactionStatus::class,
            'stripe_response' => 'array',
            'metadata' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
