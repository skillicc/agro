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
            $table->decimal('current_rate_per_share', 15, 2)->nullable()->after('premium_value_per_share');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invest_loan_liabilities', function (Blueprint $table) {
            $table->dropColumn('current_rate_per_share');
        });
    }
};
