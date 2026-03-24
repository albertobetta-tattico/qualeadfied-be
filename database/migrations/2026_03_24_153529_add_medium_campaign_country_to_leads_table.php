<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aggiunge campi per tracciamento acquisizione: mezzo, campagna, country.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('medium', 100)->nullable()->after('source_id');
            $table->string('campaign', 255)->nullable()->after('medium');
            $table->string('country', 2)->default('IT')->after('province_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['medium', 'campaign', 'country']);
        });
    }
};
