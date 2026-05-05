<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE cart_items MODIFY COLUMN purchase_mode ENUM('exclusive','shared','free') NOT NULL");

        Schema::table('cart_items', function (Blueprint $table) {
            $table->boolean('is_free_trial')->default(false)->after('price');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('is_free_trial')->default(false)->after('line_total');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('is_free_trial');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('is_free_trial');
        });

        DB::statement("ALTER TABLE cart_items MODIFY COLUMN purchase_mode ENUM('exclusive','shared') NOT NULL");
    }
};
