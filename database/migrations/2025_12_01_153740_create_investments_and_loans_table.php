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
        // Partners/Shareholders table
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['partner', 'shareholder'])->default('partner');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->decimal('share_percentage', 5, 2)->default(0); // For shareholders
            $table->decimal('total_investment', 15, 2)->default(0);
            $table->decimal('total_withdrawn', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Investment transactions
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->enum('type', ['investment', 'withdrawal'])->default('investment');
            $table->string('payment_method')->nullable(); // cash, bank, check
            $table->string('reference_no')->nullable(); // check no, transaction id
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Day term investments (daily deposit schemes)
        Schema::create('day_term_investments', function (Blueprint $table) {
            $table->id();
            $table->string('investor_name');
            $table->string('phone')->nullable();
            $table->decimal('daily_amount', 15, 2); // Daily deposit amount
            $table->integer('total_days'); // Total days in scheme
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_amount', 15, 2); // Total expected amount
            $table->decimal('paid_amount', 15, 2)->default(0); // Amount paid so far
            $table->decimal('return_amount', 15, 2)->nullable(); // Final return amount
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Day term payments (daily deposit records)
        Schema::create('day_term_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('day_term_investment_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->integer('day_number'); // Which day of the scheme
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Loans
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('lender_name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('interest_rate', 5, 2)->default(0); // Annual percentage
            $table->date('loan_date');
            $table->date('due_date')->nullable();
            $table->integer('tenure_months')->nullable(); // Loan tenure in months
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('outstanding_balance', 15, 2)->default(0);
            $table->enum('status', ['active', 'paid', 'overdue'])->default('active');
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Loan payments
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('payment_method')->nullable();
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('day_term_payments');
        Schema::dropIfExists('day_term_investments');
        Schema::dropIfExists('investments');
        Schema::dropIfExists('partners');
    }
};
