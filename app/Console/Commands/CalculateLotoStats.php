<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Draw;
use App\Models\DrawResult;
use App\Models\LotoStatistic;
use Illuminate\Support\Facades\DB;

class CalculateLotoStats extends Command
{
    protected $signature = 'stat:calculate';
    protected $description = 'Tính toán thống kê tần suất và lô gan cho 100 số (00-99)';

    public function handle()
    {
        $this->info("Đang lấy danh sách các kỳ quay...");
        
        // Lấy tất cả các ngày quay thưởng đã hoàn tất, sắp xếp tăng dần
        $allDraws = Draw::where('status', 'completed')
                        ->orderBy('draw_date', 'asc')
                        ->pluck('draw_date')
                        ->toArray();

        if (empty($allDraws)) {
            $this->error("Chưa có dữ liệu kỳ quay nào trong database!");
            return;
        }

        // Tạo mảng map để tra cứu index của ngày quay cực nhanh (O(1))
        $drawIndexes = [];
        foreach ($allDraws as $index => $date) {
            $drawIndexes[$date] = $index;
        }
        $latestDrawIndex = count($allDraws) - 1;

        $this->info("Bắt đầu tính toán cho 100 số...");

        // Duyệt từ số 00 đến 99
        for ($i = 0; $i <= 99; $i++) {
            $loto = str_pad($i, 2, '0', STR_PAD_LEFT);

            // Lấy tất cả các ngày mà loto này xuất hiện (lọc trùng nếu 1 ngày về nhiều nháy)
            $appearedDates = DB::table('draw_results')
                ->join('draws', 'draw_results.draw_id', '=', 'draws.id')
                ->where('draw_results.loto_number', $loto)
                ->where('draws.status', 'completed')
                ->orderBy('draws.draw_date', 'asc')  
                ->pluck('draws.draw_date')
                ->unique()
                ->values()
                ->toArray();

            $totalAppearances = count($appearedDates);

            if ($totalAppearances == 0) {
                continue; // Bỏ qua nếu số chưa từng xuất hiện
            }

            $lastAppearedDate = end($appearedDates);
            $lastAppearedIndex = $drawIndexes[$lastAppearedDate];

            // 1. Tính số ngày gan hiện tại (Khoảng cách từ lần cuối xuất hiện đến kỳ quay mới nhất)
            $currentGanDays = $latestDrawIndex - $lastAppearedIndex;

            // 2. Tính Max Gan (Kỷ lục gan)
            $maxGanDays = 0;
            $previousIndex = null;

            foreach ($appearedDates as $date) {
                $currentIndex = $drawIndexes[$date];
                if ($previousIndex !== null) {
                    // Khoảng cách giữa 2 lần về liên tiếp
                    $gan = $currentIndex - $previousIndex - 1;
                    if ($gan > $maxGanDays) {
                        $maxGanDays = $gan;
                    }
                }
                $previousIndex = $currentIndex;
            }

            // So sánh thêm với chu kỳ gan hiện tại xem có phá kỷ lục không
            if ($currentGanDays > $maxGanDays) {
                $maxGanDays = $currentGanDays;
            }

            // Lưu vào DB
            LotoStatistic::updateOrCreate(
                ['loto_number' => $loto],
                [
                    'total_appearances' => $totalAppearances,
                    'last_appeared_date' => $lastAppearedDate,
                    'current_gan_days' => $currentGanDays,
                    'max_gan_days' => $maxGanDays
                ]
            );
        }

        $this->info("Tính toán hoàn tất! Toàn bộ bảng thống kê đã được cập nhật.");

        $this->info("Đang xuất hệ thống ra file Excel Dashboard tĩnh...");
        try {
            $service = new \App\Services\ExcelExportService();
            $service->generateOfflineDashboard();
            $this->info("✅ Đã cập nhật file Excel Offline.");
        } catch (\Exception $e) {
            $this->warn("⚠️ Không thể cập nhật file Excel: " . $e->getMessage());
        }
    }
}