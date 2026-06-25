<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Draw;
use App\Models\DrawResult;
use App\Models\LotoStatistic;
use Illuminate\Support\Facades\Cache;

class FrontendController extends Controller
{
    public function index(Request $request)
    {
        // Nhận ngày từ thanh tìm kiếm (nếu có)
        $selectedDate = $request->input('date');

        if ($selectedDate) {
            // ĐÃ SỬA: 1. Thêm $selectedDate vào key cache, 2. Truyền biến use ($selectedDate) vào trong
            $latestDraw = Cache::remember('latest_draw_data_' . $selectedDate, 5, function () use ($selectedDate) {
                // ĐÃ SỬA: Thêm điều kiện where đúng cái ngày người dùng chọn
                $draw = Draw::with('results')
                    ->where('draw_date', $selectedDate) 
                    ->first();

                return $draw;
            });
                
            // KHÓA MÕM TỰ LÙI NGÀY Ở VIEW
            // Nếu tìm ngày mà không có dữ liệu, tạo ảo 1 bản ghi rỗng để View không tự đi lấy ngày cũ
            if (!$latestDraw) {
                $latestDraw = new Draw();
                $latestDraw->draw_date = $selectedDate;
                $latestDraw->status = 'pending';
                $latestDraw->setRelation('results', collect([]));
            }
        } else {
            // ĐÃ SỬA: Bọc Cache 5 giây cho trang chủ mặc định để chống sập khi khách spam F5
            $latestDraw = Cache::remember('latest_draw_default', 5, function () {
                return Draw::with('results')
                    ->whereIn('status', ['completed', 'updating'])
                    ->orderBy('draw_date', 'desc')
                    ->first();
            });

            // Set lại biến date để hiển thị lên lịch
            if ($latestDraw) {
                $selectedDate = $latestDraw->draw_date;
            } else {
                $selectedDate = now()->format('Y-m-d');
            }
        }

        // Gom nhóm kết quả theo tên giải (GDB, G1, G2...) để view dễ in ra
        $groupedResults = [];
        $dauStats = array_fill(0, 10, []); // Đầu 0-9
        $duoiStats = array_fill(0, 10, []); // Đuôi 0-9
        $gdbLoto = ''; // 2 số cuối giải ĐB để highlight

        if ($latestDraw && $latestDraw->results && $latestDraw->results->count() > 0) {
            foreach ($latestDraw->results as $result) {
                $groupedResults[$result->prize_tier][] = $result->full_number;

                // Lưu lại loto của giải ĐB
                if ($result->prize_tier === 'GDB') {
                    $gdbLoto = $result->loto_number;
                }

                // Tính thống kê đầu đuôi từ loto_number (2 số cuối)
                $loto = $result->loto_number;
                if (strlen($loto) == 2) {
                    $dau = (int) substr($loto, 0, 1);
                    $duoi = (int) substr($loto, 1, 1);
                    $dauStats[$dau][] = $loto;
                    $duoiStats[$duoi][] = $loto;
                }
            }
        } else {
            // ĐÂY LÀ CHÌA KHÓA FIX LỖI: 
            // Ép mảng về dấu gạch ngang để hiển thị bảng trống, không cho View tự lấy dữ liệu cũ.
            $groupedResults = [
                'GDB' => ['-----'],
                'G1'  => ['-----'],
                'G2'  => ['-----', '-----'],
                'G3'  => ['-----', '-----', '-----', '-----', '-----', '-----'],
                'G4'  => ['-----', '-----', '-----', '-----'],
                'G5'  => ['-----', '-----', '-----', '-----', '-----', '-----'],
                'G6'  => ['-----', '-----', '-----'],
                'G7'  => ['-----', '-----', '-----', '-----'],
            ];
        }

        // === TẤT CẢ THỐNG KÊ THEO NGÀY ĐÃ CHỌN ($selectedDate) ===
        $selectedCarbon = \Carbon\Carbon::parse($selectedDate);

        // Lấy 10 kỳ quay gần nhất TÍNH TỪ ngày đã chọn (để tính cầu, lô gan, nóng/lạnh)
        $recentDraws = Draw::with('results')
            ->where('status', 'completed')
            ->where('draw_date', '<=', $selectedDate)
            ->orderBy('draw_date', 'desc')
            ->limit(10)
            ->get();

        // Lấy 30 kỳ gần nhất (để tính nóng/lạnh)
        $recent30Draws = Draw::with('results')
            ->where('status', 'completed')
            ->where('draw_date', '<=', $selectedDate)
            ->orderBy('draw_date', 'desc')
            ->limit(30)
            ->get();

        // --- LÔ GAN: tính số ngày chưa về tính đến ngày đã chọn ---
        $loGanTop = [];
        $allDrawsForGan = Draw::with('results')
            ->where('status', 'completed')
            ->where('draw_date', '<=', $selectedDate)
            ->orderBy('draw_date', 'desc')
            ->limit(60)
            ->get();

        $lastSeen = []; // loto_number => ngày cuối cùng xuất hiện
        foreach ($allDrawsForGan as $draw) {
            if ($draw->results) {
                foreach ($draw->results as $r) {
                    if (!isset($lastSeen[$r->loto_number])) {
                        $lastSeen[$r->loto_number] = $draw->draw_date;
                    }
                }
            }
        }
        // Tính gan days, sắp xếp  
        $ganList = [];
        for ($n = 0; $n < 100; $n++) {
            $numStr = str_pad($n, 2, '0', STR_PAD_LEFT);
            if (isset($lastSeen[$numStr])) {
                $ganDays = $selectedCarbon->diffInDays(\Carbon\Carbon::parse($lastSeen[$numStr]));
                if ($ganDays > 0) {
                    $ganList[] = (object)[
                        'loto_number' => $numStr,
                        'current_gan_days' => $ganDays,
                        'max_gan_days' => $ganDays,
                        'last_appeared_date' => $lastSeen[$numStr],
                    ];
                }
            }
        }
        usort($ganList, fn($a, $b) => $b->current_gan_days - $a->current_gan_days);
        $loGanTop = collect(array_slice($ganList, 0, 10));

        // --- SỐ NÓNG / SỐ LẠNH: tần suất 30 ngày tính đến ngày đã chọn ---
        $freq30 = [];
        foreach ($recent30Draws as $draw) {
            if ($draw->results) {
                foreach ($draw->results as $r) {
                    $freq30[$r->loto_number] = ($freq30[$r->loto_number] ?? 0) + 1;
                }
            }
        }
        arsort($freq30);
        $hotNumbers = collect(array_slice(array_map(fn($num, $cnt) => (object)['loto_number' => $num, 'total_appearances' => $cnt], array_keys($freq30), $freq30), 0, 5));
        asort($freq30);
        $coldNumbers = collect(array_slice(array_map(fn($num, $cnt) => (object)['loto_number' => $num, 'total_appearances' => $cnt], array_keys($freq30), $freq30), 0, 5));

        // --- GỢI Ý SỐ CHO NGÀY TIẾP THEO (sau ngày đã chọn) ---
        $tomorrowDate = $selectedCarbon->copy()->addDay()->format('d/m/Y');

        // 1. Top 3 lô gan
        $ganSuggestions = $loGanTop->take(3)->pluck('loto_number')->toArray();

        // 2. Top 3 số nóng 7 ngày gần (tính từ ngày đã chọn)
        $recent7 = $recentDraws->take(7);
        $recentFreq = [];
        foreach ($recent7 as $draw) {
            if ($draw->results) {
                foreach ($draw->results as $result) {
                    $recentFreq[$result->loto_number] = ($recentFreq[$result->loto_number] ?? 0) + 1;
                }
            }
        }
        arsort($recentFreq);
        $hotSuggestions = array_slice(array_keys($recentFreq), 0, 3);

        // 3. Random theo ngày đã chọn
        mt_srand(crc32($selectedDate));
        $randomSuggestions = [];
        while (count($randomSuggestions) < 2) {
            $num = str_pad(mt_rand(0, 99), 2, '0', STR_PAD_LEFT);
            if (!in_array($num, $ganSuggestions) && !in_array($num, $hotSuggestions) && !in_array($num, $randomSuggestions)) {
                $randomSuggestions[] = $num;
            }
        }
        mt_srand();

        $suggestions = [
            ['label' => 'Lô gan', 'numbers' => $ganSuggestions, 'reason' => 'Lâu chưa về, xác suất cao'],
            ['label' => 'Số nóng', 'numbers' => $hotSuggestions, 'reason' => 'Về nhiều trong 7 ngày gần'],
            ['label' => 'Dự đoán', 'numbers' => $randomSuggestions, 'reason' => 'Phân tích xu hướng'],
        ];

        // --- CẦU LÔ TÔ: số xuất hiện liên tiếp (tính đến ngày đã chọn) ---
        $cauLoto = [];
        if ($recentDraws->count() >= 2) {
            $dailyLotos = [];
            foreach ($recentDraws as $draw) {
                $lotos = [];
                if ($draw->results) {
                    foreach ($draw->results as $r) { $lotos[$r->loto_number] = true; }
                }
                $dailyLotos[] = $lotos;
            }
            for ($num = 0; $num < 100; $num++) {
                $numStr = str_pad($num, 2, '0', STR_PAD_LEFT);
                $streak = 0;
                foreach ($dailyLotos as $day) {
                    if (isset($day[$numStr])) { $streak++; } else { break; }
                }
                if ($streak >= 2) { $cauLoto[] = ['number' => $numStr, 'streak' => $streak]; }
            }
            usort($cauLoto, fn($a, $b) => $b['streak'] - $a['streak']);
            $cauLoto = array_slice($cauLoto, 0, 5);
        }

        // --- CẦU ĐẶC BIỆT ---
        $cauDB = [];
        if ($recentDraws->count() >= 2) {
            $dailyGDB = [];
            foreach ($recentDraws as $draw) {
                if ($draw->results) {
                    foreach ($draw->results as $r) {
                        if ($r->prize_tier === 'GDB') { $dailyGDB[] = $r->loto_number; }
                    }
                }
            }
            $gdbFreq = array_count_values($dailyGDB);
            arsort($gdbFreq);
            foreach ($gdbFreq as $num => $count) {
                if ($count >= 2) { $cauDB[] = ['number' => $num, 'count' => $count]; }
            }
            if (empty($cauDB) && count($dailyGDB) >= 1) {
                foreach (array_slice($dailyGDB, 0, 3) as $num) {
                    $cauDB[] = ['number' => $num, 'count' => 1];
                }
            }
            $cauDB = array_slice($cauDB, 0, 3);
        }

        // --- CẦU 2 NHÁY ---
        $cau2Nhay = [];
        foreach ($recentDraws->take(5) as $draw) {
            if ($draw->results) {
                $lotoCount = [];
                foreach ($draw->results as $r) {
                    $lotoCount[$r->loto_number] = ($lotoCount[$r->loto_number] ?? 0) + 1;
                }
                foreach ($lotoCount as $num => $count) {
                    if ($count == 2) { $cau2Nhay[] = ['number' => $num, 'date' => $draw->draw_date]; }
                }
            }
        }
        $cau2Nhay = array_slice($cau2Nhay, 0, 5);

        // === DỰ ĐOÁN NGÀY MAI (phân tích sâu) ===
        $tomorrowDateFull = $selectedCarbon->copy()->addDay()->format('d/m/Y');
        $tomorrowDow = $selectedCarbon->copy()->addDay()->locale('vi')->isoFormat('dddd');

        // 1. Phân tích tần suất theo thứ (30 kỳ gần nhất cùng thứ)
        $targetDow = $selectedCarbon->copy()->addDay()->dayOfWeekIso;
        $sameDowDraws = Draw::with('results')
            ->where('status', 'completed')
            ->where('draw_date', '<=', $selectedDate)
            ->orderBy('draw_date', 'desc')
            ->limit(200)
            ->get()
            ->filter(fn($d) => \Carbon\Carbon::parse($d->draw_date)->dayOfWeekIso === $targetDow)
            ->take(30);

        $dowFreq = [];
        foreach ($sameDowDraws as $draw) {
            if ($draw->results) {
                foreach ($draw->results as $r) {
                    $dowFreq[$r->loto_number] = ($dowFreq[$r->loto_number] ?? 0) + 1;
                }
            }
        }
        arsort($dowFreq);
        $topDow = array_slice(array_keys($dowFreq), 0, 5);

        // 2. Tính điểm tổng hợp cho mỗi số
        $scores = [];
        for ($n = 0; $n < 100; $n++) {
            $numStr = str_pad($n, 2, '0', STR_PAD_LEFT);
            $score = 0;

            // +3 nếu số gan >= 10 ngày
            foreach ($ganList as $g) {
                if ($g->loto_number === $numStr && $g->current_gan_days >= 10) {
                    $score += min(5, intdiv($g->current_gan_days, 5));
                }
            }

            // +2 nếu số nóng 7 ngày
            if (isset($recentFreq[$numStr]) && $recentFreq[$numStr] >= 3) {
                $score += 2;
            }

            // +2 nếu cầu liên tiếp
            foreach ($cauLoto as $c) {
                if ($c['number'] === $numStr) { $score += 2; break; }
            }

            // +1 nếu hay về theo thứ
            if (in_array($numStr, $topDow)) { $score += 1; }

            // +1 nếu tần suất 30 ngày cao
            if (isset($freq30[$numStr]) && $freq30[$numStr] >= 5) { $score += 1; }

            if ($score >= 2) {
                $reasons = [];
                foreach ($ganList as $g) {
                    if ($g->loto_number === $numStr && $g->current_gan_days >= 10) {
                        $reasons[] = 'Gan ' . $g->current_gan_days . ' ngày';
                    }
                }
                if (isset($recentFreq[$numStr]) && $recentFreq[$numStr] >= 3) {
                    $reasons[] = 'Nóng (' . $recentFreq[$numStr] . ' lần/7 ngày)';
                }
                foreach ($cauLoto as $c) {
                    if ($c['number'] === $numStr) { $reasons[] = 'Cầu ' . $c['streak'] . ' ngày'; break; }
                }
                if (in_array($numStr, $topDow)) {
                    $reasons[] = 'Hay về ' . $tomorrowDow;
                }

                $scores[] = [
                    'number' => $numStr,
                    'score' => $score,
                    'reasons' => $reasons,
                ];
            }
        }
        usort($scores, fn($a, $b) => $b['score'] - $a['score']);
        $tomorrowTop = array_slice($scores, 0, 10);

        // 3. Dự đoán ĐB ngày mai
        $gdbPredictions = [];
        $recent20GDB = Draw::with('results')
            ->where('status', 'completed')
            ->where('draw_date', '<=', $selectedDate)
            ->orderBy('draw_date', 'desc')
            ->limit(20)
            ->get();

        $gdbLotos = [];
        foreach ($recent20GDB as $d) {
            if ($d->results) {
                foreach ($d->results as $r) {
                    if ($r->prize_tier === 'GDB') { $gdbLotos[] = $r->loto_number; }
                }
            }
        }
        $gdbFreqAll = array_count_values($gdbLotos);
        arsort($gdbFreqAll);
        $idx = 0;
        foreach ($gdbFreqAll as $num => $cnt) {
            if ($idx >= 3) break;
            $gdbPredictions[] = ['number' => $num, 'count' => $cnt, 'reason' => "$cnt lần ĐB / 20 kỳ"];
            $idx++;
        }

        // 4. Phân tích đầu đuôi nóng ngày mai
        $headFreq = array_fill(0, 10, 0);
        $tailFreq = array_fill(0, 10, 0);
        foreach ($recentDraws->take(7) as $draw) {
            if ($draw->results) {
                foreach ($draw->results as $r) {
                    $l = $r->loto_number;
                    if (strlen($l) == 2) {
                        $headFreq[(int)substr($l, 0, 1)]++;
                        $tailFreq[(int)substr($l, 1, 1)]++;
                    }
                }
            }
        }

        // Top đầu đuôi nóng
        arsort($headFreq);
        arsort($tailFreq);
        $hotHeads = array_slice(array_keys($headFreq), 0, 3, true);
        $hotTails = array_slice(array_keys($tailFreq), 0, 3, true);

        $tomorrowPrediction = [
            'date' => $tomorrowDateFull,
            'dow' => $tomorrowDow,
            'top' => $tomorrowTop,
            'gdb' => $gdbPredictions,
            'hotHeads' => $hotHeads,
            'hotTails' => $hotTails,
            'headFreq' => $headFreq,
            'tailFreq' => $tailFreq,
            'topDow' => $topDow,
            'dowCount' => count($sameDowDraws),
        ];

        // === GAN ALERT: số gan > 15 ngày để nhấp nháy cảnh báo ===
        $ganAlert = [];
        foreach ($ganList as $g) {
            if ($g->current_gan_days >= 15) {
                $ganAlert[] = [
                    'number' => $g->loto_number,
                    'days' => $g->current_gan_days,
                ];
            }
        }
        $ganAlert = array_slice($ganAlert, 0, 5);

        // === CALENDAR: lấy danh sách ngày trong tháng đã có dữ liệu ===
        $calendarStart = $selectedCarbon->copy()->startOfMonth()->format('Y-m-d');
        $calendarEnd = $selectedCarbon->copy()->endOfMonth()->format('Y-m-d');
        $datesWithData = Draw::where('status', 'completed')
            ->whereBetween('draw_date', [$calendarStart, $calendarEnd])
            ->pluck('draw_date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        return view('home', compact(
            'latestDraw', 'groupedResults', 'selectedDate',
            'dauStats', 'duoiStats', 'gdbLoto',
            'loGanTop', 'hotNumbers', 'coldNumbers',
            'suggestions', 'tomorrowDate',
            'cauLoto', 'cauDB', 'cau2Nhay',
            'tomorrowPrediction', 'ganAlert', 'datesWithData'
        ));
    }
    
    // 1. Trang Thống Kê (Lưới tần suất theo ngày)
    public function thongKe(Request $request)
    {
        // Bộ lọc
        $mode = $request->input('mode', 'lt'); // lt = Lô tô (tất cả giải), db = Đặc biệt
        $capSo = $request->input('cap_so', ''); // Lọc cặp số cụ thể (vd: "00,15,88")
        $fromDate = $request->input('from_date', now()->subDays(29)->format('Y-m-d'));
        $toDate = $request->input('to_date', now()->format('Y-m-d'));

        // Lấy các kỳ quay theo khoảng thời gian
        $draws = Draw::with('results')
            ->where('status', 'completed')
            ->whereBetween('draw_date', [$fromDate, $toDate])
            ->orderBy('draw_date', 'desc')
            ->get();

        // Parse cặp số cần lọc
        $filterNumbers = [];
        if (!empty($capSo)) {
            $filterNumbers = array_map(function($s) { return (int) trim($s); }, explode(',', $capSo));
            $filterNumbers = array_filter($filterNumbers, fn($n) => $n >= 0 && $n <= 99);
        }

        // Xây dựng dữ liệu lưới
        $gridData = [];
        $frequency = array_fill(0, 100, 0);

        foreach ($draws as $draw) {
            $dayData = ['date' => $draw->draw_date, 'numbers' => array_fill(0, 100, ['count' => 0, 'isGDB' => false])];

            if ($draw->results) {
                foreach ($draw->results as $result) {
                    $loto = (int) $result->loto_number;
                    $isGDB = $result->prize_tier === 'GDB';

                    // Nếu mode = db, chỉ đếm giải ĐB
                    if ($mode === 'db' && !$isGDB) {
                        continue;
                    }

                    $dayData['numbers'][$loto]['count']++;
                    $frequency[$loto]++;

                    if ($isGDB) {
                        $dayData['numbers'][$loto]['isGDB'] = true;
                    }
                }
            }

            $gridData[] = $dayData;
        }

        $maxFreq = max($frequency) ?: 1;

        // Tính số ngày thực tế
        $days = count($gridData);

        return view('thong-ke', compact('gridData', 'frequency', 'maxFreq', 'days', 'mode', 'capSo', 'fromDate', 'toDate', 'filterNumbers'));
    }

    // 2. Trang Lô Top (Các con lô top trong ngày - 3 bảng: qua, nay, mai)
    public function loGan()
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');
        $statsMap = LotoStatistic::all()->keyBy('loto_number');

        // Helper: lấy loto count từ draw theo ngày
        $getLotoForDate = function($date) {
            $draw = Draw::with('results')
                ->where('status', 'completed')
                ->where('draw_date', $date)
                ->first();

            $lotoCount = [];
            if ($draw && $draw->results) {
                foreach ($draw->results as $result) {
                    $num = $result->loto_number;
                    if (!isset($lotoCount[$num])) {
                        $lotoCount[$num] = ['number' => $num, 'count' => 0, 'prizes' => []];
                    }
                    $lotoCount[$num]['count']++;
                    $lotoCount[$num]['prizes'][] = $result->prize_tier;
                }
            }
            usort($lotoCount, fn($a, $b) => $b['count'] - $a['count']);
            return $lotoCount;
        };

        $yesterdayData = $getLotoForDate($yesterday);
        $todayData = $getLotoForDate($today);

        // Ngày mai: dự đoán dựa trên lô gan + số nóng 7 ngày
        $tomorrowDate = now()->addDay()->format('Y-m-d');
        $tomorrowData = [];

        // Lấy top lô gan
        $ganNumbers = LotoStatistic::where('current_gan_days', '>', 0)
            ->orderBy('current_gan_days', 'desc')
            ->limit(5)
            ->get();
        foreach ($ganNumbers as $gan) {
            $tomorrowData[] = [
                'number' => $gan->loto_number,
                'count' => 0,
                'prizes' => [],
                'reason' => 'Gan ' . $gan->current_gan_days . ' ngày',
            ];
        }

        // Lấy số nóng 7 ngày gần
        $recent = Draw::with('results')->where('status', 'completed')
            ->orderBy('draw_date', 'desc')->limit(7)->get();
        $freq = [];
        foreach ($recent as $d) {
            if ($d->results) {
                foreach ($d->results as $r) {
                    $freq[$r->loto_number] = ($freq[$r->loto_number] ?? 0) + 1;
                }
            }
        }
        arsort($freq);
        $existingNums = array_column($tomorrowData, 'number');
        $added = 0;
        foreach ($freq as $num => $cnt) {
            if ($added >= 5) break;
            if (!in_array($num, $existingNums)) {
                $tomorrowData[] = [
                    'number' => $num,
                    'count' => 0,
                    'prizes' => [],
                    'reason' => 'Nóng (' . $cnt . ' lần/7 ngày)',
                ];
                $added++;
            }
        }

        return view('lo-gan', compact(
            'yesterdayData', 'todayData', 'tomorrowData',
            'yesterday', 'today', 'tomorrowDate', 'statsMap'
        ));
    }

    // 3. Trang Thống Kê Đầu - Đuôi
    public function dauDuoi()
    {
        $stats = LotoStatistic::all();
        $heads = array_fill(0, 10, 0); // Mảng đếm đầu 0-9
        $tails = array_fill(0, 10, 0); // Mảng đếm đuôi 0-9

        foreach($stats as $stat) {
            $head = substr($stat->loto_number, 0, 1);
            $tail = substr($stat->loto_number, 1, 1);
            
            $heads[$head] += $stat->total_appearances;
            $tails[$tail] += $stat->total_appearances;
        }

        return view('dau-duoi', compact('heads', 'tails'));
    }

    // 4. Trang Kỳ Quay - Tra cứu kỳ quay theo khoảng ngày
    public function kyQuay(Request $request)
    {
        $fromDate = $request->input('from_date', '');
        $toDate = $request->input('to_date', '');
        $searchNumber = trim($request->input('search_number', ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 10;
        $onlyGdb = $request->input('only_gdb', '0');
        $showDauDuoi = $request->input('show_dau_duoi', '1');

        $hasDateFilter = ($fromDate !== '' && $toDate !== '');
        $hasNumberFilter = ($searchNumber !== '');

        // Parse nhiều số (phân cách bởi dấu phẩy hoặc khoảng trắng)
        $searchNumbers = [];
        if ($hasNumberFilter) {
            $parts = preg_split('/[\s,]+/', $searchNumber);
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p !== '' && is_numeric($p)) {
                    $searchNumbers[] = str_pad((int)$p, 2, '0', STR_PAD_LEFT);
                }
            }
            $searchNumbers = array_unique($searchNumbers);
        }

        $drawsData = [];
        $totalCount = 0;
        $totalPages = 1;
        $numberResults = []; // Bảng kết quả tìm theo số

        // === CHẾ ĐỘ 1: Tìm theo số (có hoặc không kèm ngày) ===
        $numPage = max(1, (int) $request->input('num_page', 1));
        $numPerPage = 20;
        $numTotalPages = [];
        $viewDate = $request->input('view_date', '');
        $viewDrawData = null;

        if (!empty($searchNumbers)) {
            // Query trực tiếp DrawResult cho giải ĐB khớp số
            $query = \App\Models\DrawResult::where('prize_tier', 'GDB')
                ->whereIn('loto_number', $searchNumbers)
                ->join('draws', 'draw_results.draw_id', '=', 'draws.id')
                ->where('draws.status', 'completed')
                ->orderBy('draws.draw_date', 'desc')
                ->select('draw_results.*', 'draws.draw_date');

            if ($hasDateFilter) {
                $query->whereBetween('draws.draw_date', [$fromDate, $toDate]);
            }

            $matchingResults = $query->get();

            // Xây dựng bảng: mỗi số -> danh sách ngày về
            foreach ($searchNumbers as $num) {
                $numberResults[$num] = [];
            }

            foreach ($matchingResults as $result) {
                $loto = $result->loto_number;
                if (isset($numberResults[$loto])) {
                    $numberResults[$loto][] = [
                        'date' => $result->draw_date,
                        'full_number' => $result->full_number,
                    ];
                }
            }

            // Phân trang cho mỗi số
            foreach ($searchNumbers as $num) {
                $total = count($numberResults[$num]);
                $numTotalPages[$num] = max(1, (int) ceil($total / $numPerPage));
                $numberResults[$num] = array_slice($numberResults[$num], ($numPage - 1) * $numPerPage, $numPerPage);
            }

            // Nếu có view_date, load kỳ quay đó
            if ($viewDate !== '') {
                $vDraw = Draw::with('results')
                    ->where('status', 'completed')
                    ->where('draw_date', $viewDate)
                    ->first();

                if ($vDraw) {
                    $grouped = [];
                    $dauStats = array_fill(0, 10, []);
                    $duoiStats = array_fill(0, 10, []);
                    $gdbNumber = '';

                    foreach ($vDraw->results as $result) {
                        $grouped[$result->prize_tier][] = $result->full_number;
                        if ($result->prize_tier === 'GDB') {
                            $gdbNumber = $result->loto_number;
                        }
                        $loto = $result->loto_number;
                        if (strlen($loto) == 2) {
                            $dau = (int) substr($loto, 0, 1);
                            $duoi = (int) substr($loto, 1, 1);
                            $dauStats[$dau][] = $loto;
                            $duoiStats[$duoi][] = $loto;
                        }
                    }

                    $viewDrawData = [
                        'draw' => $vDraw,
                        'grouped' => $grouped,
                        'dauStats' => $dauStats,
                        'duoiStats' => $duoiStats,
                        'gdbNumber' => $gdbNumber,
                    ];
                }
            }
        }

        // === CHẾ ĐỘ 2: Chỉ chọn ngày (không nhập số) -> hiện bảng kỳ quay ===
        if ($hasDateFilter && empty($searchNumbers)) {
            $query = Draw::with('results')
                ->where('status', 'completed')
                ->whereBetween('draw_date', [$fromDate, $toDate])
                ->orderBy('draw_date', 'desc');

            $allDraws = $query->get();
            $totalCount = $allDraws->count();
            $totalPages = max(1, (int) ceil($totalCount / $perPage));
            $pagedDraws = $allDraws->slice(($page - 1) * $perPage, $perPage);

            foreach ($pagedDraws as $draw) {
                $grouped = [];
                $dauStats = array_fill(0, 10, []);
                $duoiStats = array_fill(0, 10, []);
                $gdbNumber = '';

                if ($draw->results) {
                    foreach ($draw->results as $result) {
                        $grouped[$result->prize_tier][] = $result->full_number;

                        if ($result->prize_tier === 'GDB') {
                            $gdbNumber = $result->loto_number;
                        }

                        $loto = $result->loto_number;
                        if (strlen($loto) == 2) {
                            $dau = (int) substr($loto, 0, 1);
                            $duoi = (int) substr($loto, 1, 1);
                            $dauStats[$dau][] = $loto;
                            $duoiStats[$duoi][] = $loto;
                        }
                    }
                }

                $drawsData[] = [
                    'draw' => $draw,
                    'grouped' => $grouped,
                    'dauStats' => $dauStats,
                    'duoiStats' => $duoiStats,
                    'gdbNumber' => $gdbNumber,
                ];
            }
        }

        return view('ky-quay', compact(
            'drawsData', 'fromDate', 'toDate', 'searchNumber', 'totalCount',
            'page', 'totalPages', 'onlyGdb', 'showDauDuoi',
            'numberResults', 'searchNumbers',
            'numPage', 'numTotalPages', 'viewDate', 'viewDrawData'
        ));
    }

    // =========================================================
    // HÀM KÍCH HOẠT CÀO DỮ LIỆU BẰNG NÚT BẤM THỦ CÔNG
    // Đã nâng cấp: Nhận ngày bắt đầu và kết thúc từ Frontend
    // =========================================================
    public function manualCrawl(Request $request)
    {
        try {
            // 1. NHẬN NGÀY TỪ FRONTEND GỬI LÊN
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Nếu Frontend không gửi ngày (fallback về logic cũ tự tính ngày)
            if (!$startDate || !$endDate) {
                $endDate = now()->format('Y-m-d');
                $latestFullDraw = \App\Models\Draw::where('status', 'completed')
                    ->withCount('results')
                    ->having('results_count', '>=', 27)
                    ->orderBy('draw_date', 'desc')
                    ->first();
                
                if ($latestFullDraw) {
                    $startDate = \Carbon\Carbon::parse($latestFullDraw->draw_date)->addDay()->format('Y-m-d');
                    if ($startDate > $endDate) {
                        $startDate = $endDate;
                    }
                } else {
                    $startDate = $endDate;
                }
            }
            
            set_time_limit(120);
            
            // 2. GỌI LỆNH CÀO DỮ LIỆU
            \Illuminate\Support\Facades\Artisan::call('crawl:xsmb', [
                'start_date' => $startDate,
                'end_date'   => $endDate
            ]);

            // 3. THÁI NHỎ ĐẦU/ĐUÔI VÀO BẢNG analysis_extractions
            $safeStartDate = \Carbon\Carbon::parse($startDate)->subDays(5)->format('Y-m-d');
            
            $drawsWithoutExtraction = \App\Models\Draw::with('results')
                ->where('status', 'completed')
                ->where('draw_date', '>=', $safeStartDate) 
                ->where('draw_date', '<=', $endDate)
                ->whereNotIn('id', function($query) {
                    $query->select('draw_id')->from('analysis_extractions');
                })
                ->get();

            $countThained = 0;

            foreach ($drawsWithoutExtraction as $draw) {
                $resultsCollection = collect($draw->results);
                $gdb = $resultsCollection->where('prize_tier', 'GDB')->first();
                $g1 = $resultsCollection->where('prize_tier', 'G1')->first();
                
                if (!$gdb || !$g1) continue; 
                
                $gdb_full = $gdb->full_number;
                $g1_full = $g1->full_number;
                
                \App\Models\AnalysisExtraction::create([
                    'draw_id'    => $draw->id,
                    'draw_date'  => $draw->draw_date,
                    'gdb_full'   => $gdb_full, 
                    'g1_full'    => $g1_full,  
                    'gdb_first2' => strlen($gdb_full) >= 2 ? substr($gdb_full, 0, 2) : '',
                    'gdb_last2'  => strlen($gdb_full) >= 2 ? substr($gdb_full, -2) : '',
                    'g1_first2'  => strlen($g1_full) >= 2 ? substr($g1_full, 0, 2) : '',
                    'g1_last2'   => strlen($g1_full) >= 2 ? substr($g1_full, -2) : '',
                ]);
                
                $countThained++;
            }
            
            // 4. Kiểm tra kết quả của ngày kết thúc ($endDate)
            $todayDraw = \App\Models\Draw::with('results')->where('draw_date', $endDate)->first();
            $todayCount = $todayDraw ? $todayDraw->results->count() : 0;
            $todayStatus = $todayDraw ? $todayDraw->status : 'no_draw';

            
            // 4. Lấy danh sách kết quả của ngày hôm nay để trả về cho Frontend
            $resultsList = [];
            $todayDraw = \App\Models\Draw::with('results')->where('draw_date', $endDate)->first();
            if ($todayDraw && $todayDraw->results) {
                $tierIndex = [];
                foreach ($todayDraw->results as $r) {
                    $idx = $tierIndex[$r->prize_tier] ?? 0;
                    $resultsList[] = [
                        'tier' => $r->prize_tier,
                        'index' => $idx,
                        'value' => $r->full_number,
                    ];
                    $tierIndex[$r->prize_tier] = $idx + 1;
                }
            }

            $todayCount = $todayDraw ? $todayDraw->results->count() : 0;

            return response()->json([
                'success' => true, 
                'message' => "Đã cập nhật dữ liệu mới nhất!",
                'results' => $resultsList, // Trả về số để JS tự điền vào bảng
                'today_count' => $todayCount,
                'is_complete' => ($todayCount >= 27),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Lỗi: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * API: Cào 1 lần duy nhất cho ngày hôm nay và trả về tiến trình.
     * Frontend sẽ gọi API này lặp đi lặp lại mỗi 15s cho đến khi đủ 27 giải.
     */
    public function crawlOnce()
    {
        try {
            $today = now()->format('Y-m-d');
            $todayFormatted = now()->format('d-m-Y');
            
            set_time_limit(30);
            
            // Tạo / lấy kỳ quay hôm nay
            $draw = Draw::firstOrCreate(
                ['draw_date' => $today],
                ['status' => 'pending']
            );
            
            // Nếu đã completed và đủ 27 → trả về luôn
            $currentCount = $draw->results()->count();
            if ($draw->status === 'completed' && $currentCount >= 27) {
                return response()->json([
                    'success' => true,
                    'count' => $currentCount,
                    'is_complete' => true,
                    'status' => 'completed',
                    'message' => 'Đã đủ 27 giải!',
                ]);
            }
            
            // Cào 1 lần từ xoso.com.vn
            $client = new \GuzzleHttp\Client([
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
            
            $url = "https://xoso.com.vn/xsmb-{$todayFormatted}.html";
            $response = $client->request('GET', $url);
            $html = (string) $response->getBody();
            $crawler = new \Symfony\Component\DomCrawler\Crawler($html);
            
            $newCount = 0;
            $webCount = 0;
            
            foreach ($prizes as $tier => $selector) {
                $crawler->filter($selector)->each(function ($node) use ($draw, $tier, &$newCount, &$webCount) {
                    $fullNumber = trim($node->text());
                    if (!is_numeric($fullNumber)) return;
                    
                    $webCount++;
                    
                    $exists = DrawResult::where('draw_id', $draw->id)
                        ->where('prize_tier', $tier)
                        ->where('full_number', $fullNumber)
                        ->exists();
                    
                    if (!$exists) {
                        DrawResult::create([
                            'draw_id' => $draw->id,
                            'prize_tier' => $tier,
                            'full_number' => $fullNumber,
                            'loto_number' => substr($fullNumber, -2),
                        ]);
                        $newCount++;
                    }
                });
            }
            
            // Đếm lại tổng trong DB + lấy danh sách kết quả
            $totalInDb = $draw->results()->count();
            
            // Build danh sách kết quả (để frontend biết cụ thể từng giải)
            $allResults = DrawResult::where('draw_id', $draw->id)->get();
            $tierOrder = ['G7' => 1, 'G6' => 2, 'G5' => 3, 'G4' => 4, 'G3' => 5, 'G2' => 6, 'G1' => 7, 'GDB' => 8];
            $sorted = $allResults->sortBy(function ($r) use ($tierOrder) {
                return ($tierOrder[$r->prize_tier] ?? 99) * 100 + $r->id;
            });
            $tierIndex = [];
            $resultsList = [];
            foreach ($sorted as $r) {
                $idx = $tierIndex[$r->prize_tier] ?? 0;
                $resultsList[] = [
                    'tier' => $r->prize_tier,
                    'index' => $idx,
                    'value' => $r->full_number,
                    'loto' => $r->loto_number,
                ];
                $tierIndex[$r->prize_tier] = $idx + 1;
            }
            
            // Nếu đủ 27 → chốt sổ
            if ($totalInDb >= 27) {
                $draw->update(['status' => 'completed']);
                
                // Tính thống kê
                try {
                    \Illuminate\Support\Facades\Artisan::call('stat:calculate');
                } catch (\Exception $e) {}
                
                // Cắt số phân tích
                $gdb = DrawResult::where('draw_id', $draw->id)->where('prize_tier', 'GDB')->first();
                $g1  = DrawResult::where('draw_id', $draw->id)->where('prize_tier', 'G1')->first();
                if ($gdb && $g1) {
                    \App\Models\AnalysisExtraction::updateOrCreate(
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
                }
            } elseif ($totalInDb > 0) {
                $draw->update(['status' => 'updating']);
            }
            
            return response()->json([
                'success' => true,
                'count' => $totalInDb,
                'new' => $newCount,
                'web_count' => $webCount,
                'is_complete' => ($totalInDb >= 27),
                'status' => $draw->fresh()->status,
                'results' => $resultsList,
                'message' => $totalInDb >= 27 
                    ? "Đã đủ 27 giải!" 
                    : "Đã cào {$totalInDb}/27 giải (+{$newCount} mới). Đang chờ kết quả tiếp...",
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'count' => 0,
                'is_complete' => false,
                'message' => 'Lỗi: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * API: Trả về trạng thái tường thuật trực tiếp cho ngày hôm nay.
     * Frontend gọi sau khi reload để biết đã tường thuật đến đâu.
     */
    public function liveStatus()
    {
        $today = now()->format('Y-m-d');
        // ĐÃ SỬA: Cache lại 3 giây. Giúp giảm tải hàng ngàn request xuống còn 1 request/3 giây
        $draw = Cache::remember('live_status_draw_' . $today, 3, function () use ($today) {
            return Draw::with('results')
                ->where('draw_date', $today)
                ->first();
        });

        if (!$draw) {
            return response()->json([
                'is_live' => false,
                'date' => $today,
                'status' => 'no_draw',
                'results' => [],
                'total' => 0,
            ]);
        }

        $results = [];
        if ($draw->results) {
            // Sắp xếp theo thứ tự quay: G7 → G6 → ... → G1 → GDB
            $tierOrder = ['G7' => 1, 'G6' => 2, 'G5' => 3, 'G4' => 4, 'G3' => 5, 'G2' => 6, 'G1' => 7, 'GDB' => 8];
            $sorted = $draw->results->sortBy(function ($r) use ($tierOrder) {
                return ($tierOrder[$r->prize_tier] ?? 99) * 100 + $r->id;
            });

            $tierIndex = [];
            foreach ($sorted as $r) {
                $idx = $tierIndex[$r->prize_tier] ?? 0;
                $results[] = [
                    'tier' => $r->prize_tier,
                    'index' => $idx,
                    'value' => $r->full_number,
                    'loto' => $r->loto_number,
                ];
                $tierIndex[$r->prize_tier] = $idx + 1;
            }
        }

        return response()->json([
            'is_live' => in_array($draw->status, ['updating', 'pending']),
            'date' => $today,
            'status' => $draw->status,
            'results' => $results,
            'total' => count($results),
        ]);
    }



}