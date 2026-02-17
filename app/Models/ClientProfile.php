<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientProfile extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'company_name',
        'vat_number',
        'phone',
        'first_name',
        'last_name',
        'billing_address',
        'billing_city',
        'billing_province',
        'billing_zip',
        'billing_country',
        'sdi_code',
        'pec_email',
        'free_trial_enabled',
        'free_trial_leads_remaining',
        'email_notifications_enabled',
        'marketing_consent',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'free_trial_enabled' => 'boolean',
            'email_notifications_enabled' => 'boolean',
            'marketing_consent' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
