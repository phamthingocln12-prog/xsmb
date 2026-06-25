<?php

namespace App\Services;

class PatternAnalyzerService
{
    /**
     * Analyze a single number's properties
     */
    public function analyzeNumber(string $num): array
    {
        $n = (int) $num;
        $head = (int) substr($num, 0, 1);
        $tail = (int) substr($num, 1, 1);

        return [
            'number'    => $num,
            'even_odd'  => $n % 2 === 0 ? 'chẵn' : 'lẻ',
            'big_small' => $n >= 50 ? 'lớn' : 'nhỏ',
            'head'      => $head,
            'tail'      => $tail,
            'digit_sum' => $head + $tail,
        ];
    }

    /**
     * Analyze a pair of numbers and return their combined pattern
     */
    public function analyzePair(string $num1, string $num2): array
    {
        $a = $this->analyzeNumber($num1);
        $b = $this->analyzeNumber($num2);

        return [
            'num1' => $a,
            'num2' => $b,
            'pattern' => [
                'even_odd'  => $a['even_odd'] . '-' . $b['even_odd'],
                'big_small' => $a['big_small'] . '-' . $b['big_small'],
                'head'      => $a['head'] . '-' . $b['head'],
                'tail'      => $a['tail'] . '-' . $b['tail'],
                'sum'       => $a['digit_sum'] . '-' . $b['digit_sum'],
                'diff'      => abs((int)$num1 - (int)$num2),
            ],
        ];
    }

    /**
     * Analyze a full draw extraction (4 numbers: GDB first2, GDB last2, G1 first2, G1 last2)
     */
    public function analyzeExtraction(array $extraction): array
    {
        $gdbFirst2 = $extraction['gdb_first2'];
        $gdbLast2  = $extraction['gdb_last2'];
        $g1First2  = $extraction['g1_first2'];
        $g1Last2   = $extraction['g1_last2'];

        return [
            'gdb_pair'     => $this->analyzePair($gdbFirst2, $gdbLast2),
            'g1_pair'      => $this->analyzePair($g1First2, $g1Last2),
            'cross_pair_1' => $this->analyzePair($gdbFirst2, $g1First2),  // first2 vs first2
            'cross_pair_2' => $this->analyzePair($gdbLast2, $g1Last2),    // last2 vs last2
            'all_numbers'  => [$gdbFirst2, $gdbLast2, $g1First2, $g1Last2],
            'all_analysis' => [
                $this->analyzeNumber($gdbFirst2),
                $this->analyzeNumber($gdbLast2),
                $this->analyzeNumber($g1First2),
                $this->analyzeNumber($g1Last2),
            ],
        ];
    }

    /**
     * Analyze patterns across multiple extractions (historical)
     */
    public function analyzeHistory(array $extractions): array
    {
        $evenOddCount = [];
        $bigSmallCount = [];
        $headCount = array_fill(0, 10, 0);
        $tailCount = array_fill(0, 10, 0);
        $sumCount = [];

        foreach ($extractions as $ext) {
            $numbers = [$ext['gdb_first2'], $ext['gdb_last2'], $ext['g1_first2'], $ext['g1_last2']];

            foreach ($numbers as $num) {
                $info = $this->analyzeNumber($num);
                $evenOddCount[$info['even_odd']] = ($evenOddCount[$info['even_odd']] ?? 0) + 1;
                $bigSmallCount[$info['big_small']] = ($bigSmallCount[$info['big_small']] ?? 0) + 1;
                $headCount[$info['head']]++;
                $tailCount[$info['tail']]++;
                $sumCount[$info['digit_sum']] = ($sumCount[$info['digit_sum']] ?? 0) + 1;
            }
        }

        ksort($sumCount);

        return [
            'even_odd'  => $evenOddCount,
            'big_small' => $bigSmallCount,
            'head'      => $headCount,
            'tail'      => $tailCount,
            'digit_sum' => $sumCount,
            'total_numbers' => count($extractions) * 4,
        ];
    }
}
