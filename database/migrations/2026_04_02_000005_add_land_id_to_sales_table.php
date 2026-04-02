<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sales', 'land_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->foreignId('land_id')->nullable()->after('project_id')->constrained('lands')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales', 'land_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropForeign(['land_id']);
                $table->dropColumn('land_id');
            });
        }
    }
};
