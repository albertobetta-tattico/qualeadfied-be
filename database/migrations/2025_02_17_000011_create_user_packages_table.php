<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('package_id')->constrained('packages')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('package_name');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->integer('total_leads');
            $table->integer('exclusive_leads_total')->default(0);
            $table->integer('exclusive_leads_used')->default(0);
            $table->integer('shared_leads_total')->default(0);
            $table->integer('shared_leads_used')->default(0);
            $table->enum('status', ['active', 'exhausted', 'expired'])->default('active');
            $table->timestamp('purchased_at');
            $table->timestamp('expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_packages');
    }
};
