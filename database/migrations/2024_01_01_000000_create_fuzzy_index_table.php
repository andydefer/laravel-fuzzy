<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the fuzzy search index table.
     *
     * This table stores normalized search data for fuzzy matching across
     * various models and fields. It supports weighted searches and
     * database-optimized indexing strategies.
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
     * Create base indexes for the fuzzy index table.
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
     * @return void
     */
    private function createDatabaseSpecificIndexes(): void
    {
        $this->createMySqlIndex();
        $this->createPostgreSqlIndex();
    }

    /**
     * Create MySQL-specific index for normalized_value.
     *
     * MySQL requires specifying length for text field indexes.
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
     * Create PostgreSQL-specific index for words JSON array.
     *
     * PostgreSQL can index JSON array elements for faster lookups.
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
     * Drop the fuzzy search index table.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fuzzy_index');
    }
};
