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
            $table->string('partner_id', 50)->nullable()->unique()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invest_loan_liabilities', function (Blueprint $table) {
            $table->dropColumn('partner_id');
        });
    }
};
