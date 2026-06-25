<?php

namespace App\Services;

use App\Models\AnalysisExtraction;
use Carbon\Carbon;

class AnalysisStatisticsService
{
    /**
     * Calculate frequency of each extracted number over a date range
     */
    public function getNumberFrequency(?string $fromDate = null, ?string $toDate = null): array
    {
        $query = AnalysisExtraction::query();
        if ($fromDate) $query->where('draw_date', '>=', $fromDate);
        if ($toDate) $query->where('draw_date', '<=', $toDate);

        $extractions = $query->orderBy('draw_date', 'desc')->get();

        $freq = array_fill(0, 100, 0);
        foreach ($extractions as $ext) {
            foreach ($ext->getAnalysisNumbers() as $num) {
                $freq[(int)$num]++;
            }
        }

        // Convert to keyed array
        $result = [];
        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            $result[$str] = $freq[$i];
        }

        return $result;
    }

    /**
     * Calculate streak (gan days) — how many consecutive days since a number last appeared
     */
    public function getStreaks(?string $referenceDate = null): array
    {
        $refDate = $referenceDate ? Carbon::parse($referenceDate) : Carbon::today();

        $extractions = AnalysisExtraction::where('draw_date', '<=', $refDate->format('Y-m-d'))
            ->orderBy('draw_date', 'desc')
            ->limit(100)
            ->get();

        $lastSeen = [];
        foreach ($extractions as $ext) {
            foreach ($ext->getAnalysisNumbers() as $num) {
                if (!isset($lastSeen[$num])) {
                    $lastSeen[$num] = $ext->draw_date;
                }
            }
        }

        $streaks = [];
        for ($i = 0; $i < 100; $i++) {
            $str = str_pad($i, 2, '0', STR_PAD_LEFT);
            if (isset($lastSeen[$str])) {
                $days = $refDate->diffInDays(Carbon::parse($lastSeen[$str]));
                $streaks[$str] = (int) $days;
            } else {
                $streaks[$str] = -1; // never appeared
            }
        }

        return $streaks;
    }

    /**
     * Distribution of extracted numbers across partition sets
     */
    public function getSetDistribution(array $partitionSets, ?string $fromDate = null, ?string $toDate = null): array
    {
        $freq = $this->getNumberFrequency($fromDate, $toDate);

        $distribution = [];
        foreach ($partitionSets as $label => $numbers) {
            $total = 0;
            $details = [];
            foreach ($numbers as $num) {
                $count = $freq[$num] ?? 0;
                $total += $count;
                $details[$num] = $count;
            }
            $distribution[$label] = [
                'total' => $total,
                'numbers' => $details,
            ];
        }

        return $distribution;
    }

    /**
     * Get the most frequently hit set for each draw in history
     */
    public function getSetHitHistory(array $partitionSets, int $days = 30, ?string $referenceDate = null): array
    {
        $refDate = $referenceDate ? Carbon::parse($referenceDate) : Carbon::today();
        $fromDate = $refDate->copy()->subDays($days)->format('Y-m-d');

        $extractions = AnalysisExtraction::where('draw_date', '>=', $fromDate)
            ->where('draw_date', '<=', $refDate->format('Y-m-d'))
            ->orderBy('draw_date', 'desc')
            ->get();

        // Build reverse lookup: number → set label
        $numberToSet = [];
        foreach ($partitionSets as $label => $numbers) {
            foreach ($numbers as $num) {
                $numberToSet[$num] = $label;
            }
        }

        $history = [];
        $setWins = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];

        foreach ($extractions as $ext) {
            $hits = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
            foreach ($ext->getAnalysisNumbers() as $num) {
                $set = $numberToSet[$num] ?? null;
                if ($set) {
                    $hits[$set][] = $num;
                }
            }

            // Find which set got the most hits
            $maxHits = 0;
            $winningSet = 'A';
            foreach ($hits as $label => $nums) {
                if (count($nums) > $maxHits) {
                    $maxHits = count($nums);
                    $winningSet = $label;
                }
            }

            $setWins[$winningSet]++;

            $history[] = [
                'date'        => $ext->draw_date instanceof Carbon ? $ext->draw_date->format('Y-m-d') : $ext->draw_date,
                'numbers'     => $ext->getAnalysisNumbers(),
                'hits'        => $hits,
                'winning_set' => $winningSet,
            ];
        }

        return [
            'history'  => $history,
            'set_wins' => $setWins,
        ];
    }

    /**
     * Get comprehensive stats for the dashboard
     */
    public function getDashboardStats(int $days = 30, ?string $referenceDate = null): array
    {
        $refDate = $referenceDate ?: Carbon::today()->format('Y-m-d');
        $fromDate = Carbon::parse($refDate)->subDays($days)->format('Y-m-d');

        return [
            'frequency' => $this->getNumberFrequency($fromDate, $refDate),
            'streaks'   => $this->getStreaks($refDate),
        ];
    }
}
