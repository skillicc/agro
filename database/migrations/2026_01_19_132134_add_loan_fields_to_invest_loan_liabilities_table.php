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
            $table->enum('loan_type', ['with_profit', 'without_profit'])->nullable()->after('invest_period');
            $table->decimal('received_amount', 15, 2)->default(0)->after('loan_type'); // Total loan amount received
            $table->decimal('total_payable', 15, 2)->default(0)->after('received_amount'); // Total amount to pay (with profit)
            $table->date('receive_date')->nullable()->after('total_payable'); // Date when loan was received
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invest_loan_liabilities', function (Blueprint $table) {
            $table->dropColumn(['loan_type', 'received_amount', 'total_payable', 'receive_date']);
        });
    }
};
