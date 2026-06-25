<?php
namespace App\Services;
use App\Events\XoSoUpdated;
use App\Models\Draw;
use Illuminate\Support\Facades\Log;
class LiveDrawService
{
    /**
     * Cấu trúc giải XSMB chính thức.
     * key => [digits, count, delay_after_each_number (seconds)]
     */
    protected array $prizeStructure = [
        'giai_1'         => ['digits' => 5, 'count' => 1, 'delay' => 3.0],
        'giai_2'         => ['digits' => 5, 'count' => 2, 'delay' => 2.5],
        'giai_3'         => ['digits' => 5, 'count' => 6, 'delay' => 2.0],
        'giai_4'         => ['digits' => 4, 'count' => 4, 'delay' => 2.0],
        'giai_5'         => ['digits' => 4, 'count' => 6, 'delay' => 1.5],
        'giai_6'         => ['digits' => 3, 'count' => 3, 'delay' => 1.5],
        'giai_7'         => ['digits' => 2, 'count' => 4, 'delay' => 1.5],
        'giai_dac_biet'  => ['digits' => 5, 'count' => 1, 'delay' => 5.0],
    ];
    /**
     * Map key → prize_tier trong DB
     */
    protected array $keyToTier = [
        'giai_7'        => 'G7',
        'giai_6'        => 'G6',
        'giai_5'        => 'G5',
        'giai_4'        => 'G4',
        'giai_3'        => 'G3',
        'giai_2'        => 'G2',
        'giai_1'        => 'G1',
        'giai_dac_biet' => 'GDB',
    ];
    /**
     * Lấy dữ liệu giải thưởng từ database (hôm nay).
     */
    public function getResultsFromDb(): ?array
    {
        $draw = Draw::with('results')
            ->where('status', 'completed')
            ->orderBy('draw_date', 'desc')
            ->first();
        if (!$draw || !$draw->results || $draw->results->count() === 0) {
            return null;
        }
        $results = [];
        foreach ($this->prizeStructure as $key => $config) {
            $tier = $this->keyToTier[$key];
            $numbers = $draw->results
                ->where('prize_tier', $tier)
                ->pluck('full_number')
                ->values()
                ->toArray();
            $results[$key] = $numbers;
        }
        return $results;
    }
    /**
     * Sinh dữ liệu ngẫu nhiên.
     */
    public function generateRandomResults(): array
    {
        $results = [];
        foreach ($this->prizeStructure as $key => $config) {
            $numbers = [];
            for ($i = 0; $i < $config['count']; $i++) {
                $max = pow(10, $config['digits']) - 1;
                $numbers[] = str_pad(rand(0, $max), $config['digits'], '0', STR_PAD_LEFT);
            }
            $results[$key] = $numbers;
        }
        return $results;
    }
    /**
     * Chạy mô phỏng quay số trực tiếp.
     * Broadcast từng số theo timeline thực tế.
     *
     * @param string $source 'random' hoặc 'today'
     * @param callable|null $logger Callback để log thông tin ra console
     */
    public function runLiveDraw(string $source = 'random', ?callable $logger = null): void
    {
// 1. Lấy dữ liệu
        if ($source === 'today') {
            $results = $this->getResultsFromDb();
            if (!$results) {
                $msg = 'Không tìm thấy dữ liệu kỳ quay nào trong DB. Sử dụng dữ liệu ngẫu nhiên.';
                Log::warning($msg);
                if ($logger) $logger($msg);
                $results = $this->generateRandomResults();
            }
        } else {
            $results = $this->generateRandomResults();
        }
        // 2. Broadcast signal bắt đầu quay
        broadcast(new XoSoUpdated('_start', 'live_draw_started', 0));
        if ($logger) $logger('🎬 Bắt đầu quay số trực tiếp!');
        sleep(2); // chờ 2 giây trước khi bắt đầu
        // 3. Broadcast từng giải theo thứ tự timeline
        foreach ($this->prizeStructure as $key => $config) {
            $values = $results[$key] ?? [];
            // Đảm bảo đủ số lượng giải
            while (count($values) < $config['count']) {
                $max = pow(10, $config['digits']) - 1;
                $values[] = str_pad(rand(0, $max), $config['digits'], '0', STR_PAD_LEFT);
            }
            // Gửi delay giữa các nhóm giải
            if ($key !== 'giai_1') {
                usleep(1500000); // 1.5 giây nghỉ giữa các nhóm giải
            }
            if ($logger) {
                $label = strtoupper(str_replace('_', ' ', $key));
                $logger("📢 Đang quay {$label}...");
            }
            // Broadcast từng số trong giải
            foreach ($values as $index => $value) {
                broadcast(new XoSoUpdated($key, $value, $index));
                if ($logger) {
                    $logger("   ✅ {$key}[{$index}] = {$value}");
                }
                // Delay giữa các số trong cùng nhóm giải
                if ($index < count($values) - 1) {
                    usleep((int)($config['delay'] * 1000000));
                }
            }
            // Delay sau khi xong nhóm giải
            usleep((int)($config['delay'] * 1000000));
        }
        // 4. Broadcast signal kết thúc
        broadcast(new XoSoUpdated('_end', 'live_draw_completed', 0));
        if ($logger) $logger('🏁 Quay số hoàn tất!');
    }
    /**
     * Lấy cấu trúc giải thưởng (cho frontend reference).
     */
    public function getPrizeStructure(): array
    {
        return $this->prizeStructure;
    }
}