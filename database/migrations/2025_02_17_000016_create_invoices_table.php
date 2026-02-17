<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->enum('type', ['invoice', 'credit_note'])->default('invoice');
            $table->string('fatture_cloud_id')->nullable();
            $table->enum('sdi_status', ['pending', 'sent', 'delivered', 'accepted', 'rejected', 'not_delivered', 'error'])->default('pending');
            $table->string('sdi_message')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('vat_rate', 5, 2);
            $table->decimal('vat_amount', 10, 2);
            $table->decimal('total', 10, 2);
            $table->json('billing_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('issued_at');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('sdi_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
