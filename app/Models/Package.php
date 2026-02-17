<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_ids',
        'name',
        'description',
        'exclusive_lead_quantity',
        'exclusive_price',
        'shared_lead_quantity',
        'shared_price',
        'is_active',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category_ids' => 'array',
            'exclusive_price' => 'decimal:2',
            'shared_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<UserPackage, $this>
     */
    public function userPackages(): HasMany
    {
        return $this->hasMany(UserPackage::class);
    }
}
