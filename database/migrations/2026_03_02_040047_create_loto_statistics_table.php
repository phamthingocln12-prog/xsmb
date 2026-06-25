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
        Schema::create('loto_statistics', function (Blueprint $table) {
            $table->char('loto_number', 2)->primary(); // Khóa chính là số từ '00' đến '99'
            $table->integer('total_appearances')->default(0); // Tổng số lần về
            $table->date('last_appeared_date')->nullable(); // Lần cuối xuất hiện
            $table->integer('current_gan_days')->default(0); // Số ngày gan hiện tại
            $table->integer('max_gan_days')->default(0); // Kỷ lục gan cực đại
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loto_statistics');
    }
};
