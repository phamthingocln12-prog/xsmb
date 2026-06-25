<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Draw;
use App\Models\DrawResult;
use App\Models\AnalysisExtraction;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessXsmbDraw implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Ngày cần cào (Carbon instance)
     */
    protected $targetDate;

    /**
     * Số lần retry tối đa khi chưa đủ 27 giải
     */
    public $tries = 1; // Queue chỉ chạy 1 lần, retry nằm bên trong handle()

    /**
     * Timeout cho job (phút) — đủ cho 20 lần retry × 30s = 10 phút
     */
    public $timeout = 900; // 15 phút

    /**
     * Create a new job instance.
     */
    public function __construct(Carbon $date = null)
    {
        $this->targetDate = $date ?? Carbon::today('Asia/Ho_Chi_Minh');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $date = $this->targetDate;
        $dateString = $date->format('d-m-Y');
        $dbDate = $date->format('Y-m-d');

        Log::info("[CRAWL] Bắt đầu cào ngày: {$dateString}");

        // Tạo hoặc lấy kỳ quay
        $draw = Draw::firstOrCreate(
            ['draw_date' => $dbDate],
            ['status' => 'pending']
        );

        // ====================================================================
        // KHỐI 1: KIỂM TRA ĐÃ QUAY XONG VÀ TỰ CHỮA LÀNH DỮ LIỆU
        // ====================================================================
        if ($draw->status === 'completed') {
            // Kiểm tra xem bảng phân tích đã được cắt số chưa?
            $hasExtraction = AnalysisExtraction::where('draw_id', $draw->id)->exists();

            // NẾU CHƯA CÓ -> TỰ ĐỘNG CẮT BÙ NGAY LẬP TỨC
            if (!$hasExtraction) {
                $this->extractAnalysis($draw, $dateString);
            }

            Log::info("[CRAWL] Kỳ quay {$dateString} đã hoàn tất từ trước.");
            return;
        }

        // ====================================================================
        // KHỐI 2: TIẾN HÀNH CÀO DỮ LIỆU TỪ WEB (CÓ RETRY)
        // ====================================================================
        $client = new Client([
            'timeout' => 15.0,
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

        $url = "https://xoso.com.vn/xsmb-{$dateString}.html";
        $maxRetries = 20;        // Tối đa 20 lần thử
        $retryDelay = 30;        // Đợi 30 giây giữa mỗi lần
        $currentPrizeCount = 0;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                Log::info("[CRAWL] Lần thử #{$attempt} — URL: {$url}");

                $response = $client->request('GET', $url);
                $html = (string) $response->getBody();
                $crawler = new Crawler($html);

                $currentPrizeCount = 0;
                $newCount = 0;

                foreach ($prizes as $tier => $selector) {
                    $crawler->filter($selector)->each(function ($node) use ($draw, $tier, &$currentPrizeCount, &$newCount) {
                        $fullNumber = trim($node->text());

                        // Bỏ qua placeholder "..." hay text không phải số
                        if (!is_numeric($fullNumber)) return;

                        $currentPrizeCount++;

                        $exists = DrawResult::where('draw_id', $draw->id)
                            ->where('prize_tier', $tier)
                            ->where('full_number', $fullNumber)
                            ->exists();

                        if (!$exists) {
                            $newData = DrawResult::create([
                                'draw_id' => $draw->id,
                                'prize_tier' => $tier,
                                'full_number' => $fullNumber,
                                'loto_number' => substr($fullNumber, -2),
                            ]);
                            event(new \App\Events\DrawResultUpdated($newData));
                            $newCount++;
                            Log::info("[CRAWL] Số mới: [{$tier}] - {$fullNumber}");
                        }
                    });
                }

                // Đếm lại tổng số trong DB (bao gồm cả lần cào trước)
                $totalInDb = DrawResult::where('draw_id', $draw->id)->count();

                Log::info("[CRAWL] Lần #{$attempt}: Tìm thấy {$currentPrizeCount} giải trên web, +{$newCount} mới, DB có {$totalInDb}/27");

                // === ĐỦ 27 GIẢI → CHỐT SỔ ===
                if ($totalInDb >= 27) {
                    $draw->update(['status' => 'completed']);
                    Log::info("[CRAWL] ✅ Đã quay xong đủ 27 giải ngày {$dateString}!");

                    // Tính thống kê
                    try {
                        Artisan::call('stat:calculate');
                    } catch (\Exception $e) {
                        Log::error("[CRAWL] Lỗi stat:calculate: " . $e->getMessage());
                    }

                    // Cắt số phân tích
                    $this->extractAnalysis($draw, $dateString);

                    return; // XONG!
                }

                // Chưa đủ → cập nhật trạng thái
                if ($totalInDb > 0) {
                    $draw->update(['status' => 'updating']);
                }

                // Nếu là ngày cũ (không phải hôm nay) và web không có đủ dữ liệu → dừng
                $isToday = $date->isToday();
                if (!$isToday && $currentPrizeCount < 27 && $attempt >= 3) {
                    Log::warning("[CRAWL] Ngày {$dateString} không phải hôm nay và web chưa có đủ dữ liệu sau 3 lần thử. Dừng.");
                    break;
                }

                // Nếu là hôm nay, đợi rồi thử lại
                if ($isToday && $attempt < $maxRetries) {
                    Log::info("[CRAWL] Chưa đủ 27 giải ({$totalInDb}/27). Đợi {$retryDelay}s rồi thử lại...");
                    sleep($retryDelay);
                }

            } catch (\Exception $e) {
                Log::error("[CRAWL] Lỗi lần #{$attempt}: " . $e->getMessage());

                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                }
            }
        }

        // Nếu hết retry mà vẫn chưa đủ
        $finalCount = DrawResult::where('draw_id', $draw->id)->count();
        if ($finalCount > 0 && $finalCount < 27) {
            Log::warning("[CRAWL] Hết {$maxRetries} lần thử. Chỉ có {$finalCount}/27 giải cho ngày {$dateString}.");
        }
    }

    /**
     * Cắt số phân tích GDB + G1
     */
    protected function extractAnalysis(Draw $draw, string $dateString): void
    {
        $gdb = DrawResult::where('draw_id', $draw->id)->where('prize_tier', 'GDB')->first();
        $g1  = DrawResult::where('draw_id', $draw->id)->where('prize_tier', 'G1')->first();

        if ($gdb && $g1) {
            AnalysisExtraction::updateOrCreate(
                ['draw_id' => $draw->id],
                [
                    'draw_date'  => $draw->draw_date,
                    'gdb_full'   => $gdb->full_number,
                    'g1_full'    => $g1->full_number,
                    'gdb_first2' => substr($gdb->full_number, 0, 2),
                    'g1_first2'  => substr($g1->full_number, 0, 2),
                    'gdb_last2'  => substr($gdb->full_number, -2),
                    'g1_last2'   => substr($g1->full_number, -2),
                ]
            );
            Log::info("[CRAWL] ✅ Đã cắt số phân tích ĐB + G1 cho ngày {$dateString}!");
        }
    }
}