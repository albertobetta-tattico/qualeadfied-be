<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->json('category_ids');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('exclusive_lead_quantity')->default(0);
            $table->decimal('exclusive_price', 10, 2)->default(0);
            $table->integer('shared_lead_quantity')->default(0);
            $table->decimal('shared_price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
