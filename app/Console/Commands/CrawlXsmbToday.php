<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Draw;
use App\Jobs\ProcessXsmbDraw;
use Carbon\Carbon;

class CrawlXsmbToday extends Command
{
    protected $signature = 'crawl:today';
    protected $description = 'Cào dữ liệu XSMB với cơ chế backfill tự động (crawl bù ngày thiếu)';

    public function handle()
    {
        // 1. Tìm ngày mới nhất đã có dữ liệu hoàn chỉnh trong DB
        $latestDraw = Draw::where('status', 'completed')
            ->orderBy('draw_date', 'desc')
            ->first();

        // 2. Xác định ngày bắt đầu crawl
        //    - Nếu đã có dữ liệu → bắt đầu từ ngày tiếp theo
        //    - Nếu chưa có gì → mặc định 7 ngày trước
        $startDate = $latestDraw
            ? Carbon::parse($latestDraw->draw_date)->addDay()
            : Carbon::today()->subDays(90);

        $endDate = Carbon::today();

        // 3. Nếu đã có đủ dữ liệu đến hôm nay, vẫn dispatch 1 lần
        //    để xử lý real-time (cập nhật kết quả đang quay)
        if ($startDate->greaterThan($endDate)) {
            ProcessXsmbDraw::dispatch($endDate->copy());
            $this->info("Dữ liệu đã đầy đủ. Dispatch cập nhật real-time cho hôm nay.");
            return;
        }

        // 4. Backfill: lặp từng ngày thiếu và dispatch job
        $count = 0;
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // Kiểm tra ngày này đã có dữ liệu hoàn chỉnh chưa
            $exists = Draw::where('draw_date', $date->format('Y-m-d'))
                ->where('status', 'completed')
                ->exists();

            if ($exists) {
                $this->line("✓ Ngày {$date->format('d-m-Y')} đã có dữ liệu, bỏ qua.");
                continue;
            }

            // Dispatch job crawl cho ngày này
            ProcessXsmbDraw::dispatch($date->copy());
            $this->info("→ Đã dispatch crawl cho ngày: {$date->format('d-m-Y')}");
            $count++;
        }

        if ($count > 0) {
            $this->info("Hoàn tất! Đã dispatch {$count} ngày vào hàng đợi.");
        } else {
            $this->info("Không có ngày nào cần crawl bù.");
        }
    }
}