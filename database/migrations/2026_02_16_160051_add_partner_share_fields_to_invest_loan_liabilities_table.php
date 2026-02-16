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
            $table->text('address')->nullable()->after('contact_person');
            $table->integer('number_of_shares')->nullable()->after('amount');
            $table->decimal('face_value_per_share', 15, 2)->nullable()->after('number_of_shares');
            $table->decimal('premium_value_per_share', 15, 2)->default(0)->after('face_value_per_share');
            $table->date('withdraw_date')->nullable()->after('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invest_loan_liabilities', function (Blueprint $table) {
            $table->dropColumn(['address', 'number_of_shares', 'face_value_per_share', 'premium_value_per_share', 'withdraw_date']);
        });
    }
};
