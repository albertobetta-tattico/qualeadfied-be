<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->string('bank_iban')->nullable()->after('pec_email');
            $table->string('bank_account_holder')->nullable()->after('bank_iban');
            $table->string('bank_bic_swift')->nullable()->after('bank_account_holder');
            $table->string('bank_name')->nullable()->after('bank_bic_swift');
        });

        Schema::create('category_client_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('client_profile_id')->constrained('client_profiles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['category_id', 'client_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_client_profile');

        Schema::table('client_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'bank_iban',
                'bank_account_holder',
                'bank_bic_swift',
                'bank_name',
            ]);
        });
    }
};
