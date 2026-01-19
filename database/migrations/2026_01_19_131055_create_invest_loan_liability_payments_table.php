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
        Schema::create('invest_loan_liability_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invest_loan_liability_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['share_payment', 'profit_withdrawal', 'honorarium_payment']);
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->year('for_year')->nullable(); // For profit withdrawal - which year
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invest_loan_liability_payments');
    }
};
