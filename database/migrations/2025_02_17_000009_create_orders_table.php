<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->enum('type', ['single', 'package', 'free_trial']);
            $table->enum('payment_method', ['card', 'sepa', 'free']);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('vat_rate', 5, 2)->default(22.00);
            $table->decimal('vat_amount', 10, 2);
            $table->decimal('total', 10, 2);
            $table->enum('status', ['pending', 'processing', 'paid', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->json('billing_snapshot')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('invoice_url')->nullable();
            $table->string('payment_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
