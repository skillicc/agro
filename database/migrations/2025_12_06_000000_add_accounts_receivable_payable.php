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
        // Accounts Receivable - Track outstanding payments from customers
        Schema::create('accounts_receivable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total_amount', 15, 2); // Total sale amount
            $table->decimal('paid_amount', 15, 2)->default(0); // Amount customer paid
            $table->decimal('outstanding_amount', 15, 2); // Amount due
            $table->date('sale_date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Accounts Receivable Payments - Track customer payments
        Schema::create('accounts_receivable_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounts_receivable_id')->constrained('accounts_receivable')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'bank', 'check', 'mobile_banking'])->default('cash');
            $table->string('reference_no')->nullable(); // Check no, transaction id
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Accounts Payable - Track outstanding payments to suppliers
        Schema::create('accounts_payable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total_amount', 15, 2); // Total purchase amount
            $table->decimal('paid_amount', 15, 2)->default(0); // Amount we paid
            $table->decimal('outstanding_amount', 15, 2); // Amount due to pay
            $table->date('purchase_date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Accounts Payable Payments - Track payments to suppliers
        Schema::create('accounts_payable_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounts_payable_id')->constrained('accounts_payable')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'bank', 'check', 'mobile_banking'])->default('cash');
            $table->string('reference_no')->nullable(); // Check no, transaction id
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
        Schema::dropIfExists('accounts_payable_payments');
        Schema::dropIfExists('accounts_payable');
        Schema::dropIfExists('accounts_receivable_payments');
        Schema::dropIfExists('accounts_receivable');
    }
};
