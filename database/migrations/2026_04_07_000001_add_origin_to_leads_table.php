<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aggiunge il campo "origin" (sorgente marketing del lead: meta, google ads,
     * google search, ...). Distinto dal source_id (LeadSource = sistema tecnico
     * di ingestione: meta, delera/zapier, ...).
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('origin', 50)->nullable()->after('source_id');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('origin');
        });
    }
};
