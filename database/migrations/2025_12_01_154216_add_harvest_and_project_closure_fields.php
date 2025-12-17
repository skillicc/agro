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
        Schema::table('projects', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('location');
            $table->date('expected_harvest_date')->nullable()->after('start_date');
            $table->date('actual_harvest_date')->nullable()->after('expected_harvest_date');
            $table->integer('duration_months')->nullable()->after('actual_harvest_date'); // 3-6 months
            $table->enum('project_status', ['planning', 'active', 'harvesting', 'completed', 'closed'])->default('active')->after('duration_months');
            $table->boolean('is_closed')->default(false)->after('is_active');
            $table->date('closure_date')->nullable()->after('is_closed');
        });

        // Harvest records table
        Schema::create('harvests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->string('unit')->default('kg');
            $table->decimal('price_per_unit', 15, 2)->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->date('harvest_date');
            $table->enum('quality', ['excellent', 'good', 'average', 'poor'])->default('good');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Project closure/final report
        Schema::create('project_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->onDelete('cascade');
            $table->date('closure_date');
            
            // Income
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->decimal('total_harvest_value', 15, 2)->default(0);
            $table->decimal('other_income', 15, 2)->default(0);
            $table->decimal('total_income', 15, 2)->default(0);
            
            // Expenses
            $table->decimal('total_purchases', 15, 2)->default(0);
            $table->decimal('total_expenses', 15, 2)->default(0);
            $table->decimal('total_salaries', 15, 2)->default(0);
            $table->decimal('total_damages', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            
            // Profit/Loss
            $table->decimal('gross_profit', 15, 2)->default(0);
            $table->decimal('net_profit', 15, 2)->default(0);
            $table->decimal('profit_percentage', 5, 2)->default(0);
            
            // Partner/Shareholder distribution
            $table->json('partner_shares')->nullable(); // Array of partner_id => share_amount
            
            $table->text('summary')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_closures');
        Schema::dropIfExists('harvests');
        
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'start_date',
                'expected_harvest_date',
                'actual_harvest_date',
                'duration_months',
                'project_status',
                'is_closed',
                'closure_date',
            ]);
        });
    }
};
