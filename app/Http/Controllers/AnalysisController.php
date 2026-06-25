<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Draw;
use App\Models\AnalysisExtraction;
use App\Services\NumberClassificationService;
use App\Services\PartitionGeneratorService;
use App\Services\PatternAnalyzerService;
use App\Services\AnalysisStatisticsService;
use Carbon\Carbon;

class AnalysisController extends Controller
{
    protected $classifier;
    protected $partitioner;
    protected $patternAnalyzer;
    protected $statistics;

    public function __construct()
    {
        $this->classifier = new NumberClassificationService();
        $this->partitioner = new PartitionGeneratorService();
        $this->patternAnalyzer = new PatternAnalyzerService();
        $this->statistics = new AnalysisStatisticsService();
    }

    /**
     * Main analysis dashboard
     */
    public function dashboard(Request $request)
    {
        $selectedDate = $request->input('date');
        $mode = $request->input('mode', 25); // Mặc định là 25 số (4 Bộ)

        if ($selectedDate) {
            $draw = Draw::with('results')->where('draw_date', $selectedDate)->first();
        } else {
            $draw = Draw::with('results')->whereIn('status', ['completed', 'updating'])
                ->orderBy('draw_date', 'desc')->first();
            $selectedDate = $draw ? $draw->draw_date : now()->format('Y-m-d');
        }
        $refDate = is_string($selectedDate) ? $selectedDate : (is_object($selectedDate) ? $selectedDate->format('Y-m-d') : now()->format('Y-m-d'));

        $extraction = null;
        $gdbFull = '';
        $g1Full = '';
        if ($draw) {
            $extraction = AnalysisExtraction::where('draw_id', $draw->id)->first();
            foreach ($draw->results as $result) {
                if ($result->prize_tier === 'GDB') $gdbFull = $result->full_number;
                if ($result->prize_tier === 'G1') $g1Full = $result->full_number;
            }
        }

        $numbersMap = $this->classifier->getNumbersMap();
        $stats = $this->statistics->getDashboardStats(30, $refDate);

        // === 1. TÍNH LÔ GAN ĐUÔI ĐẶC BIỆT (00-99) ===
        $ganGdbTail = [];
        for ($i = 0; $i <= 99; $i++) {
            $ganGdbTail[str_pad($i, 2, '0', STR_PAD_LEFT)] = 0;
        }
        $foundGdbTail = array_fill_keys(array_keys($ganGdbTail), false);
        $foundCount = 0;

        $drawsForGan = AnalysisExtraction::where('draw_date', '<=', $refDate)
            ->orderBy('draw_date', 'desc')
            ->limit(11000)
            ->pluck('gdb_last2');

        foreach ($drawsForGan as $val) {
            if ($val === null || $val === '') continue;
            $valStr = str_pad($val, 2, '0', STR_PAD_LEFT);
            if (!$foundGdbTail[$valStr]) {
                $foundGdbTail[$valStr] = true;
                $foundCount++;
            }
            foreach ($ganGdbTail as $k => $v) {
                if (!$foundGdbTail[$k]) $ganGdbTail[$k]++;
            }
            if ($foundCount == 100) break;
        }
        arsort($ganGdbTail);

        // === 2. KHỐI TÍNH TOÁN MEGA EXCEL KẸP NGÀY THÁNG MAX GAN ===
        $allDrawsForMatrix = AnalysisExtraction::where('draw_date', '<=', $refDate)
            ->orderBy('draw_date', 'desc')
            ->limit(12000)
            ->get();

        $matrixFields = [
            'gdb_first2' => 'ĐẦU ĐẶC BIỆT',
            'gdb_last2'  => 'CUỐI ĐẶC BIỆT',
            'g1_first2'  => 'ĐẦU GIẢI NHẤT',
            'g1_last2'   => 'CUỐI GIẢI NHẤT'
        ];

        // TỰ ĐỘNG CHUYỂN ĐỔI BỘ SỐ THEO CHẾ ĐỘ NÚT BẤM (MODE)
        if ($mode == 20) {
            // Chế độ chia 5 Bộ (mỗi bộ 20 số) - Chuẩn theo bản viết tay
            $strategies = [
                'bong_tong' => ['name' => 'BỘ TỔNG', 'cols' => ['0 - 5', '1 - 6', '2 - 7', '3 - 8', '4 - 9']],
                'dai_so'    => ['name' => 'BỘ DẢI SỐ', 'cols' => ['00-19', '20-39', '40-59', '60-79', '80-99']],
                'bong_dau'  => ['name' => 'ĐẦU SỐ', 'cols' => ['0 - 5', '1 - 6', '2 - 7', '3 - 8', '4 - 9']],
                'bong_duoi' => ['name' => 'ĐUÔI SỐ', 'cols' => ['0 - 5', '1 - 6', '2 - 7', '3 - 8', '4 - 9']],
                'bong_hieu' => ['name' => 'BỘ HIỆU', 'cols' => ['0 - 5', '1 - 9', '2 - 8', '3 - 7', '4 - 6']]
            ];
        } else {
            // Chế độ chia 4 Bộ (mỗi bộ 25 số) - Mặc định cũ
            $strategies = [
                'tn'    => ['name' => 'BỘ TO NHỎ', 'cols' => ['TT', 'NN', 'TN', 'NT']],
                'cl'    => ['name' => 'BỘ CHẴN LẺ', 'cols' => ['CC', 'LL', 'LC', 'CL']],
                'tn_cl' => ['name' => 'TO.NHỎ - CHẴN.LẺ', 'cols' => ['TC', 'TL', 'NC', 'NL']],
                'cl_tn' => ['name' => 'CHẴN.LẺ - TO.NHỎ', 'cols' => ['CT', 'LT', 'CN', 'LN']],
                'mod4'  => ['name' => 'BỘ MOD 4', 'cols' => ['Dư 1', 'Dư 2', 'Dư 3', 'Dư 0']],
                'range' => ['name' => 'BỘ DẢI SỐ', 'cols' => ['00-24', '25-49', '50-74', '75-99']]
            ];
        }

        $excelData = [];
        $numberSets = [];

        foreach ($strategies as $strKey => $strInfo) {
            // === SINH DANH SÁCH SỐ THUỘC TỪNG BỘ ===
            $numberSets[$strKey] = ['name' => $strInfo['name'], 'cols' => $strInfo['cols'], 'sets' => []];
            foreach ($strInfo['cols'] as $col) {
                $numberSets[$strKey]['sets'][$col] = [];
            }
            for ($i = 0; $i < 100; $i++) {
                $valStr = str_pad($i, 2, '0', STR_PAD_LEFT);
                $n = $i;
                $pattern = '-';
                if ($mode == 20) {
                    if ($strKey === 'dai_so')    $pattern = $this->getDaiSo($valStr);
                    if ($strKey === 'bong_dau')  $pattern = $this->getBongDau($valStr);
                    if ($strKey === 'bong_duoi') $pattern = $this->getBongDuoi($valStr);
                    if ($strKey === 'bong_tong') $pattern = $this->getBongTong($valStr);
                    if ($strKey === 'bong_hieu') $pattern = $this->getBongHieu($valStr);
                } else {
                    $h = (int)$valStr[0]; $t = (int)$valStr[1];
                    $h_tn = $h >= 5 ? 'T' : 'N'; $t_tn = $t >= 5 ? 'T' : 'N';
                    $h_cl = $h % 2 === 0 ? 'C' : 'L'; $t_cl = $t % 2 === 0 ? 'C' : 'L';
                    if ($strKey === 'tn')    $pattern = $h_tn . $t_tn;
                    if ($strKey === 'cl')    $pattern = $h_cl . $t_cl;
                    if ($strKey === 'tn_cl') $pattern = $h_tn . $t_cl;
                    if ($strKey === 'cl_tn') $pattern = $h_cl . $t_tn;
                    if ($strKey === 'mod4') {
                        $mod = $n % 4; 
                        $pattern = 'Dư ' . $mod;
                    }
                    if ($strKey === 'range') {
                        if ($n <= 24) $pattern = '00-24'; elseif ($n <= 49) $pattern = '25-49';
                        elseif ($n <= 74) $pattern = '50-74'; else $pattern = '75-99';
                    }
                }
                if (isset($numberSets[$strKey]['sets'][$pattern])) {
                    $numberSets[$strKey]['sets'][$pattern][] = $valStr;
                }
            }

            $excelData[$strKey] = [
                'name' => $strInfo['name'],
                'cols' => $strInfo['cols'],
                'fields' => []
            ];

            foreach ($matrixFields as $fKey => $fName) {
                $gan = array_fill_keys($strInfo['cols'], 0);
                $maxGan = array_fill_keys($strInfo['cols'], 0);
                
                $maxGanDates = array_fill_keys($strInfo['cols'], ''); 
                $ganCyclesHistory = array_fill_keys($strInfo['cols'], []); 
                
                $currentGap = array_fill_keys($strInfo['cols'], 0);
                $foundFirst = array_fill_keys($strInfo['cols'], false);
                $lastSeenHitDate = array_fill_keys($strInfo['cols'], null);
                
                $history = [];

                foreach ($allDrawsForMatrix as $index => $d) {
                    $val = $d->$fKey; 
                    if($val === null || $val === '') continue;

                    $valStr = str_pad($val, 2, '0', STR_PAD_LEFT);
                    $n = (int)$valStr; 

                    $pattern = '-';
                    
                    // RẼ NHÁNH: XÁC ĐỊNH MẪU SỐ DỰA TRÊN CHẾ ĐỘ
                    if ($mode == 20) {
                        if ($strKey === 'dai_so')    $pattern = $this->getDaiSo($valStr);
                        if ($strKey === 'bong_dau')  $pattern = $this->getBongDau($valStr);
                        if ($strKey === 'bong_duoi') $pattern = $this->getBongDuoi($valStr);
                        if ($strKey === 'bong_tong') $pattern = $this->getBongTong($valStr);
                        if ($strKey === 'bong_hieu') $pattern = $this->getBongHieu($valStr);
                    } else {
                        $h = (int)$valStr[0]; $t = (int)$valStr[1]; 
                        $h_tn = $h >= 5 ? 'T' : 'N'; $t_tn = $t >= 5 ? 'T' : 'N';
                        $h_cl = $h % 2 === 0 ? 'C' : 'L'; $t_cl = $t % 2 === 0 ? 'C' : 'L';

                        if ($strKey === 'tn')    $pattern = $h_tn . $t_tn;
                        if ($strKey === 'cl')    $pattern = $h_cl . $t_cl;
                        if ($strKey === 'tn_cl') $pattern = $h_tn . $t_cl;
                        if ($strKey === 'cl_tn') $pattern = $h_cl . $t_tn;
                        if ($strKey === 'mod4') {
                            $mod = $n % 4; 
                            $pattern = 'Dư ' . $mod;
                        }
                        if ($strKey === 'range') {
                            if ($n <= 24) $pattern = '00-24'; elseif ($n <= 49) $pattern = '25-49';
                            elseif ($n <= 74) $pattern = '50-74'; else $pattern = '75-99';
                        }
                    }

                    $currentDateStr = \Carbon\Carbon::parse($d->draw_date)->format('d/m/Y');

                    foreach ($strInfo['cols'] as $col) {
                        if ($pattern === $col) {
                            if (!$foundFirst[$col]) { 
                                $gan[$col] = $currentGap[$col]; 
                                $foundFirst[$col] = true; 
                            } else { 
                                if ($currentGap[$col] > 0) {
                                    $ganCyclesHistory[$col][] = [
                                        'length' => $currentGap[$col],
                                        'from'   => $currentDateStr,
                                        'to'     => $lastSeenHitDate[$col]
                                    ];
                                }

                                if ($currentGap[$col] > $maxGan[$col]) {
                                    $maxGan[$col] = $currentGap[$col];
                                    $maxGanDates[$col] = "{$currentDateStr} - {$lastSeenHitDate[$col]}";
                                }
                            }
                            $lastSeenHitDate[$col] = $currentDateStr;
                            $currentGap[$col] = 0;
                        } else {
                            $currentGap[$col]++;
                        }
                    }

                    if ($index < 30) {
                        $history[] = ['date' => \Carbon\Carbon::parse($d->draw_date)->format('d/m'), 'val' => $valStr, 'pattern' => $pattern];
                    }
                }
                
                foreach ($strInfo['cols'] as $col) {
                    if (!$foundFirst[$col]) $gan[$col] = $currentGap[$col];
                    
                    if ($currentGap[$col] > $maxGan[$col]) {
                        $maxGan[$col] = $currentGap[$col];
                        $maxGanDates[$col] = "Quá 500 kỳ";
                    }

                    if ($gan[$col] > $maxGan[$col]) {
                        $maxGan[$col] = $gan[$col];
                        $start = $lastSeenHitDate[$col] ?? '---';
                        $maxGanDates[$col] = "{$start} - Nay";
                    }
                }

                $excelData[$strKey]['fields'][$fKey] = [
                    'name' => $fName, 
                    'gan' => $gan, 
                    'maxGan' => $maxGan, 
                    'maxGanDates' => $maxGanDates, 
                    'history' => $history,
                    'ganCycles' => $ganCyclesHistory,
                ];
            }
        }

        return view('phan-tich', compact('selectedDate', 'draw', 'extraction', 'gdbFull', 'g1Full', 'numbersMap', 'stats', 'ganGdbTail', 'excelData', 'numberSets'));
    }

    /**
     * API: Get partition data for a strategy
     */
    public function apiStrategy(Request $request, string $key)
    {
        $partition = $this->partitioner->getPartition($key);
        if (!$partition) {
            return response()->json(['error' => 'Strategy not found'], 404);
        }

        $date = $request->input('date');
        $hits = ['A' => [], 'B' => [], 'C' => [], 'D' => []];

        if ($date) {
            $draw = Draw::where('draw_date', $date)->first();
            if ($draw) {
                $ext = AnalysisExtraction::where('draw_id', $draw->id)->first();
                if ($ext) {
                    $numbers = $ext->getAnalysisNumbers();
                    foreach ($partition['sets'] as $label => $nums) {
                        foreach ($numbers as $aN) {
                            if (in_array($aN, $nums)) {
                                $hits[$label][] = $aN;
                            }
                        }
                    }
                }
            }
        }

        $refDate = $date ?: now()->format('Y-m-d');
        $fromDate = Carbon::parse($refDate)->subDays(30)->format('Y-m-d');
        $distribution = $this->statistics->getSetDistribution($partition['sets'], $fromDate, $refDate);
        $hitHistory = $this->statistics->getSetHitHistory($partition['sets'], 30, $refDate);

        return response()->json([
            'partition'    => $partition,
            'hits'         => $hits,
            'distribution' => $distribution,
            'hit_history'  => $hitHistory,
        ]);
    }

    /**
     * API: Get statistics
     */
    public function apiStats(Request $request, int $days = 30)
    {
        $refDate = $request->input('date', now()->format('Y-m-d'));
        $stats = $this->statistics->getDashboardStats($days, $refDate);

        return response()->json($stats);
    }

    /**
     * API: Liệt kê kết quả chi tiết từng ngày trong khoảng thời gian Gan
     */
    public function getRangeStats(Request $request)
    {
        $fromStr = $request->input('from');
        $toStr   = $request->input('to');
        $field   = $request->input('field');  // gdb_first2, gdb_last2, g1_first2, g1_last2

        if (!$fromStr || !$toStr || !$field) {
            return response()->json(['error' => 'Thiếu tham số dữ liệu'], 400);
        }

        // Xử lý ngày tháng
        $from = \Carbon\Carbon::createFromFormat('d/m/Y', $fromStr)->format('Y-m-d');
        $to   = ($toStr === 'hiện tại') ? now()->format('Y-m-d') : \Carbon\Carbon::createFromFormat('d/m/Y', $toStr)->format('Y-m-d');

        // Query lấy thẳng kết quả trong khoảng thời gian này, sắp xếp từ cũ đến mới
        $draws = AnalysisExtraction::whereBetween('draw_date', [$from, $to])
                    ->orderBy('draw_date', 'asc')
                    ->get(['draw_date', $field]);

        $results = [];
        foreach ($draws as $d) {
            $dateFormatted = \Carbon\Carbon::parse($d->draw_date)->format('d/m/Y');
            $val = $d->$field;
            $valStr = ($val !== null && $val !== '') ? str_pad($val, 2, '0', STR_PAD_LEFT) : '--';
            
            $results[] = [
                'date'  => $dateFormatted,
                'value' => $valStr
            ];
        }

        $fieldNames = [
            'gdb_first2' => 'ĐẦU ĐẶC BIỆT (BỘ 12)',
            'gdb_last2'  => 'CUỐI ĐẶC BIỆT (BỘ 45)',
            'g1_first2'  => 'ĐẦU GIẢI NHẤT (BỘ 12)',
            'g1_last2'   => 'CUỐI GIẢI NHẤT (BỘ 45)'
        ];

        return response()->json([
            'fieldName' => $fieldNames[$field] ?? $field,
            'results'   => $results
        ]);
    }

    // =========================================================================
    // CÁC HÀM XÁC ĐỊNH BỘ SỐ CHẾ ĐỘ 20 SỐ (5 BỘ) THEO BẢN VIẾT TAY
    // =========================================================================

    // 1. Chia theo dải số (20 số / dải)
    private function getDaiSo($num) {
        $n = (int)$num;
        if($n <= 19) return '00-19';
        if($n <= 39) return '20-39';
        if($n <= 59) return '40-59';
        if($n <= 79) return '60-79';
        return '80-99';
    }

    // 3. Bóng Đầu
    private function getBongDau($num) {
        $dau = (int)substr(str_pad($num, 2, '0', STR_PAD_LEFT), 0, 1);
        $mod = $dau % 5;
        return $mod . ' - ' . ($mod + 5);
    }

    // 4. Bóng Đuôi
    private function getBongDuoi($num) {
        $duoi = (int)substr(str_pad($num, 2, '0', STR_PAD_LEFT), -1);
        $mod = $duoi % 5;
        return $mod . ' - ' . ($mod + 5);
    }

    // 5. Bóng Tổng (Lấy hàng đơn vị của Tổng)
    private function getBongTong($num) {
        $nStr = str_pad($num, 2, '0', STR_PAD_LEFT);
        $tong = ((int)$nStr[0] + (int)$nStr[1]) % 10;
        $mod = $tong % 5;
        return $mod . ' - ' . ($mod + 5);
    }

    // 6. Bóng Hiệu (Khoảng cách vòng tròn |a-b|)
    private function getBongHieu($num) {
        $nStr = str_pad($num, 2, '0', STR_PAD_LEFT);
        $hieu = abs((int)$nStr[0] - (int)$nStr[1]);
        $cyclic = min($hieu, 10 - $hieu); // Công thức chuẩn khoảng cách vòng tròn
        
        if ($cyclic == 0 || $cyclic == 5) return '0 - 5';
        if ($cyclic == 1) return '1 - 9';
        if ($cyclic == 2) return '2 - 8';
        if ($cyclic == 3) return '3 - 7';
        if ($cyclic == 4) return '4 - 6';
        return '-';
    }
}