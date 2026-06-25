<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\XsmbController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\Api\XsmbApiController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Giao diện (Frontend)
Route::get('/', [FrontendController::class, 'index'])->name('home');
Route::get('/thong-ke', [FrontendController::class, 'thongKe'])->name('thong-ke');
Route::get('/lo-gan', [FrontendController::class, 'loGan'])->name('lo-gan');
Route::get('/dau-duoi', [FrontendController::class, 'dauDuoi'])->name('dau-duoi');
Route::get('/ky-quay', [FrontendController::class, 'kyQuay'])->name('ky-quay');

// Phân tích DB & G1
Route::get('/phan-tich', [AnalysisController::class, 'dashboard'])->name('phan-tich');
Route::get('/api/analysis/strategy/{key}', [AnalysisController::class, 'apiStrategy']);
Route::get('/api/analysis/stats/{days?}', [AnalysisController::class, 'apiStats']);

// Các API lấy dữ liệu thống kê
Route::get('/api/latest-draw', [XsmbController::class, 'getLatestDraw']);
Route::get('/api/lo-gan', [XsmbController::class, 'getLoGan']);
Route::get('/api/top-loto', [XsmbController::class, 'getTopLoto']);

// Quay số trực tiếp (Live Draw)
Route::get('/quay-so-truc-tiep', function () {
    return view('live-draw');
})->name('live-draw');


Route::get('/test-broadcast', function () {
    // Giả lập dữ liệu giải mới vừa quay xong
    $newData = [
        'prize_tier' => 'GDB',
        'full_number' => '88888',
        'loto_number' => '88',
        'time' => now()->toDateTimeString()
    ];

    // Gọi Event để bắn dữ liệu lên Pusher
    event(new \App\Events\DrawResultUpdated($newData));

    return 'Đã bắn dữ liệu thành công lên Pusher!';
});

// Route gọi lệnh cào thủ công bằng nút bấm
Route::post('/manual-crawl', [App\Http\Controllers\FrontendController::class, 'manualCrawl'])->name('manual.crawl');

// Route cào 1 lần (cho frontend auto-poll)
Route::post('/crawl-once', [App\Http\Controllers\FrontendController::class, 'crawlOnce'])->name('crawl.once');

Route::get('/api/analysis/range-stats', [\App\Http\Controllers\AnalysisController::class, 'getRangeStats']);

// API: Trạng thái tường thuật trực tiếp (live-status)
Route::get('/api/live-status', [FrontendController::class, 'liveStatus'])->name('live.status');

// Lấy kết quả xổ số chi tiết
Route::get('/xsmb/draws', [XsmbApiController::class, 'getDraws']);

// Lấy bảng thống kê lô (lô gan, tần suất)
Route::get('/xsmb/statistics', [XsmbApiController::class, 'getLotoStats']);

// Lấy bảng trích xuất đầu đuôi GĐB, G1
Route::get('/xsmb/analysis', [XsmbApiController::class, 'getAnalysis']);
