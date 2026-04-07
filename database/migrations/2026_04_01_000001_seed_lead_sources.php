<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Unica fonte (LeadSource) supportata: Zapier.
     * Riusa l'API key che era stata generata in precedenza per "Meta",
     * così le integrazioni esistenti continuano a funzionare senza dover
     * riconfigurare il webhook.
     *
     * Il canale marketing originale del lead (meta, google ads, google search,
     * ...) viene tracciato a parte sul campo `leads.origin`.
     */
    public function up(): void
    {
        DB::table('lead_sources')->insert([
            'name' => 'Zapier',
            'slug' => 'zapier',
            'description' => 'Lead importati tramite Zapier',
            'api_key' => '79c59bc9-f2df-4fd1-84f7-0adfc73927a1',
            'is_active' => true,
            'config' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('lead_sources')->where('slug', 'zapier')->delete();
    }
};
