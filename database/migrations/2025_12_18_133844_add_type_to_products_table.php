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
        Schema::table('products', function (Blueprint $table) {
            $table->enum('type', ['own_production', 'trading'])->default('trading')->after('category_id');
            $table->decimal('production_cost', 12, 2)->nullable()->after('buying_price');
        });

        // Make buying_price nullable for own production products
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('buying_price', 12, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['type', 'production_cost']);
        });
    }
};
