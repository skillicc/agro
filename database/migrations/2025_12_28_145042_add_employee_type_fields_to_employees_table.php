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
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('employee_type', ['regular', 'contractual'])->default('regular')->after('project_id');
            $table->decimal('daily_rate', 15, 2)->nullable()->after('salary_amount');
        });

        // Make project_id nullable for contractual employees
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['employee_type', 'daily_rate']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable(false)->change();
        });
    }
};
