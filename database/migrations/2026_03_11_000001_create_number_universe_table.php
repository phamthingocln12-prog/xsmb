<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_universe', function (Blueprint $table) {
            $table->char('number', 2)->primary();
            $table->tinyInteger('head');          // first digit 0-9
            $table->tinyInteger('tail');           // second digit 0-9
            $table->boolean('is_even');            // even/odd
            $table->boolean('is_big');             // big(5-9)/small(0-4) based on the number value (>=50)
            $table->tinyInteger('digit_sum');      // sum of two digits
            $table->tinyInteger('mod2');           // number % 2
            $table->tinyInteger('mod3');           // number % 3
            $table->tinyInteger('mod4');           // number % 4
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_universe');
    }
};
