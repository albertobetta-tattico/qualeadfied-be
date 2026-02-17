<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('province_id')->constrained('provinces')->onDelete('cascade');
            $table->foreignId('source_id')->constrained('lead_sources')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->text('request_text');
            $table->json('extra_tags')->nullable();
            $table->enum('status', ['free', 'sold_exclusive', 'sold_shared', 'exhausted'])->default('free');
            $table->integer('current_shares')->default(0);
            $table->date('generated_at');
            $table->string('external_id')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'status', 'generated_at']);
            $table->index(['category_id', 'province_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
