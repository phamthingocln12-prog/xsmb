<?php

namespace App\Services;

use App\Models\NumberUniverse;

class NumberClassificationService
{
    /**
     * Generate classification data for a single number (00-99)
     */
    public function classify(int $num): array
    {
        $str = str_pad($num, 2, '0', STR_PAD_LEFT);
        $head = (int) substr($str, 0, 1);
        $tail = (int) substr($str, 1, 1);

        return [
            'number'    => $str,
            'head'      => $head,
            'tail'      => $tail,
            'is_even'   => $num % 2 === 0,
            'is_big'    => $num >= 50,
            'digit_sum' => $head + $tail,
            'mod2'      => $num % 2,
            'mod3'      => $num % 3,
            'mod4'      => $num % 4,
        ];
    }

    /**
     * Generate all 100 numbers with their classifications
     */
    public function generateAll(): array
    {
        $numbers = [];
        for ($i = 0; $i < 100; $i++) {
            $numbers[] = $this->classify($i);
        }
        return $numbers;
    }

    /**
     * Seed the number_universe table
     */
    public function seedDatabase(): int
    {
        $count = 0;
        for ($i = 0; $i < 100; $i++) {
            $data = $this->classify($i);
            NumberUniverse::updateOrCreate(
                ['number' => $data['number']],
                $data
            );
            $count++;
        }
        return $count;
    }

    /**
     * Get all numbers as a keyed collection
     */
    public function getNumbersMap(): array
    {
        $map = [];
        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            $map[$str] = $this->classify($i);
        }
        return $map;
    }
}
