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
        Schema::table('invest_loan_liabilities', function (Blueprint $table) {
            $table->integer('invest_period')->nullable()->after('honorarium_type'); // Period in months (4, 6, 12, 18, 24)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invest_loan_liabilities', function (Blueprint $table) {
            $table->dropColumn('invest_period');
        });
    }
};
