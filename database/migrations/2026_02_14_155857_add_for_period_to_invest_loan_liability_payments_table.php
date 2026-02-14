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
        Schema::table('invest_loan_liability_payments', function (Blueprint $table) {
            $table->integer('for_period')->nullable()->after('for_year')->comment('For investors - period in months (4, 6, 12, 18, 24)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invest_loan_liability_payments', function (Blueprint $table) {
            $table->dropColumn('for_period');
        });
    }
};
