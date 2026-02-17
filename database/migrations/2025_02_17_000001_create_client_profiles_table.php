<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('company_name');
            $table->string('vat_number');
            $table->string('phone');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('billing_address');
            $table->string('billing_city');
            $table->string('billing_province');
            $table->string('billing_zip');
            $table->string('billing_country')->default('IT');
            $table->string('sdi_code')->nullable();
            $table->string('pec_email')->nullable();
            $table->boolean('free_trial_enabled')->default(false);
            $table->integer('free_trial_leads_remaining')->default(0);
            $table->boolean('email_notifications_enabled')->default(true);
            $table->boolean('marketing_consent')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_profiles');
    }
};
