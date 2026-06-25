<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$today = '2026-03-29';
$draw = App\Models\Draw::where('draw_date', $today)->first();

if ($draw) {
    // Xóa tất cả kết quả
    $deleted = App\Models\DrawResult::where('draw_id', $draw->id)->delete();
    echo "Đã xóa {$deleted} kết quả." . PHP_EOL;
    
    // Xóa analysis extraction
    App\Models\AnalysisExtraction::where('draw_id', $draw->id)->delete();
    
    // Reset draw status
    $draw->update(['status' => 'pending']);
    echo "Draw ID {$draw->id} đã reset về pending." . PHP_EOL;
} else {
    echo "Không tìm thấy draw ngày {$today}" . PHP_EOL;
}

echo "✅ Sẵn sàng test!" . PHP_EOL;
