<?php
/**
 * TEST TỰ ĐỘNG CÀO HẰNG NGÀY
 * 
 * Script này mô phỏng quy trình:
 * 1. Xoá dữ liệu ngày hôm nay (giả vờ chưa có)
 * 2. Chạy lệnh crawl:today (giống scheduler chạy tự động)
 * 3. Chờ ProcessXsmbDraw job xử lý
 * 4. Kiểm tra kết quả
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Draw;
use App\Models\DrawResult;
use App\Models\AnalysisExtraction;

$today = now()->format('Y-m-d');
$todayVN = now()->format('d/m/Y');

echo "╔══════════════════════════════════════════════════╗\n";
echo "║   🧪 TEST TỰ ĐỘNG CÀO DỮ LIỆU HẰNG NGÀY      ║\n";
echo "║   Ngày test: {$todayVN}                         ║\n";
echo "╚══════════════════════════════════════════════════╝\n\n";

// === BƯỚC 1: Kiểm tra trạng thái hiện tại ===
echo "📋 BƯỚC 1: Kiểm tra trạng thái hiện tại...\n";
$draw = Draw::where('draw_date', $today)->first();
if ($draw) {
    $count = $draw->results()->count();
    echo "   → Draw tồn tại: status={$draw->status}, {$count}/27 giải\n";
    $hasExtraction = AnalysisExtraction::where('draw_id', $draw->id)->exists();
    echo "   → Phân tích ĐB+G1: " . ($hasExtraction ? '✅ Có' : '❌ Chưa') . "\n";
} else {
    echo "   → Chưa có draw cho ngày hôm nay\n";
}

// === BƯỚC 2: Xoá dữ liệu để test ===
echo "\n🗑️  BƯỚC 2: Xoá dữ liệu ngày hôm nay để mô phỏng...\n";
if ($draw) {
    AnalysisExtraction::where('draw_id', $draw->id)->delete();
    $draw->results()->delete();
    $draw->delete();
    echo "   → Đã xoá draw + results + analysis_extraction\n";
} else {
    echo "   → Không có gì để xoá\n";
}

// === BƯỚC 3: Chạy crawl:today (giống scheduler tự chạy) ===
echo "\n🚀 BƯỚC 3: Chạy 'crawl:today' (mô phỏng scheduler)...\n";
echo "   → Lệnh này sẽ dispatch ProcessXsmbDraw job...\n\n";

// Chạy crawl:today
$exitCode = Artisan::call('crawl:today');
$output = Artisan::output();
echo "   " . str_replace("\n", "\n   ", trim($output)) . "\n";

// === BƯỚC 4: Vì queue driver có thể là 'sync', job đã chạy xong ===
// Nếu queue driver là 'database', cần chạy queue:work
echo "\n📊 BƯỚC 4: Kiểm tra queue driver...\n";
$queueDriver = config('queue.default');
echo "   → Queue driver: {$queueDriver}\n";

if ($queueDriver !== 'sync') {
    echo "   → Queue driver không phải 'sync', cần chạy queue worker...\n";
    echo "   → Đang chạy 1 job từ queue...\n";
    Artisan::call('queue:work', ['--once' => true, '--timeout' => 120]);
    echo "   → " . trim(Artisan::output()) . "\n";
}

// === BƯỚC 5: Kiểm tra kết quả sau khi crawl ===
echo "\n✅ BƯỚC 5: Kiểm tra kết quả sau crawl...\n";
$draw = Draw::where('draw_date', $today)->first();

if (!$draw) {
    echo "   ❌ Không tìm thấy draw! Crawl có thể bị lỗi.\n";
    exit(1);
}

$count = $draw->results()->count();
$status = $draw->status;
echo "   → Status: {$status}\n";
echo "   → Số giải: {$count}/27\n";

if ($count >= 27) {
    echo "\n   🎉 THÀNH CÔNG! Đã cào đủ 27 giải!\n\n";
    
    // Hiển thị kết quả
    $tiers = ['GDB' => 'Đặc biệt', 'G1' => 'Giải Nhất', 'G2' => 'Giải Nhì', 
              'G3' => 'Giải Ba', 'G4' => 'Giải Tư', 'G5' => 'Giải Năm', 
              'G6' => 'Giải Sáu', 'G7' => 'Giải Bảy'];
    
    echo "   ┌───────────────┬────────────────────────────────────┐\n";
    foreach ($tiers as $tier => $label) {
        $numbers = DrawResult::where('draw_id', $draw->id)
            ->where('prize_tier', $tier)
            ->pluck('full_number')
            ->implode(' - ');
        $label = str_pad($label, 13);
        echo "   │ {$label} │ {$numbers}\n";
    }
    echo "   └───────────────┴────────────────────────────────────┘\n";
    
    // Kiểm tra phân tích
    $hasExtraction = AnalysisExtraction::where('draw_id', $draw->id)->exists();
    echo "\n   📐 Phân tích ĐB+G1: " . ($hasExtraction ? '✅ Đã cắt số' : '❌ Chưa cắt') . "\n";
    
} else {
    echo "\n   ⚠️  Chưa đủ 27 giải. Có thể trang nguồn chưa có đủ dữ liệu.\n";
    echo "   → Nếu đang trong giờ quay (18:10-18:30), đây là bình thường.\n";
    echo "   → Thử lại sau 18:35 khi kết quả đã đầy đủ.\n";
    
    // Hiển thị những gì đã cào được
    if ($count > 0) {
        echo "\n   Các giải đã cào:\n";
        foreach ($draw->results as $r) {
            echo "   → [{$r->prize_tier}] {$r->full_number}\n";
        }
    }
}

echo "\n════════════════════════════════════════════════════\n";
echo "📝 GHI CHÚ:\n";
echo "   - Scheduler chạy 'crawl:today' lúc 18:35 và mỗi 4 giờ\n";
echo "   - ProcessXsmbDraw sẽ retry tối đa 20 lần × 30s\n";
echo "   - Sau 18:35, trang xoso.com.vn thường đã có đủ 27 giải\n";
echo "════════════════════════════════════════════════════\n";
