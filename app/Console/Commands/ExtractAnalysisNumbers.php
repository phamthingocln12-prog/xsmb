<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Draw;
use App\Models\AnalysisExtraction;

class ExtractAnalysisNumbers extends Command
{
    // BƯỚC 1: Thêm 2 tham số {start_date?} và {end_date?}. Dấu ? nghĩa là có thể truyền hoặc không
    protected $signature = 'xsmb:extract-analysis {start_date?} {end_date?}';

    protected $description = 'Bóc tách bộ 12 và 45 cho giải ĐB và G1 (Hỗ trợ lọc theo khoảng ngày)';

    public function handle()
    {
        $this->info('Extracting analysis numbers from draws...');

        $draws = Draw::with('results')
            ->where('status', 'completed')
            ->orderBy('draw_date', 'asc')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($draws as $draw) {
            // Check if extraction already exists
            if (AnalysisExtraction::where('draw_id', $draw->id)->exists()) {
                $skipped++;
                continue;
            }

            $gdbResult = null;
            $g1Result = null;

            foreach ($draw->results as $result) {
                if ($result->prize_tier === 'GDB') {
                    $gdbResult = $result;
                }
                if ($result->prize_tier === 'G1') {
                    $g1Result = $result;
                }
            }

            if (!$gdbResult || !$g1Result) {
                $this->warn("Draw {$draw->draw_date}: Missing GDB or G1 result, skipping.");
                $skipped++;
                continue;
            }

            // Use original full_number directly (5 digits for GDB, 5 for G1)
            $gdbFull = $gdbResult->full_number;
            $g1Full = $g1Result->full_number;

            AnalysisExtraction::create([
                'draw_id'     => $draw->id,
                'draw_date'   => $draw->draw_date,
                'gdb_full'    => $gdbResult->full_number,
                'g1_full'     => $g1Result->full_number,
                'gdb_first2'  => substr($gdbFull, 0, 2),
                'gdb_last2'   => substr($gdbFull, -2),
                'g1_first2'   => substr($g1Full, 0, 2),
                'g1_last2'    => substr($g1Full, -2),
            ]);

            $created++;
        }

        $this->info("✅ Done! Created: {$created}, Skipped: {$skipped}");
        return Command::SUCCESS;
    }
}
