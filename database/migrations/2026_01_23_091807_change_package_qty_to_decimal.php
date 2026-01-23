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
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('package_qty', 15, 2)->default(1)->change();
            $table->decimal('unit_per_package', 15, 2)->default(1)->change();
            $table->decimal('quantity', 15, 2)->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->integer('package_qty')->default(1)->change();
            $table->integer('unit_per_package')->default(1)->change();
            $table->integer('quantity')->default(1)->change();
        });
    }
};
