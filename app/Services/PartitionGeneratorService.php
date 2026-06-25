<?php

namespace App\Services;

class PartitionGeneratorService
{
    private array $allNumbers;

    public function __construct()
    {
        $this->allNumbers = [];
        for ($i = 0; $i < 100; $i++) {
            $this->allNumbers[] = str_pad($i, 2, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Get all available strategy keys and names
     */
    public function getStrategyList(): array
    {
        return [
            'mod4'              => 'Chia theo Mod 4',
            'tn'                => 'Hệ TN (To/Nhỏ đầu-đuôi)',
            'cl'                => 'Hệ CL (Chẵn/Lẻ đầu-đuôi)',
            'tn_cl'             => 'Hệ TN.CL (đầu To/Nhỏ + đuôi Chẵn/Lẻ)',
            'cl_tn'             => 'Hệ CL.TN (đầu Chẵn/Lẻ + đuôi To/Nhỏ)',
            'numeric_ranges'    => 'Theo dải số (00-24, 25-49...)',
        ];
    }

    /**
     * Get partition for a specific strategy
     */
    public function getPartition(string $key): ?array
    {
        $method = 'strategy_' . $key;
        if (!method_exists($this, $method)) {
            return null;
        }

        $info = $this->getStrategyList();
        $sets = $this->$method();

        return [
            'key'         => $key,
            'name'        => $info[$key] ?? $key,
            'sets'        => $sets,
        ];
    }

    /**
     * Get all partitions
     */
    public function getAllPartitions(): array
    {
        $result = [];
        foreach (array_keys($this->getStrategyList()) as $key) {
            $result[$key] = $this->getPartition($key);
        }
        return $result;
    }

    // ═══════════════════════════════════════════════
    // STRATEGY METHODS — each returns ['A'=>[...], 'B'=>[...], 'C'=>[...], 'D'=>[...]]
    // ═══════════════════════════════════════════════

    /** 1. mod4 grouping: n%4 == 0,1,2,3 */
    private function strategy_mod4(): array
    {
        $sets = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        $labels = ['A', 'B', 'C', 'D'];
        for ($i = 0; $i < 100; $i++) {
            $sets[$labels[$i % 4]][] = str_pad($i, 2, '0', STR_PAD_LEFT);
        }
        return $sets;
    }

    /** 2a. Hệ TN — To/Nhỏ của đầu và đuôi (T=5-9, N=0-4) */
    private function strategy_tn(): array
    {
        $sets = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            $head = (int) substr($str, 0, 1);
            $tail = (int) substr($str, 1, 1);
            $hTo = $head >= 5;
            $tTo = $tail >= 5;
            if ($hTo && !$tTo) $sets['A'][] = $str;        // TN: đầu To, đuôi Nhỏ
            elseif (!$hTo && $tTo) $sets['B'][] = $str;    // NT: đầu Nhỏ, đuôi To
            elseif ($hTo && $tTo) $sets['C'][] = $str;     // TT: đầu To, đuôi To
            else $sets['D'][] = $str;                       // NN: đầu Nhỏ, đuôi Nhỏ
        }
        return $sets;
    }

    /** 2b. Hệ CL — Chẵn/Lẻ của đầu và đuôi (C=0,2,4,6,8 / L=1,3,5,7,9) */
    private function strategy_cl(): array
    {
        $sets = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            $head = (int) substr($str, 0, 1);
            $tail = (int) substr($str, 1, 1);
            $hChan = $head % 2 === 0;
            $tChan = $tail % 2 === 0;
            if ($hChan && !$tChan) $sets['A'][] = $str;    // CL: đầu Chẵn, đuôi Lẻ
            elseif (!$hChan && $tChan) $sets['B'][] = $str; // LC: đầu Lẻ, đuôi Chẵn
            elseif ($hChan && $tChan) $sets['C'][] = $str;  // CC: đầu Chẵn, đuôi Chẵn
            else $sets['D'][] = $str;                       // LL: đầu Lẻ, đuôi Lẻ
        }
        return $sets;
    }

    /** 2c. Hệ TN.CL — đầu To/Nhỏ + đuôi Chẵn/Lẻ */
    private function strategy_tn_cl(): array
    {
        $sets = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            $head = (int) substr($str, 0, 1);
            $tail = (int) substr($str, 1, 1);
            $hTo = $head >= 5;
            $tChan = $tail % 2 === 0;
            if ($hTo && $tChan) $sets['A'][] = $str;       // TC: đầu To, đuôi Chẵn
            elseif ($hTo && !$tChan) $sets['B'][] = $str;  // TL: đầu To, đuôi Lẻ
            elseif (!$hTo && $tChan) $sets['C'][] = $str;  // NC: đầu Nhỏ, đuôi Chẵn
            else $sets['D'][] = $str;                       // NL: đầu Nhỏ, đuôi Lẻ
        }
        return $sets;
    }

    /** 2d. Hệ CL.TN — đầu Chẵn/Lẻ + đuôi To/Nhỏ */
    private function strategy_cl_tn(): array
    {
        $sets = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            $head = (int) substr($str, 0, 1);
            $tail = (int) substr($str, 1, 1);
            $hChan = $head % 2 === 0;
            $tTo = $tail >= 5;
            if ($hChan && $tTo) $sets['A'][] = $str;       // CT: đầu Chẵn, đuôi To
            elseif (!$hChan && $tTo) $sets['B'][] = $str;  // LT: đầu Lẻ, đuôi To
            elseif ($hChan && !$tTo) $sets['C'][] = $str;  // CN: đầu Chẵn, đuôi Nhỏ
            else $sets['D'][] = $str;                       // LN: đầu Lẻ, đuôi Nhỏ
        }
        return $sets;
    }

    /** 6. numeric ranges 00-24, 25-49, 50-74, 75-99 */
    private function strategy_numeric_ranges(): array
    {
        $sets = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            if ($i < 25) $sets['A'][] = $str;
            elseif ($i < 50) $sets['B'][] = $str;
            elseif ($i < 75) $sets['C'][] = $str;
            else $sets['D'][] = $str;
        }
        return $sets;
    }

    /** 8. tail mod4 */
    private function strategy_tail_mod4(): array
    {
        $sets = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        $labels = ['A', 'B', 'C', 'D'];
        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            $tail = (int) substr($str, 1, 1);
            $sets[$labels[$tail % 4]][] = $str;
        }
        return $this->rebalance($sets);
    }

    /** 9. head even/odd + upper/lower half */
    private function strategy_head_even_odd(): array
    {
        $sets = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            $head = (int) substr($str, 0, 1);
            $headEven = $head % 2 === 0;
            $upper = $i >= 50;
            if ($headEven && !$upper) $sets['A'][] = $str;
            elseif ($headEven && $upper) $sets['B'][] = $str;
            elseif (!$headEven && !$upper) $sets['C'][] = $str;
            else $sets['D'][] = $str;
        }
        return $this->rebalance($sets);
    }

    /** 10. digit sum mod4 */
    private function strategy_sum_mod4(): array
    {
        $sets = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        $labels = ['A', 'B', 'C', 'D'];
        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            $sum = (int)substr($str, 0, 1) + (int)substr($str, 1, 1);
            $sets[$labels[$sum % 4]][] = $str;
        }
        return $this->rebalance($sets);
    }

    /** 11. diagonal pattern on 10x10 grid */
    private function strategy_diagonal(): array
    {
        $sets = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        $labels = ['A', 'B', 'C', 'D'];
        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            $head = (int) substr($str, 0, 1);
            $tail = (int) substr($str, 1, 1);
            $diag = ($head + $tail) % 4;
            $sets[$labels[$diag]][] = $str;
        }
        return $this->rebalance($sets);
    }

    /** 12. checkerboard pattern */
    private function strategy_checkerboard(): array
    {
        $sets = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            $head = (int) substr($str, 0, 1);
            $tail = (int) substr($str, 1, 1);
            $color = ($head + $tail) % 2; // 0=white, 1=black
            $half = $i < 50 ? 0 : 1;
            $idx = $color * 2 + $half;
            $labels = ['A', 'B', 'C', 'D'];
            $sets[$labels[$idx]][] = $str;
        }
        return $this->rebalance($sets);
    }

    /** 13. prime and composite classification */
    private function strategy_prime_composite(): array
    {
        $primes = [];
        $composites = [];
        $special = []; // 0, 1
        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            if ($i < 2) $special[] = $str;
            elseif ($this->isPrime($i)) $primes[] = $str;
            else $composites[] = $str;
        }
        // primes: 25, composites: 73, special: 2 → split composites + special into 3 groups
        $nonPrime = array_merge($special, $composites);
        $sets = [
            'A' => $primes,
            'B' => array_slice($nonPrime, 0, 25),
            'C' => array_slice($nonPrime, 25, 25),
            'D' => array_slice($nonPrime, 50),
        ];
        return $sets;
    }

    /** 14. mirror pairs (xy paired with yx) */
    private function strategy_mirror_pairs(): array
    {
        $sets = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        $labels = ['A', 'B', 'C', 'D'];
        $assigned = [];
        $groupIdx = 0;

        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            if (isset($assigned[$str])) continue;

            $mirror = substr($str, 1, 1) . substr($str, 0, 1);
            $label = $labels[$groupIdx % 4];

            $sets[$label][] = $str;
            $assigned[$str] = true;

            if ($mirror !== $str) {
                $sets[$label][] = $mirror;
                $assigned[$mirror] = true;
            }

            if (count($sets[$label]) >= 25) {
                $groupIdx++;
            }
        }
        return $this->rebalance($sets);
    }

    /** 15. snake pattern (zigzag rows on 10x10 grid) */
    private function strategy_snake_pattern(): array
    {
        $order = [];
        for ($row = 0; $row < 10; $row++) {
            if ($row % 2 === 0) {
                for ($col = 0; $col < 10; $col++) {
                    $order[] = str_pad($row * 10 + $col, 2, '0', STR_PAD_LEFT);
                }
            } else {
                for ($col = 9; $col >= 0; $col--) {
                    $order[] = str_pad($row * 10 + $col, 2, '0', STR_PAD_LEFT);
                }
            }
        }
        return [
            'A' => array_slice($order, 0, 25),
            'B' => array_slice($order, 25, 25),
            'C' => array_slice($order, 50, 25),
            'D' => array_slice($order, 75, 25),
        ];
    }

    /** 16. spiral pattern from center of 10x10 grid */
    private function strategy_spiral(): array
    {
        $grid = [];
        for ($r = 0; $r < 10; $r++) {
            for ($c = 0; $c < 10; $c++) {
                $grid[$r][$c] = str_pad($r * 10 + $c, 2, '0', STR_PAD_LEFT);
            }
        }

        $order = [];
        $top = 0; $bottom = 9; $left = 0; $right = 9;
        while ($top <= $bottom && $left <= $right) {
            for ($c = $left; $c <= $right; $c++) $order[] = $grid[$top][$c];
            $top++;
            for ($r = $top; $r <= $bottom; $r++) $order[] = $grid[$r][$right];
            $right--;
            if ($top <= $bottom) {
                for ($c = $right; $c >= $left; $c--) $order[] = $grid[$bottom][$c];
                $bottom--;
            }
            if ($left <= $right) {
                for ($r = $bottom; $r >= $top; $r--) $order[] = $grid[$r][$left];
                $left++;
            }
        }

        return [
            'A' => array_slice($order, 0, 25),
            'B' => array_slice($order, 25, 25),
            'C' => array_slice($order, 50, 25),
            'D' => array_slice($order, 75, 25),
        ];
    }

    /** 17. zigzag columns on 10x10 grid */
    private function strategy_zigzag(): array
    {
        $order = [];
        for ($col = 0; $col < 10; $col++) {
            if ($col % 2 === 0) {
                for ($row = 0; $row < 10; $row++) {
                    $order[] = str_pad($row * 10 + $col, 2, '0', STR_PAD_LEFT);
                }
            } else {
                for ($row = 9; $row >= 0; $row--) {
                    $order[] = str_pad($row * 10 + $col, 2, '0', STR_PAD_LEFT);
                }
            }
        }
        return [
            'A' => array_slice($order, 0, 25),
            'B' => array_slice($order, 25, 25),
            'C' => array_slice($order, 50, 25),
            'D' => array_slice($order, 75, 25),
        ];
    }

    /** 18. cross diagonal */
    private function strategy_cross_diagonal(): array
    {
        $sets = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        $labels = ['A', 'B', 'C', 'D'];
        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            $head = (int) substr($str, 0, 1);
            $tail = (int) substr($str, 1, 1);
            $diff = abs($head - $tail);
            $sets[$labels[$diff % 4]][] = $str;
        }
        return $this->rebalance($sets);
    }

    /** 19. head + tail combined grouping */
    private function strategy_head_tail_sum(): array
    {
        $sets = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        $labels = ['A', 'B', 'C', 'D'];
        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            $head = (int) substr($str, 0, 1);
            $tail = (int) substr($str, 1, 1);
            // Combine head quadrant and tail quadrant
            $hq = intdiv($head, 5); // 0 or 1
            $tq = intdiv($tail, 5); // 0 or 1
            $idx = $hq * 2 + $tq;
            $sets[$labels[$idx]][] = $str;
        }
        return $sets;
    }

    /** 20. quadrant on 10x10 grid */
    private function strategy_quadrant(): array
    {
        $sets = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            $head = (int) substr($str, 0, 1);
            $tail = (int) substr($str, 1, 1);
            if ($head < 5 && $tail < 5) $sets['A'][] = $str;
            elseif ($head < 5 && $tail >= 5) $sets['B'][] = $str;
            elseif ($head >= 5 && $tail < 5) $sets['C'][] = $str;
            else $sets['D'][] = $str;
        }
        return $sets;
    }

    /** 21. concentric rings on 10x10 grid */
    private function strategy_concentric(): array
    {
        $sets = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        $labels = ['A', 'B', 'C', 'D'];
        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            $head = (int) substr($str, 0, 1);
            $tail = (int) substr($str, 1, 1);
            // Distance from center (4.5, 4.5) using Chebyshev distance
            $dist = max(abs($head - 4.5), abs($tail - 4.5));
            if ($dist <= 1.5) $ring = 0;
            elseif ($dist <= 2.5) $ring = 1;
            elseif ($dist <= 3.5) $ring = 2;
            else $ring = 3;
            $sets[$labels[$ring]][] = $str;
        }
        return $this->rebalance($sets);
    }

    /** 22. fibonacci proximity */
    private function strategy_fibonacci(): array
    {
        $fibs = [0, 1, 1, 2, 3, 5, 8, 13, 21, 34, 55, 89];
        $nearFib = [];
        $farFib = [];

        for ($i = 0; $i < 100; $i++) {
            $minDist = 100;
            foreach ($fibs as $f) {
                $minDist = min($minDist, abs($i - $f));
            }
            if ($minDist <= 2) $nearFib[] = $i;
            else $farFib[] = $i;
        }

        $near = array_map(fn($n) => str_pad($n, 2, '0', STR_PAD_LEFT), $nearFib);
        $far = array_map(fn($n) => str_pad($n, 2, '0', STR_PAD_LEFT), $farFib);

        // Split each group into 2
        $nearHalf = (int) ceil(count($near) / 2);
        $farHalf = (int) ceil(count($far) / 2);

        $sets = [
            'A' => array_slice($near, 0, $nearHalf),
            'B' => array_slice($near, $nearHalf),
            'C' => array_slice($far, 0, $farHalf),
            'D' => array_slice($far, $farHalf),
        ];
        return $this->rebalance($sets);
    }

    // ═══════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════

    /**
     * Rebalance sets to ensure exactly 25 numbers each.
     * Moves numbers from overfull sets to underfull sets.
     */
    private function rebalance(array $sets): array
    {
        $all = [];
        foreach ($sets as $label => $nums) {
            foreach ($nums as $num) {
                $all[$num] = $label;
            }
        }

        // If any set has more than 25 or less than 25, redistribute
        $counts = array_map('count', $sets);
        $overflow = [];
        foreach ($sets as $label => $nums) {
            if (count($nums) > 25) {
                $excess = array_slice($nums, 25);
                $sets[$label] = array_slice($nums, 0, 25);
                foreach ($excess as $num) {
                    $overflow[] = $num;
                }
            }
        }

        // Fill underfull sets
        $labels = ['A', 'B', 'C', 'D'];
        foreach ($labels as $label) {
            while (count($sets[$label]) < 25 && !empty($overflow)) {
                $sets[$label][] = array_shift($overflow);
            }
        }

        // Sort each set
        foreach ($sets as $label => $nums) {
            sort($sets[$label]);
        }

        return $sets;
    }

    private function isPrime(int $n): bool
    {
        if ($n < 2) return false;
        if ($n === 2) return true;
        if ($n % 2 === 0) return false;
        for ($i = 3; $i * $i <= $n; $i += 2) {
            if ($n % $i === 0) return false;
        }
        return true;
    }
}
