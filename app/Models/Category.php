<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $appends = ['leadchange_url'];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'max_shares',
        'is_active',
        'sort_order',
        'custom_fields',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'custom_fields' => 'array',
        ];
    }

    /**
     * @return HasMany<CategoryPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(CategoryPrice::class);
    }

    /**
     * @return HasOne<CategoryPrice, $this>
     */
    public function currentPrice(): HasOne
    {
        return $this->hasOne(CategoryPrice::class)
            ->whereNull('valid_to')
            ->latestOfMany('valid_from');
    }

    /**
     * @return BelongsToMany<Lead, $this>
     */
    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class)->withPivot('assigned_at');
    }

    /**
     * @return HasMany<NotificationSetting, $this>
     */
    public function getLeadchangeUrlAttribute(): string
    {
        $base = rtrim(config('app.url'), '/');

        return "{$base}/api/leadchange?email=USER_EMAIL&tel=USER_PHONE&category_destination={$this->id}";
    }

    public function notificationSettings(): HasMany
    {
        return $this->hasMany(NotificationSetting::class);
    }
}
