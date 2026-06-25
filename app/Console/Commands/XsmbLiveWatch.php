<?php

namespace App\Console\Commands;

use App\Events\XoSoUpdated;
use App\Models\AnalysisExtraction;
use App\Models\Draw;
use App\Models\DrawResult;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class XsmbLiveWatch extends Command
{
    protected $signature = 'xsmb:live-watch
        {--interval=5 : Khoảng thời gian poll (giây)}
        {--timeout=30 : Thời gian tối đa chờ (phút)}
        {--date= : Ngày cần tường thuật (dd-mm-yyyy), mặc định là hôm nay}
        {--force : Chạy ngay không cần chờ khung giờ 18:10}';

    protected $description = 'Tường thuật trực tiếp XSMB — poll tuần tự theo giải (Chờ lấy đủ từng giải mới sang giải tiếp theo)';

    /**
     * CSS selectors → prize tier (giống ProcessXsmbDraw)
     */
    protected array $selectors = [
        'GDB' => '.special-prize',
        'G1'  => '.prize1',
        'G2'  => '.prize2',
        'G3'  => '.prize3',
        'G4'  => '.prize4',
        'G5'  => '.prize5',
        'G6'  => '.prize6',
        'G7'  => '.prize7',
    ];

    /**
     * Map tier → key cho frontend (thứ tự broadcast)
     */
    protected array $tierToKey = [
        'G1'  => 'giai_1',
        'G2'  => 'giai_2',
        'G3'  => 'giai_3',
        'G4'  => 'giai_4',  
        'G5'  => 'giai_5',
        'G6'  => 'giai_6',
        'G7'  => 'giai_7',
        'GDB' => 'giai_dac_biet',
    ];

    public function handle(): int
    {
        $interval = max(3, (int) $this->option('interval'));
        $timeout  = max(5, (int) $this->option('timeout'));
        $force    = $this->option('force');
        $dateOpt  = $this->option('date');

        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║   🎰 XSMB LIVE WATCH — KỶ LUẬT TUẦN TỰ GIẢI    ║');
        $this->info('║   Nguồn: xoso.com.vn | Poll: ' . $interval . 's               ║');
        $this->info('╚══════════════════════════════════════════════════╝');
        $this->newLine();

        // === 1. Xác định ngày quay ===
        if ($dateOpt) {
            $targetDate = Carbon::createFromFormat('d-m-Y', $dateOpt)->startOfDay();
            $force = true; // Ngày cũ → luôn force
            $this->info("📅 Tường thuật kỳ quay ngày: {$targetDate->format('d-m-Y')}");
        } else {
            $targetDate = Carbon::today('Asia/Ho_Chi_Minh');
        }

        // === 2. Chờ khung giờ quay (18:10) nếu không --force ===
        if (!$force) {
            $now = Carbon::now('Asia/Ho_Chi_Minh');
            $drawStart = $now->copy()->setTime(18, 10, 0);
            $drawEnd   = $now->copy()->setTime(18, 10, 0)->addMinutes($timeout);

            if ($now->lt($drawStart)) {
                $wait = $now->diffInSeconds($drawStart);
                $this->info("⏳ Chờ đến 18:10 để bắt đầu... (còn {$wait}s)");
                $this->info("   Hoặc chạy với --force để bắt đầu ngay.");
                sleep($wait);
            } elseif ($now->gt($drawEnd)) {
                $this->warn("⚠️  Đã qua khung giờ quay. Dùng --force để chạy ngay.");
                return self::FAILURE;
            }
        }

        // === 3. Tạo / lấy kỳ quay ===
        $dbDate = $targetDate->format('Y-m-d');
        $draw = Draw::firstOrCreate(
            ['draw_date' => $dbDate],
            ['status' => 'pending']
        );

        if ($draw->status === 'completed') {
            $this->info("📋 Kỳ quay ngày {$targetDate->format('d-m-Y')} đã có trong DB — Phát lại tường thuật!");
            $this->replayFromDb($draw);
            return self::SUCCESS;
        }

        $draw->update(['status' => 'updating']);

        // Khởi tạo tracking (phòng trường hợp restart giữa chừng)
        $existingResults = DrawResult::where('draw_id', $draw->id)
            ->get()
            ->groupBy('prize_tier')
            ->map(fn($group) => $group->pluck('full_number')->toArray())
            ->toArray();

        $knownNumbers = []; // "tier:number" => true
        foreach ($existingResults as $tier => $numbers) {
            foreach ($numbers as $num) {
                $knownNumbers["{$tier}:{$num}"] = true;
            }
        }

        $totalFound = count($knownNumbers);

        // Broadcast _start signal
        broadcast(new XoSoUpdated('_start', 'live_draw_started', 0));
        $this->info('🎬 Bắt đầu tường thuật trực tiếp!');

        if ($totalFound > 0) {
            $this->info("📋 Đã có {$totalFound} giải từ trước, đồng bộ cho client...");
            $this->broadcastExistingResults($existingResults);
        }

        // === 4. ĐỊNH NGHĨA KỶ LUẬT THÉP ===
        // Quy định thứ tự cào nghiêm ngặt và số lượng kết quả phải có
        $prizeSequence = [
            'G1'  => 1,
            'G2'  => 2,
            'G3'  => 6,
            'G4'  => 4,
            'G5'  => 6,
            'G6'  => 3,
            'G7'  => 4,
            'GDB' => 1,
        ];

        // Khởi tạo Client (Tắt Verify để không bị lỗi SSL ở Localhost như lần trước)
        $client = new Client([
            'timeout' => 10.0,
            'verify'  => false, 
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);

        $url = "https://xoso.com.vn/xsmb-{$targetDate->format('d-m-Y')}.html";
        $maxTime = Carbon::now()->addMinutes($timeout);
        $pollCount = 0;

        $this->info("🔗 URL: {$url}");
        $this->info("⏱️  Timeout: {$timeout} phút | Interval: {$interval}s");
        $this->newLine();

        // === 5. DUYỆT TUẦN TỰ TỪNG GIẢI ===
        foreach ($prizeSequence as $tier => $expectedCount) {
            $fetchedCountForTier = count($existingResults[$tier] ?? []);

            // Nếu giải này đã đủ số (do restart lại command), bỏ qua và sang giải tiếp theo
            if ($fetchedCountForTier >= $expectedCount) {
                $this->info("✔️ Đã có đủ {$expectedCount}/{$expectedCount} số của giải {$tier}.");
                continue;
            }

            $this->info("⏳ Đang chờ lấy giải: {$tier} (cần {$expectedCount} số)...");

            // VÒNG LẶP BLOCKING: Bắt buộc kẹt ở đây cho đến khi lấy đủ số của giải $tier
            while ($fetchedCountForTier < $expectedCount && Carbon::now()->lt($maxTime)) {
                $pollCount++;
                try {
                    $response = $client->request('GET', $url);
                    $html = (string) $response->getBody();
                    $crawler = new Crawler($html);

                    // Chỉ quét đúng thẻ CSS của giải đang đợi
                    $selector = $this->selectors[$tier];
                    
                    $crawler->filter($selector)->each(function ($node) use (
                        $draw, $tier, &$knownNumbers, &$totalFound,
                        &$existingResults, &$fetchedCountForTier
                    ) {
                        $fullNumber = trim($node->text());
                        if (!is_numeric($fullNumber)) return; // Ô đang trống (-----) -> Bỏ qua
                        
                        $key = "{$tier}:{$fullNumber}";
                        if (isset($knownNumbers[$key])) return; // Đã cào số này rồi -> Bỏ qua

                        // TÌM THẤY SỐ MỚI ĐÚNG GIẢI ĐANG CHỜ!
                        $knownNumbers[$key] = true;
                        $totalFound++;
                        
                        // Tính toán vị trí Index cho bảng hiển thị giao diện
                        $currentIndex = count($existingResults[$tier] ?? []);
                        $existingResults[$tier][] = $fullNumber;
                        
                        $fetchedCountForTier++; // Tăng biến đếm của vòng lặp Block

                        // Lưu DB
                        DrawResult::create([
                            'draw_id'     => $draw->id,
                            'prize_tier'  => $tier,
                            'full_number' => $fullNumber,
                            'loto_number' => substr($fullNumber, -2),
                        ]);

                        // Bắn sự kiện Pusher WebSocket
                        $frontendKey = $this->tierToKey[$tier] ?? $tier;
                        broadcast(new XoSoUpdated($frontendKey, $fullNumber, $currentIndex));

                        Log::info("[LIVE] Giải mới: [{$tier}] #{$currentIndex} = {$fullNumber}");
                        $this->line("   🔴 [{$tier}] #{$currentIndex} = <fg=red;options=bold>{$fullNumber}</>");
                    });

                } catch (\Exception $e) {
                    $this->warn("   ⚠️  Lỗi poll #{$pollCount}: " . $e->getMessage());
                    Log::error("[LIVE] Poll error: " . $e->getMessage());
                }

                // NẾU CHƯA ĐỦ SỐ -> NGỦ $interval GIÂY RỒI QUAY LẠI TÌM TIẾP CÁI GIẢI NÀY
                if ($fetchedCountForTier < $expectedCount) {
                    $this->output->write("\r   ⏳ Poll #{$pollCount}: Chờ kết quả {$tier}... ({$fetchedCountForTier}/{$expectedCount})    ");
                    sleep($interval);
                }
            }

            $this->newLine();

            // Nếu thoát while do hết giờ timeout -> Phá vỡ luôn quy trình cào
            if (Carbon::now()->gte($maxTime)) {
                break;
            }

            $this->info(">> Đã lấy đủ giải {$tier}! Chuẩn bị sang giải tiếp theo.");
        }

        // === 6. KẾT THÚC CÀO ===
        if ($totalFound >= 27) {
            $this->newLine();
            $this->info('🏁 ĐÃ ĐỦ 27 GIẢI — CHỐT SỔ!');
            $this->finalizeDraw($draw);
            broadcast(new XoSoUpdated('_end', 'live_draw_completed', 0));
            $this->info('✨ Tường thuật hoàn tất! Dữ liệu đã lưu vào DB.');
            return self::SUCCESS;
        }

        // Xử lý khi Timeout (Trường quay gặp sự cố mất điện, nghỉ quá lâu...)
        $this->newLine();
        $this->warn("⏰ Hết thời gian chờ ({$timeout} phút). Đã thu được {$totalFound}/27 giải.");
        
        if ($totalFound > 0 && $totalFound < 27) {
            $draw->update(['status' => 'updating']);
            $this->info("Trạng thái kỳ quay: updating (chưa đủ giải).");
        }
        
        broadcast(new XoSoUpdated('_end', 'live_draw_timeout', 0));
        return self::SUCCESS;
    }

    /**
     * Broadcast lại các kết quả đã có (cho client join muộn hoặc restart).
     */
    protected function broadcastExistingResults(array $existingResults): void
    {
        foreach ($existingResults as $tier => $numbers) {
            $frontendKey = $this->tierToKey[$tier] ?? $tier;
            foreach ($numbers as $index => $number) {
                broadcast(new XoSoUpdated($frontendKey, $number, $index));
                usleep(50000); // 50ms delay để không flood
            }
        }
    }

    /**
     * Chốt sổ: cập nhật status, chạy thống kê, cắt số phân tích.
     */
    protected function finalizeDraw(Draw $draw): void
    {
        $draw->update(['status' => 'completed']);
        
        try {
            Artisan::call('stat:calculate');
            $this->info('📈 Đã tính thống kê.');
        } catch (\Exception $e) {
            $this->warn("⚠️  Lỗi tính thống kê: " . $e->getMessage());
        }
        
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
            $this->info('🔢 Đã cắt số phân tích ĐB + G1.');
        }
    }

    /**
     * Phát lại kỳ quay đã có trong DB qua WebSocket (mô phỏng tường thuật).
     */
    protected function replayFromDb(Draw $draw): void
    {
        $results = DrawResult::where('draw_id', $draw->id)->get();
        if ($results->isEmpty()) {
            $this->warn('Không có dữ liệu kết quả để phát lại.');
            return;
        }
        
        $tierOrder = ['G1', 'G2', 'G3', 'G4', 'G5', 'G6', 'G7', 'GDB'];
        $grouped = $results->groupBy('prize_tier');
        
        $tierDelays = [
            'G1' => 2.0, 'G2' => 2.0, 'G3' => 1.5, 'G4' => 1.5,
            'G5' => 1.2, 'G6' => 1.2, 'G7' => 1.0, 'GDB' => 3.0,
        ];
        
        broadcast(new XoSoUpdated('_start', 'live_draw_started', 0));
        $this->info('🎬 Phát lại tường thuật!');
        sleep(2);
        
        foreach ($tierOrder as $tier) {
            $numbers = $grouped[$tier] ?? collect();
            if ($numbers->isEmpty()) continue;
            
            $frontendKey = $this->tierToKey[$tier] ?? $tier;
            $label = strtoupper(str_replace('_', ' ', $frontendKey));
            $this->info("📢 Đang quay {$label}...");
            
            if ($tier !== 'G1') {
                usleep(1200000); 
            }
            
            foreach ($numbers->values() as $index => $result) {
                broadcast(new XoSoUpdated($frontendKey, $result->full_number, $index));
                $this->line("   🔴 [{$tier}] #{$index} = <fg=red;options=bold>{$result->full_number}</>");
                
                $delay = $tierDelays[$tier] ?? 1.5;
                usleep((int)($delay * 1000000));
            }
        }
        
        broadcast(new XoSoUpdated('_end', 'live_draw_completed', 0));
        $this->info(' Phát lại hoàn tất!');
    }
}