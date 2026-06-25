<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // ===== TƯỜNG THUẬT TRỰC TIẾP XSMB =====
        $schedule->command('xsmb:live-watch --force --timeout=35')
            ->everyMinute()
            ->between('18:08', '18:40')
            ->timezone('Asia/Ho_Chi_Minh')
            ->withoutOverlapping()
            ->runInBackground();

        // ===== CÀO NGAY SAU KHI QUAY XONG =====
        $schedule->command('crawl:today')
            ->dailyAt('18:35')
            ->timezone('Asia/Ho_Chi_Minh')
            ->withoutOverlapping();

        // ===== BACKFILL: BÙ DỮ LIỆU NGÀY THIẾU =====
        $schedule->command('crawl:today')
            ->everyFourHours()
            ->timezone('Asia/Ho_Chi_Minh')
            ->withoutOverlapping();

        // ===== FALLBACK: THỐNG KÊ + PHÂN TÍCH =====
        $schedule->command('stat:calculate')
            ->dailyAt('18:55')
            ->timezone('Asia/Ho_Chi_Minh');

        $schedule->command('xsmb:extract-analysis')
            ->dailyAt('18:55')
            ->timezone('Asia/Ho_Chi_Minh');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}