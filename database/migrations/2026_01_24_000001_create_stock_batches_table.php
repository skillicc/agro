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
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_item_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('production_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('warehouse_id')->nullable()->constrained()->onDelete('set null');
            $table->string('batch_number')->nullable();
            $table->decimal('unit_price', 15, 2); // Buying/cost price
            $table->decimal('unit_mrp', 15, 2)->nullable(); // Selling price
            $table->decimal('initial_quantity', 15, 2);
            $table->decimal('remaining_quantity', 15, 2);
            $table->date('purchase_date');
            $table->date('expiry_date')->nullable();
            $table->enum('source', ['purchase', 'production', 'transfer', 'adjustment'])->default('purchase');
            $table->enum('status', ['active', 'depleted', 'expired'])->default('active');
            $table->text('note')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['product_id', 'status', 'remaining_quantity']);
            $table->index(['warehouse_id', 'product_id']);
            $table->index(['purchase_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
