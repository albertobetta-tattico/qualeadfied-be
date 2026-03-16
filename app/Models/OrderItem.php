<?php

namespace App\Models;

use App\Enums\AcquisitionMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = ['purchase_mode', 'price'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'lead_id',
        'package_id',
        'acquisition_mode',
        'unit_price',
        'quantity',
        'line_total',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acquisition_mode' => AcquisitionMode::class,
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    /**
     * Alias for 'acquisition_mode' – frontend uses 'purchase_mode'.
     */
    public function getPurchaseModeAttribute(): ?string
    {
        return $this->acquisition_mode?->value ?? $this->attributes['acquisition_mode'] ?? null;
    }

    /**
     * Alias for 'unit_price' – frontend uses 'price'.
     */
    public function getPriceAttribute(): ?string
    {
        return $this->unit_price;
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
