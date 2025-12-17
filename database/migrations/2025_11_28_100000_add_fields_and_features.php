<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add Bill No. to expenses
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('bill_no')->nullable()->after('expense_category_id');
        });

        // Add Challan No. to sales (rename invoice_no to challan_no)
        Schema::table('sales', function (Blueprint $table) {
            $table->renameColumn('invoice_no', 'challan_no');
        });

        // Add Invoice No. to assets
        Schema::table('assets', function (Blueprint $table) {
            $table->string('invoice_no')->nullable()->after('name');
            $table->decimal('depreciation_rate', 5, 2)->default(0)->after('value'); // Annual depreciation %
            $table->decimal('current_value', 15, 2)->nullable()->after('depreciation_rate');
        });

        // Update purchase_items for package calculations
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->string('size')->nullable()->after('product_id'); // Size/variant
            $table->integer('package_qty')->default(1)->after('quantity'); // Number of packages
            $table->integer('unit_per_package')->default(1)->after('package_qty'); // Units per package
            $table->decimal('package_price', 15, 2)->default(0)->after('unit_per_package'); // Price per package
        });

        // Add Courier & Transportation to expense categories
        DB::table('expense_categories')->insert([
            ['name' => 'Courier', 'project_type' => 'all', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Transportation', 'project_type' => 'all', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('bill_no');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->renameColumn('challan_no', 'invoice_no');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['invoice_no', 'depreciation_rate', 'current_value']);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['size', 'package_qty', 'unit_per_package', 'package_price']);
        });

        DB::table('expense_categories')->whereIn('name', ['Courier', 'Transportation'])->delete();
    }
};
