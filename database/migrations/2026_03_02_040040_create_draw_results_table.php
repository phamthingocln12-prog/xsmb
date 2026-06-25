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
        Schema::create('draw_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw_id')->constrained('draws')->onDelete('cascade');
            $table->string('prize_tier', 10); // VD: GDB, G1, G2...
            $table->string('full_number', 10); // Đầy đủ dãy số về
            $table->char('loto_number', 2)->index(); // Tách riêng 2 số cuối và đánh Index để thống kê lô gan, đầu đuôi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draw_results');
    }
};
