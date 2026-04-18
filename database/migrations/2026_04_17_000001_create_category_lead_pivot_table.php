<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create pivot table
        Schema::create('category_lead', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->timestamp('assigned_at')->useCurrent();

            $table->unique(['lead_id', 'category_id']);
            $table->index('category_id');
        });

        // 2. Migrate existing data
        DB::statement('
            INSERT INTO category_lead (lead_id, category_id, assigned_at)
            SELECT id, category_id, COALESCE(created_at, NOW())
            FROM leads
            WHERE category_id IS NOT NULL
        ');

        // 3. Drop foreign key (if exists), indexes, and column
        $this->dropForeignIfExists('leads', 'leads_category_id_foreign');
        $this->dropIndexIfExists('leads', 'leads_category_id_status_generated_at_index');
        $this->dropIndexIfExists('leads', 'leads_category_id_province_id_status_index');

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('category_id');
        });

        // 4. Add new indexes for common queries
        Schema::table('leads', function (Blueprint $table) {
            $table->index(['status', 'generated_at']);
            $table->index(['province_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['status', 'generated_at']);
            $table->dropIndex(['province_id', 'status']);
            $table->foreignId('category_id')->nullable()->after('id')->constrained('categories')->onDelete('cascade');
        });

        DB::statement('
            UPDATE leads l
            INNER JOIN (
                SELECT lead_id, MIN(category_id) as category_id
                FROM category_lead
                GROUP BY lead_id
            ) cl ON l.id = cl.lead_id
            SET l.category_id = cl.category_id
        ');

        Schema::table('leads', function (Blueprint $table) {
            $table->index(['category_id', 'status', 'generated_at']);
            $table->index(['category_id', 'province_id', 'status']);
        });

        Schema::dropIfExists('category_lead');
    }

    private function dropForeignIfExists(string $table, string $key): void
    {
        $fkeys = collect(DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table]))->pluck('CONSTRAINT_NAME');

        if ($fkeys->contains($key)) {
            Schema::table($table, fn (Blueprint $t) => $t->dropForeign($key));
        }
    }

    private function dropIndexIfExists(string $table, string $key): void
    {
        $indexes = collect(DB::select('SHOW INDEX FROM ' . $table))
            ->pluck('Key_name')->unique();

        if ($indexes->contains($key)) {
            Schema::table($table, fn (Blueprint $t) => $t->dropIndex($key));
        }
    }
};
