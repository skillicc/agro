<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the type enum in projects table to add Administration and Central
        Schema::table('projects', function (Blueprint $table) {
            // Drop and recreate the enum column with new values
            $table->dropColumn('type');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->enum('type', ['field', 'nursery', 'shop', 'administration', 'central'])
                ->default('field')
                ->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->enum('type', ['field', 'nursery', 'shop'])
                ->default('field')
                ->after('name');
        });
    }
};
