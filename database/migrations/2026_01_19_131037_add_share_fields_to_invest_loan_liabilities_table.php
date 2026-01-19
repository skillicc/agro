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
        Schema::table('invest_loan_liabilities', function (Blueprint $table) {
            $table->decimal('share_value', 15, 2)->default(0)->after('amount');
            $table->decimal('honorarium', 12, 2)->default(0)->after('share_value');
            $table->enum('honorarium_type', ['monthly', 'yearly'])->default('monthly')->after('honorarium');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invest_loan_liabilities', function (Blueprint $table) {
            $table->dropColumn(['share_value', 'honorarium', 'honorarium_type']);
        });
    }
};
