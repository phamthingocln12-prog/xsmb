<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Draw;
use App\Models\DrawResult;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class CrawlXsmbHistory extends Command
{
    protected $signature = 'crawl:xsmb {start_date} {end_date}';
    protected $description = 'Crawl dữ liệu XSMB siêu tốc';

    public function handle()
    {
        $startDate = Carbon::parse($this->argument('start_date'));
        $endDate = Carbon::parse($this->argument('end_date'));
        $period = CarbonPeriod::create($startDate, $endDate);

        $client = new Client([
            'timeout' => 15.0, // Giảm timeout xuống để không phải chờ lâu nếu mạng kẹt
            'verify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ]
        ]);

        $prizes = [
            'GDB' => '.special-prize', 'G1' => '.prize1', 'G2' => '.prize2',
            'G3' => '.prize3', 'G4' => '.prize4', 'G5' => '.prize5',
            'G6' => '.prize6', 'G7' => '.prize7',
        ];

        foreach ($period as $date) {
            $dateString = $date->format('d-m-Y');
            $success = false;
            $attempt = 1;

            while (!$success) {
                if ($attempt == 1) {
                    $this->info("Đang quét siêu tốc ngày: {$dateString}...");
                }

                try {
                    $url = "https://xoso.com.vn/xsmb-{$dateString}.html"; 
                    $response = $client->request('GET', $url);  
                    $html = (string) $response->getBody();
                    
                    $draw = Draw::firstOrCreate(
                        ['draw_date' => $date->format('Y-m-d')],
                        ['status' => 'completed']
                    );

                    // --- BẮT ĐẦU ĐOẠN CODE THÔNG MINH ---
                    $countResults = $draw->results()->count();

                    if ($countResults >= 27) {
                        // Đảm bảo status = completed dù draw đã tồn tại từ trước
                        if ($draw->status !== 'completed') {
                            $draw->update(['status' => 'completed']);
                            $this->line("-> Ngày {$dateString} đã đủ 27 giải, đã fix status → completed.");
                        } else {
                            $this->line("-> Ngày {$dateString} đã đủ 27 giải, bỏ qua.");
                        }
                        $success = true;
                        continue 2; // Bỏ qua toàn bộ phần dưới, nhảy sang ngày tiếp theo
                    } elseif ($countResults > 0 && $countResults < 27) {
                        $this->warn("-> Ngày {$dateString} bị thiếu số ({$countResults}/27). Đang xóa để cào lại...");
                        $draw->results()->delete(); // Xóa đi để vòng lặp bên dưới insert lại bản Full
                    }
                    // --- KẾT THÚC ĐOẠN CODE THÔNG MINH ---

                    $crawler = new Crawler($html);
                    $resultsToInsert = [];
                    $now = now();

                    foreach ($prizes as $tier => $selector) {
                        $crawler->filter($selector)->each(function (Crawler $node) use (&$resultsToInsert, $draw, $tier, $now) {
                            $fullNumber = trim($node->text());
                            if (is_numeric($fullNumber)) {
                                $resultsToInsert[] = [
                                    'draw_id' => $draw->id,
                                    'prize_tier' => $tier,
                                    'full_number' => $fullNumber,
                                    'loto_number' => substr($fullNumber, -2),
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                            }
                        });
                    }

                    if (!empty($resultsToInsert)) {
                        DrawResult::insert($resultsToInsert);
                        $this->info("=> Đã lưu: {$dateString}");
                    }

                    // Luôn cập nhật status = completed sau khi cào xong
                    if ($draw->status !== 'completed') {
                        $draw->update(['status' => 'completed']);
                    }

                    $success = true;
                    // Chỉ nghỉ 0.2 giây thay vì 2-4 giây như cũ để tăng tốc độ tối đa
                    usleep(200000); 

                } catch (\Exception $e) {
                    $this->error("Lỗi: " . $e->getMessage());
                    $attempt++;
                    sleep(2); // Lỗi mới phải nghỉ để tránh kẹt
                }
            }
        }

        $this->info("Hoàn tất Crawl dữ liệu cực nhanh!");
    }

    public function dongBoDuLieuRaExcel()
    {
        $this->info("Đang tạo file Excel Dashboard tĩnh...");
        try {
            $service = new \App\Services\ExcelExportService();
            $path = $service->generateOfflineDashboard();
            $this->info("Đã tạo thành công file Excel: " . $path);
        } catch (\Exception $e) {
            $this->error('Lỗi khi lưu file Excel: ' . $e->getMessage());
        }
    }
}