<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_return_batches')) {
            Schema::create('product_return_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_return_id')->constrained()->cascadeOnDelete();
                $table->foreignId('stock_batch_id')->nullable()->constrained('stock_batches')->nullOnDelete();
                $table->decimal('quantity', 15, 2);
                $table->decimal('cost_price', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_return_batches');
    }
};
