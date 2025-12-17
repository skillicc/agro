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
        Schema::create('invest_loan_liabilities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['partner', 'shareholder', 'investment_day_term', 'loan', 'account_payable', 'account_receivable']);
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invest_loan_liabilities');
    }
};
