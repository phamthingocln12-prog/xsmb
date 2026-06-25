<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\FrontendController;
use Carbon\Carbon;

class LiveScraperDaemon extends Command
{
    // Tên lệnh để gọi trong Terminal
    protected $signature = 'scraper:daemon';
    
    // Mô tả lệnh
    protected $description = 'Chạy ngầm cào dữ liệu xổ số mỗi 5 giây trong giờ quay thưởng';

    public function handle()
    {
        $this->info('🚀 Bắt đầu khởi động tiến trình cào dữ liệu ngầm...');

        while (true) {
            $now = Carbon::now();
            $timeStr = $now->format('H:i');

            // Khung giờ trực tiếp quay thưởng (18h14 đến 18h40)
            if ($timeStr >= '18:14' && $timeStr <= '18:40') {
                $this->info("[$timeStr] Đang tiến hành cào dữ liệu...");

                try {
                    // Gọi trực tiếp hàm crawlOnce() từ FrontendController mà không cần thông qua HTTP Request
                    $controller = new FrontendController();
                    $response = $controller->crawlOnce();
                    $data = $response->getData(); // Lấy dữ liệu JSON trả về

                    $this->info("👉 Kết quả: " . ($data->message ?? 'Thành công'));

                    // Nếu đã cào đủ 27 giải, cho tiến trình ngủ dài (1 tiếng) để đỡ tốn tài nguyên
                    if (isset($data->is_complete) && $data->is_complete) {
                        $this->info('✅ Đã đủ 27 giải. Tạm nghỉ chờ đến ngày mai.');
                        sleep(3600); 
                        continue;
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Lỗi: " . $e->getMessage());
                }

                // Nghỉ 5 giây rồi mới cào lại (Bảo vệ CPU và tránh bị block IP nguồn)
                sleep(5);
            } else {
                // Nếu ngoài khung giờ quay, tiến trình ngủ 1 phút rồi mới kiểm tra lại giờ
                $this->info("[$timeStr] Ngoài giờ quay thưởng. Tiến trình đang ngủ...");
                sleep(60);
            }
        }
    }
}