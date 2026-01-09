<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuzzy_index', function (Blueprint $table) {
            $table->id();
            $table->string('indexable_type');
            $table->string('indexable_id');
            $table->string('field');
            $table->text('original_value');
            $table->text('normalized_value');
            $table->json('words');
            $table->float('weight')->default(0.5);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['indexable_type', 'indexable_id']);
            $table->index('field');
            $table->index('normalized_value');
            $table->index('weight');
            $table->index('created_at');

            $table->unique(['indexable_type', 'indexable_id', 'field']);
        });

        // Index PostgreSQL uniquement
        if (DB::getDriverName() === 'pgsql') {
            Schema::table('fuzzy_index', function (Blueprint $table) {
                $table->rawIndex('(json_array_elements(words))', 'fuzzy_index_words_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fuzzy_index');
    }
};
