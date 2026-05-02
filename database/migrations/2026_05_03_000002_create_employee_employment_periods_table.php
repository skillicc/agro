<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_employment_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();
        $rows = DB::table('employees')
            ->whereNotNull('joining_date')
            ->select('id as employee_id', 'joining_date as start_date')
            ->get()
            ->map(fn ($row) => [
                'employee_id' => $row->employee_id,
                'start_date' => $row->start_date,
                'end_date' => null,
                'note' => 'Initial employment period migrated from joining date',
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if (!empty($rows)) {
            DB::table('employee_employment_periods')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_employment_periods');
    }
};
