<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_extractions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw_id')->constrained('draws')->onDelete('cascade');
            $table->date('draw_date')->index();
            $table->string('gdb_full', 10);       // GDB full number
            $table->string('g1_full', 10);         // G1 full number
            $table->char('gdb_first2', 2);         // GDB first 2 digits
            $table->char('gdb_last2', 2);          // GDB last 2 digits
            $table->char('g1_first2', 2);           // G1 first 2 digits
            $table->char('g1_last2', 2);            // G1 last 2 digits
            $table->timestamps();

            $table->unique('draw_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_extractions');
    }
};
