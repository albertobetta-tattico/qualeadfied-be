<?php

namespace Database\Seeders;

use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the users table with the test user and client profile.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'test@qualeadfied.com'],
            [
                'password' => 'Passw0rd!',
                'role' => 'client',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        ClientProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => 'Test Company S.r.l.',
                'vat_number' => 'IT00000000000',
                'phone' => '+39 02 0000000',
                'first_name' => 'Test',
                'last_name' => 'User',
                'billing_address' => 'Via Test 1',
                'billing_city' => 'Milano',
                'billing_province' => 'MI',
                'billing_zip' => '20100',
                'billing_country' => 'IT',
                'sdi_code' => '0000000',
                'pec_email' => 'test@pec.it',
                'free_trial_enabled' => true,
                'free_trial_leads_remaining' => 5,
                'email_notifications_enabled' => true,
                'marketing_consent' => true,
            ]
        );
    }
}
