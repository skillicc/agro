<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('expense_histories')) {
            Schema::create('expense_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
                $table->string('action', 20);
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('changes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_histories');
    }
};
