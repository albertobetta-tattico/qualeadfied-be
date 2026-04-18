<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'province_id',
        'source_id',
        'full_name',
        'email',
        'phone',
        'address',
        'request_text',
        'extra_tags',
        'status',
        'current_shares',
        'generated_at',
        'external_id',
        'medium',
        'campaign',
        'country',
        'origin',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'extra_tags' => 'array',
            'status' => LeadStatus::class,
            'generated_at' => 'date',
        ];
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('assigned_at');
    }

    /**
     * @return BelongsTo<Province, $this>
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * @return BelongsTo<LeadSource, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    /**
     * @return HasMany<LeadSale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(LeadSale::class);
    }

    /**
     * @return HasMany<UserLead, $this>
     */
    public function userLeads(): HasMany
    {
        return $this->hasMany(UserLead::class);
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
