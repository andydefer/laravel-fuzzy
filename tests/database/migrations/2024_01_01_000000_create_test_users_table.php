<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the users table for the application.
 *
 * This migration defines the core user data structure including authentication
 * and authorization information.
 */
return new class() extends Migration {
    /**
     * Run the migration.
     *
     * Creates the users table with basic user information columns.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string(column: 'name');
            $table->string(column: 'email')->unique();
            $table->string(column: 'type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migration.
     *
     * Drops the users table if it exists.
     */
    public function down(): void
    {
        Schema::dropIfExists(table: 'users');
    }
};
