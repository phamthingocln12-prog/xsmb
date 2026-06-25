<?php



namespace App\Services;



use PhpOffice\PhpSpreadsheet\Spreadsheet;

use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use App\Models\Draw;  

use App\Models\LotoStatistic;



class ExcelExportService

{

    public function generateOfflineDashboard()

    {

        $spreadsheet = new Spreadsheet();

       

        // --- SHEET 1: LỊCH SỬ QUAY (RAW DATA) ---

        $sheet1 = $spreadsheet->getActiveSheet();

        $sheet1->setTitle('Dữ Liệu Lịch Sử');

       

        $tieuDe1 = [

            'Ngày Quay',

            'Đặc Biệt', 'Lô ĐB (Đề)', 'Đầu Đề', 'Đuôi Đề',

            'Giải 1', 'Lô G1',

            'Giải 2', 'Giải 3', 'Giải 4', 'Giải 5', 'Giải 6', 'Giải 7',

            'Dàn Lô Tô (27 con)'

        ];

        $col = 'A';

        foreach ($tieuDe1 as $header) {

            $sheet1->setCellValue($col . '1', $header);

            $sheet1->getStyle($col . '1')->getFont()->setBold(true);

            // Thêm màu nền xanh lá cho tiêu đề giống web xổ số

            $sheet1->getStyle($col . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)

                   ->getStartColor()->setARGB('FF4CAF50');

            $sheet1->getStyle($col . '1')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);

            $col++;

        }

       

        $danhSachKyQuay = Draw::with('results')->where('status', 'completed')->orderBy('draw_date', 'asc')->get();

        $row = 2;

        foreach ($danhSachKyQuay as $kyQuay) {

            if ($kyQuay->results->count() < 27) continue;

           

            $nhomGiai = $kyQuay->results->groupBy('prize_tier');

            $gdb = isset($nhomGiai['GDB']) ? $nhomGiai['GDB']->first()->full_number : '';

            $loDB = strlen($gdb) >= 2 ? substr($gdb, -2) : '';

            $dauDe = strlen($loDB) == 2 ? substr($loDB, 0, 1) : '';

            $duoiDe = strlen($loDB) == 2 ? substr($loDB, -1) : '';



            $g1 = isset($nhomGiai['G1']) ? $nhomGiai['G1']->first()->full_number : '';

            $loG1 = strlen($g1) >= 2 ? substr($g1, -2) : '';



            $danLoTo = $kyQuay->results->pluck('full_number')->map(function($so) {

                return strlen($so) >= 2 ? substr($so, -2) : '';

            })->filter()->implode(' - ');

           

            $dongDuLieu = [

                \Carbon\Carbon::parse($kyQuay->draw_date)->format('d/m/Y'),

                $gdb, $loDB, $dauDe, $duoiDe,

                $g1, $loG1,

                isset($nhomGiai['G2']) ? $nhomGiai['G2']->pluck('full_number')->implode(' - ') : '',

                isset($nhomGiai['G3']) ? $nhomGiai['G3']->pluck('full_number')->implode(' - ') : '',

                isset($nhomGiai['G4']) ? $nhomGiai['G4']->pluck('full_number')->implode(' - ') : '',

                isset($nhomGiai['G5']) ? $nhomGiai['G5']->pluck('full_number')->implode(' - ') : '',

                isset($nhomGiai['G6']) ? $nhomGiai['G6']->pluck('full_number')->implode(' - ') : '',

                isset($nhomGiai['G7']) ? $nhomGiai['G7']->pluck('full_number')->implode(' - ') : '',

                $danLoTo

            ];

           

            $col = 'A';

            foreach ($dongDuLieu as $data) {

                $sheet1->setCellValueExplicit($col . $row, $data, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                $col++;

            }

            $row++;

        }

        foreach (range('A', 'N') as $colId) {

            $sheet1->getColumnDimension($colId)->setAutoSize(true);

        }

        $sheet1->setAutoFilter('A1:N' . ($row - 1));



        // --- SHEET 2: LÔ TÔ (00-99) & LÔ GAN ---

        $sheet2 = $spreadsheet->createSheet();

        $sheet2->setTitle('Thống Kê Lô Gan 00-99');

        $tieuDe2 = ['Số (00-99)', 'Tần Suất (Tổng Số Lần Về)', 'Số Lần Về (30 Ngày Gần Nhất)', 'Ngày Về Gần Nhất', 'Gan Hiện Tại (Số Ngày Chưa Về)', 'Gan Cực Đại (Bao Giờ Về Lâu Nhất)'];

        $col = 'A';

        foreach ($tieuDe2 as $header) {

            $sheet2->setCellValue($col . '1', $header);

            $sheet2->getStyle($col . '1')->getFont()->setBold(true);

            $sheet2->getStyle($col . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)

                   ->getStartColor()->setARGB('FFE91E63'); // Màu hồng nổi bật

            $sheet2->getStyle($col . '1')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);

            $col++;

        }



        // Tần suất 30 ngày gần nhất

        $recent30DrawsCount = Draw::where('status', 'completed')->orderBy('draw_date', 'desc')->limit(30)->count();

        $freq30 = [];

        if ($recent30DrawsCount > 0) {

            $recent30Draws = Draw::with('results')->where('status', 'completed')->orderBy('draw_date', 'desc')->limit(30)->get();

            foreach ($recent30Draws as $draw) {

                if ($draw->results) {

                    foreach ($draw->results as $r) {

                        $freq30[$r->loto_number] = ($freq30[$r->loto_number] ?? 0) + 1;

                    }

                }

            }

        }

   

        $stats = LotoStatistic::orderBy('loto_number')->get();

        $row2 = 2;

        foreach ($stats as $stat) {

            $lotoNum = $stat->loto_number;

            $freq30Count = $freq30[$lotoNum] ?? 0;

           

            $sheet2->setCellValueExplicit('A' . $row2, $lotoNum, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

            $sheet2->setCellValue('B' . $row2, $stat->total_appearances);

            $sheet2->setCellValue('C' . $row2, $freq30Count);

            $sheet2->setCellValue('D' . $row2, \Carbon\Carbon::parse($stat->last_appeared_date)->format('d/m/Y'));

            $sheet2->setCellValue('E' . $row2, $stat->current_gan_days);

            $sheet2->setCellValue('F' . $row2, $stat->max_gan_days);

           

            // Highlight màu đỏ nếu là Lô Gan > 15 ngày

            if ($stat->current_gan_days > 15) {

                $sheet2->getStyle('E' . $row2)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);

                $sheet2->getStyle('E' . $row2)->getFont()->setBold(true);

            }

            // Highlight màu xanh lá nếu là Số Nóng (về > 8 lần / 30 ngày)

            if ($freq30Count >= 8) {

                $sheet2->getStyle('C' . $row2)->getFont()->getColor()->setARGB('FF4CAF50');

                $sheet2->getStyle('C' . $row2)->getFont()->setBold(true);

            }

            $row2++;

        }

        foreach (range('A', 'F') as $colId) {

            $sheet2->getColumnDimension($colId)->setAutoSize(true);

        }

        $sheet2->setAutoFilter('A1:F' . ($row2 - 1));

        // Sắp xếp tự động ở Sheet này giảm dần theo Gan hiện tại bằng cách add Rule Filter (nếu muốn, nhưng để simple cho Excel thì cứ set auto filter)



        // --- SHEET 3: THỐNG KÊ ĐẦU ĐUÔI ---

        $sheet3 = $spreadsheet->createSheet();

        $sheet3->setTitle('Thống Kê Đầu Đuôi');

        $tieuDe3 = ['Số (0-9)', 'Tần Suất Bấm Đầu', 'Tần Suất Bấm Đuôi'];

        $col = 'A';

        foreach ($tieuDe3 as $header) {

            $sheet3->setCellValue($col . '1', $header);

            $sheet3->getStyle($col . '1')->getFont()->setBold(true);

            $sheet3->getStyle($col . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)

                   ->getStartColor()->setARGB('FF2196F3'); // Màu xanh dương

            $sheet3->getStyle($col . '1')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);

            $col++;

        }

       

        $heads = array_fill(0, 10, 0);

        $tails = array_fill(0, 10, 0);

        foreach($stats as $stat) {

            $head = substr($stat->loto_number, 0, 1);

            $tail = substr($stat->loto_number, 1, 1);

            $heads[$head] += $stat->total_appearances;

            $tails[$tail] += $stat->total_appearances;

        }

       

        $row3 = 2;

        for ($i = 0; $i <= 9; $i++) {

            $sheet3->setCellValueExplicit('A' . $row3, (string)$i, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

            $sheet3->setCellValue('B' . $row3, $heads[$i]);

            $sheet3->setCellValue('C' . $row3, $tails[$i]);

            $row3++;

        }

        foreach (range('A', 'C') as $colId) {

            $sheet3->getColumnDimension($colId)->setAutoSize(true);

        }



        // Kích hoạt lại Sheet 1 (Lịch Sử Quay) khi người dùng mở file Excel

        $spreadsheet->setActiveSheetIndex(0);



        $duongDanFile = public_path('DuLieu_XSMB.xlsx');

        $writer = new Xlsx($spreadsheet);

        $writer->save($duongDanFile);

       

        return $duongDanFile;

    }

}

