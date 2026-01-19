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
        // Modify enum to add loan_payment type
        DB::statement("ALTER TABLE invest_loan_liability_payments MODIFY COLUMN type ENUM('share_payment', 'profit_withdrawal', 'honorarium_payment', 'loan_payment') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE invest_loan_liability_payments MODIFY COLUMN type ENUM('share_payment', 'profit_withdrawal', 'honorarium_payment') NOT NULL");
    }
};
