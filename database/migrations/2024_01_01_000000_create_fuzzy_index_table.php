<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration to create the fuzzy search index table.
     *
     * This table stores normalized search data for efficient fuzzy matching
     * across different Eloquent models and fields. It supports weighted
     * searches and database-optimized indexing strategies.
     *
     * @return void
     */
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

            $this->createBaseIndexes($table);
        });

        $this->createDatabaseSpecificIndexes();
    }

    /**
     * Create essential indexes for query optimization.
     *
     * @param Blueprint $table
     * @return void
     */
    private function createBaseIndexes(Blueprint $table): void
    {
        $table->index(['indexable_type', 'indexable_id']);
        $table->index('field');
        $table->index('weight');
        $table->index('created_at');
        $table->unique(['indexable_type', 'indexable_id', 'field']);
    }

    /**
     * Create database-specific indexes for optimal performance.
     *
     * Different database systems require different indexing strategies
     * for text and JSON fields.
     *
     * @return void
     */
    private function createDatabaseSpecificIndexes(): void
    {
        $this->createMySqlIndex();
        $this->createPostgreSqlIndex();
    }

    /**
     * Create MySQL-specific index for the normalized_value field.
     *
     * MySQL requires specifying a length when indexing text fields.
     * The length of 191 characters is chosen for compatibility with
     * older MySQL versions and utf8mb4 encoding.
     *
     * @return void
     */
    private function createMySqlIndex(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE fuzzy_index ADD INDEX fuzzy_index_normalized_value_index (normalized_value(191))');
    }

    /**
     * Create PostgreSQL-specific index for the words JSON array.
     *
     * PostgreSQL supports indexing JSON array elements for faster
     * lookups and query performance.
     *
     * @return void
     */
    private function createPostgreSqlIndex(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('fuzzy_index', function (Blueprint $table) {
            $table->rawIndex('(json_array_elements(words))', 'fuzzy_index_words_index');
        });
    }

    /**
     * Reverse the migration by dropping the fuzzy search index table.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fuzzy_index');
    }
};
