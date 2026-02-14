<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the enum to include 'investor'
        DB::statement("ALTER TABLE invest_loan_liabilities MODIFY COLUMN type ENUM('partner', 'shareholder', 'investor', 'investment_day_term', 'loan', 'account_payable', 'account_receivable') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'investor' from the enum
        DB::statement("ALTER TABLE invest_loan_liabilities MODIFY COLUMN type ENUM('partner', 'shareholder', 'investment_day_term', 'loan', 'account_payable', 'account_receivable') NOT NULL");
    }
};
