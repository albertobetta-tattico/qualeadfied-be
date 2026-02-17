<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId('user_package_id')->nullable()->constrained('user_packages')->onDelete('set null');
            $table->enum('mode', ['exclusive', 'shared', 'free']);
            $table->integer('share_slot')->nullable();
            $table->decimal('price_paid', 10, 2)->default(0);
            $table->timestamp('sold_at');

            $table->unique(['lead_id', 'user_id']);
            $table->index(['user_id', 'sold_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_sales');
    }
};
