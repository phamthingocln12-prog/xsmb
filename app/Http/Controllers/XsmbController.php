<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LotoStatistic;
use App\Models\Draw;

class XsmbController extends Controller
{
    // 1. Lấy kết quả mới nhất (Trang chủ)
    public function getLatestDraw()
    {
        // Lấy kỳ quay hoàn tất gần nhất kèm theo chi tiết các giải
        $latestDraw = Draw::with('results')
            ->where('status', 'completed')
            ->orderBy('draw_date', 'desc')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $latestDraw
        ]);
    }

    // 2. Lấy danh sách Lô Gan (Trang Lô Gan)
    public function getLoGan()
    {
        // Chỉ cần query thẳng vào bảng cache, sắp xếp số ngày gan giảm dần. Siêu nhanh!
        $loGans = LotoStatistic::orderBy('current_gan_days', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $loGans
        ]);
    }

    // 3. Lấy Top các số về nhiều nhất (Tần suất)
    public function getTopLoto()
    {
        // Lấy 10 số xuất hiện nhiều nhất
        $topLotos = LotoStatistic::orderBy('total_appearances', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $topLotos
        ]);
    }
}
