<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('land_cultivations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('land_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('crop_name');
            $table->date('opening_date');
            $table->date('expected_closing_date')->nullable();
            $table->date('closing_date')->nullable();
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['land_id', 'status']);
            $table->index(['project_id', 'opening_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('land_cultivations');
    }
};
