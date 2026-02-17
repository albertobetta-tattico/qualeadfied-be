<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->enum('acquisition_type', ['exclusive', 'shared', 'free_trial']);
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->enum('contact_status', ['new', 'contacted', 'in_progress', 'not_interested', 'converted', 'unreachable'])->default('new');
            $table->text('notes')->nullable();
            $table->timestamp('purchased_at');
            $table->timestamp('last_contacted_at')->nullable();

            $table->unique(['user_id', 'lead_id']);
            $table->index(['user_id', 'contact_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_leads');
    }
};
