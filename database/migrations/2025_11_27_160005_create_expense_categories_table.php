<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('project_type', ['field', 'nursery', 'shop', 'all'])->default('all');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default expense categories
        DB::table('expense_categories')->insert([
            // Field Project expenses
            ['name' => 'Labor Bill', 'project_type' => 'field', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Labor Tiffin', 'project_type' => 'field', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Seed/Chara', 'project_type' => 'field', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Fertilizer', 'project_type' => 'field', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pesticide', 'project_type' => 'field', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Irrigation', 'project_type' => 'field', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // Nursery expenses
            ['name' => 'Labor', 'project_type' => 'nursery', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Seed', 'project_type' => 'nursery', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cocopeat', 'project_type' => 'nursery', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tiffin', 'project_type' => 'nursery', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Electricity Bill', 'project_type' => 'nursery', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nursery Fertilizer', 'project_type' => 'nursery', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nursery Pesticide', 'project_type' => 'nursery', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // Common
            ['name' => 'Others', 'project_type' => 'all', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
