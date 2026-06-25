<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Draw;
use App\Models\LotoStatistic;
use App\Models\AnalysisExtraction;

class XsmbApiController extends Controller
{
    /**
     * Lấy danh sách kết quả quay số (Bao gồm chi tiết các giải)
     */
    public function getDraws(Request $request)
    {
        // Tạm thời tắt giới hạn RAM của PHP để load 20 năm dữ liệu
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '300'); // Cho phép chạy tối đa 5 phút

        // Lấy số ngày muốn xem (Ví dụ: 8000 ngày ~ 21 năm)
        $limit = $request->query('limit', 11000); 
        
        $draws = Draw::with('results')
            ->orderBy('draw_date', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $draws
        ]);
    }

    /**
     * Lấy bảng thống kê Loto (Rất hữu ích để lọc lô gan trên Excel)
     */
    public function getLotoStats()
    {
        // Lấy toàn bộ 100 số loto
        $stats = LotoStatistic::orderBy('loto_number', 'asc')->get();

        return response()->json([
            'success' => true,
            'data'    => $stats
        ]);
    }

    /**
     * Lấy dữ liệu phân tích giải ĐB và Giải 1
     */
    public function getAnalysis(Request $request)
    {
        $limit = $request->query('limit', 7);

        $analysis = AnalysisExtraction::orderBy('draw_date', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $analysis
        ]);
    }
}