<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Add note field
            $table->string('note')->nullable()->after('status');
        });

        // Update status enum to include leave and sick_leave
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('present', 'absent', 'leave', 'sick_leave') DEFAULT 'present'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert status enum back to original
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('present', 'absent') DEFAULT 'present'");

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
