<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Aggiungere full_name (unione di first_name + last_name)
            $table->string('full_name', 200)->after('source_id');

            // Aggiungere address (testo libero per dati non strutturati, es. Meta)
            $table->string('address', 500)->nullable()->after('phone');

            // Rendere province_id facoltativa
            $table->foreignId('province_id')->nullable()->change();

            // Rimuovere first_name e last_name
            $table->dropColumn(['first_name', 'last_name']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Ripristinare first_name e last_name
            $table->string('first_name')->after('source_id');
            $table->string('last_name')->after('first_name');

            // Rimuovere full_name e address
            $table->dropColumn(['full_name', 'address']);

            // province_id torna NOT NULL
            $table->foreignId('province_id')->nullable(false)->change();
        });
    }
};
