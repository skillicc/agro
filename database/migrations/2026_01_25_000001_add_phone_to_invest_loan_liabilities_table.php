<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invest_loan_liabilities', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('name');
            $table->string('contact_person')->nullable()->after('phone'); // For loans from organizations
            $table->decimal('profit_rate', 5, 2)->default(0)->after('invest_period'); // Expected profit rate %
        });
    }

    public function down(): void
    {
        Schema::table('invest_loan_liabilities', function (Blueprint $table) {
            $table->dropColumn(['phone', 'contact_person', 'profit_rate']);
        });
    }
};
