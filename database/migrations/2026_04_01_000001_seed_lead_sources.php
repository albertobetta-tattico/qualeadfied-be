<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('lead_sources')->insert([
            [
                'name' => 'Meta',
                'slug' => 'meta',
                'description' => 'Lead provenienti da campagne Meta (Facebook/Instagram Ads)',
                'api_key' => Str::uuid()->toString(),
                'is_active' => true,
                'config' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Delera',
                'slug' => 'delera',
                'description' => 'Lead importati tramite Delera/Zapier',
                'api_key' => Str::uuid()->toString(),
                'is_active' => true,
                'config' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('lead_sources')->whereIn('slug', ['meta', 'delera'])->delete();
    }
};
