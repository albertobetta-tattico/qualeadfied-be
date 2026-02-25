<?php

namespace Database\Seeders;

use App\Models\LeadSource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LeadSourceSeeder extends Seeder
{
    /**
     * Seed the lead_sources table.
     */
    public function run(): void
    {
        LeadSource::create([
            'name' => 'Sito Web',
            'slug' => 'sito-web',
            'description' => 'Lead provenienti dal sito web principale',
            'api_key' => Str::uuid()->toString(),
            'is_active' => true,
            'config' => ['webhook_url' => 'https://qualeadfied.com/api/webhooks/leads', 'auto_validate' => true],
        ]);

        LeadSource::create([
            'name' => 'Facebook Ads',
            'slug' => 'facebook-ads',
            'description' => 'Lead provenienti da campagne Facebook Ads',
            'api_key' => Str::uuid()->toString(),
            'is_active' => true,
            'config' => ['campaign_id' => 'fb_camp_2026', 'pixel_id' => '123456789'],
        ]);

        LeadSource::create([
            'name' => 'Google Ads',
            'slug' => 'google-ads',
            'description' => 'Lead provenienti da campagne Google Ads',
            'api_key' => Str::uuid()->toString(),
            'is_active' => true,
            'config' => ['campaign_id' => 'gads_camp_2026', 'conversion_id' => 'AW-987654321'],
        ]);

        LeadSource::create([
            'name' => 'Referral',
            'slug' => 'referral',
            'description' => 'Lead provenienti da programma referral',
            'api_key' => null,
            'is_active' => true,
            'config' => null,
        ]);

        LeadSource::create([
            'name' => 'Manuale',
            'slug' => 'manuale',
            'description' => 'Lead inseriti manualmente dagli operatori',
            'api_key' => null,
            'is_active' => true,
            'config' => null,
        ]);
    }
}
