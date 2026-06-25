@extends('layouts.app')

@section('content')
@php
/** @var \App\Models\Draw|null $latestDraw */
/** @var array $groupedResults */
/** @var string $selectedDate */
/** @var array $dauStats */
/** @var array $duoiStats */
/** @var string $gdbLoto */
/** @var \Illuminate\Support\Collection $loGanTop */
/** @var \Illuminate\Support\Collection $hotNumbers */
/** @var \Illuminate\Support\Collection $coldNumbers */
/** @var array $suggestions */
/** @var string $tomorrowDate */
/** @var array $cauLoto */
/** @var array $cauDB */
/** @var array $cau2Nhay */
/** @var array $tomorrowPrediction */
/** @var array $ganAlert */
@endphp
@php
    $selectedCarbon = \Carbon\Carbon::parse($selectedDate);
    $currentMonth = $selectedCarbon->month;
    $currentYear = $selectedCarbon->year;
    
    $startOfMonth = $selectedCarbon->copy()->startOfMonth();
    $daysInMonth = $startOfMonth->daysInMonth;
    $dayOfWeek = $startOfMonth->dayOfWeekIso;
@endphp

<style>
    /* CSS cho bộ đếm ngược và Tabs */
    .nav-tabs-custom { display: flex; border-bottom: 2px solid #ed1c24; margin-bottom: 20px; }
    .nav-tabs-custom a { flex: 1; text-align: center; padding: 12px 0; font-weight: bold; color: #555; text-decoration: none; background: #f8f9fa; border: 1px solid #ddd; border-bottom: none; }
    .nav-tabs-custom a.active { background: #ed1c24; color: #fff; border-color: #ed1c24; }
    .countdown-container { text-align: center; margin-bottom: 20px; }
    .cd-box { background-color: #cc0000; color: white; font-size: 1.5rem; font-weight: bold; padding: 5px 12px; border-radius: 4px; display: inline-block; margin: 0 5px; }
    .cd-label { font-size: 1rem; font-weight: normal; }

    /* Lô Gan animations */
    .gan-item { transition: transform 0.2s; cursor: default; }
    .gan-item:hover { transform: scale(1.15); }
    .gan-blink .gan-number { animation: ganBlink 1s ease-in-out infinite; }
    .gan-pulse .gan-number { animation: ganPulse 0.6s ease-in-out infinite; box-shadow: 0 0 12px rgba(220,53,69,0.6); }

    @keyframes ganBlink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }
    @keyframes ganPulse {
        0%, 100% { transform: scale(1); box-shadow: 0 0 8px rgba(220,53,69,0.4); }
        50% { transform: scale(1.12); box-shadow: 0 0 20px rgba(220,53,69,0.8); }
    }

    /* Hiệu ứng nảy số mới */
    .prize-new { animation: prizeNew 0.5s ease; }
    @keyframes prizeNew {
        0% { transform: scale(0.3); opacity: 0; }
        60% { transform: scale(1.2); }
        100% { transform: scale(1); opacity: 1; }
    }

    /* === LIVE DRAW ROLLING ANIMATION === */
    .prize-slot.rolling {
        color: #999 !important;
        position: relative;
        overflow: hidden;
    }
    .prize-slot.rolling::after {
        content: '';
        position: absolute;
        top: 0; left: -100%;
        width: 200%; height: 100%;
background: linear-gradient(90deg, transparent, rgba(220,53,69,0.08), transparent);
        animation: rollingSweep 0.8s linear infinite;
    }
    @keyframes rollingSweep {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    .prize-slot.revealed {
        animation: revealFlash 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }
    @keyframes revealFlash {
        0% { transform: scale(1.4); opacity: 0.3; background: #fff3cd; }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); opacity: 1; background: transparent; }
    }
    .live-title-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .live-title-dot {
        width: 10px; height: 10px;
        border-radius: 50%;
        background: #dc3545;
        animation: liveTitlePulse 1s ease-in-out infinite;
    }
    @keyframes liveTitlePulse {
        0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(220,53,69,0.6); }
        50% { opacity: 0.5; box-shadow: 0 0 0 6px rgba(220,53,69,0); }
    }

    /* Gan Alert animations */
    @keyframes ganAlertPulse {
        0%, 100% { transform: scale(1); box-shadow: 0 6px 30px rgba(213,0,0,0.5); }
        50% { transform: scale(1.04); box-shadow: 0 8px 40px rgba(213,0,0,0.7); }
    }

    /* Hiệu ứng bôi xanh số được chọn từ trang Phân Tích */
    .highlight-number {
        background-color: #198754 !important;
        color: #fff !important;
        padding: 2px 10px;
        border-radius: 6px;
        box-shadow: 0 0 12px rgba(25,135,84,0.7);
        display: inline-block;
        font-weight: 900;
        transform: scale(1.15);
        transition: all 0.3s ease;
        animation: pulseHighlight 1.5s infinite;
    }
    @keyframes pulseHighlight {
        0%, 100% { box-shadow: 0 0 8px rgba(25,135,84,0.5); }
        50% { box-shadow: 0 0 18px rgba(25,135,84,0.9); }
    }

    /* Calendar warning icon cho ngày chưa có dữ liệu */
    .cal-warn {
        font-size: 11px;
        line-height: 1;
        color: #e6a817;
        filter: drop-shadow(0 0 1px rgba(230,168,23,0.5));
        animation: calWarnPulse 2s ease-in-out infinite;
        pointer-events: none;
    }
    @keyframes calWarnPulse {
        0%, 100% { opacity: 0.7; transform: translateX(-50%) scale(1); }
        50% { opacity: 1; transform: translateX(-50%) scale(1.15); }
    }


</style>

<div class="row">
    <div class="col-lg-8 mb-4">
        
        <div class="card shadow-sm border-0 mb-3">
            <div class="nav-tabs-custom">
                <a href="#" class="active">Trực tiếp XSMB</a>
            </div>
            
            <div class="card-body pt-0 pb-2">
                <div class="d-flex justify-content-center align-items-center gap-2 mb-2">
                    <div id="countdown-wrapper" class="countdown-container d-flex justify-content-center align-items-center">
                        <span class="fs-5 me-2">Còn</span>
                        <div class="cd-box"><span id="cd-hours">00</span> <span class="cd-label">giờ</span></div>
                        <div class="cd-box"><span id="cd-minutes">00</span> <span class="cd-label">phút</span></div>
                        <div class="cd-box"><span id="cd-seconds">00</span> <span class="cd-label">giây</span></div>
                    </div>

                </div>

                <div id="text-summary" class="text-muted text-start" style="font-size: 15px;">
Là tới giờ quay <strong>xổ số Miền Bắc trực tiếp</strong>, chúng tôi sẽ tường thuật kết quả XSMB ngay sau khi hội đồng xổ số bắt đầu.... 
                    <div class="text-end mt-1">
                        <a href="javascript:void(0)" onclick="toggleText()" class="text-secondary text-decoration-none fst-italic">...Đọc tiếp &#9652;</a>
                    </div>
                </div>

                <div id="text-full" class="text-muted text-start d-none" style="font-size: 15px;">
                    Là tới giờ quay <strong>xổ số Miền Bắc trực tiếp</strong>, chúng tôi sẽ tường thuật kết quả XSMB ngay sau khi hội đồng xổ số bắt đầu.
                    <br><br>
                    <strong>Truc tiep XSMB</strong> – Tường thuật <strong>trực tiếp Xổ số miền Bắc</strong> tại trường quay lúc 18h15 hàng ngày, từng giải Nhanh và chính xác, <strong>XSMB trực tiếp</strong>.
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="fst-italic">Chúc bạn may mắn!</span>
                        <a href="javascript:void(0)" onclick="toggleText()" class="text-secondary text-decoration-none fst-italic">Thu gọn &#9652;</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white text-center">
                <h4 class="text-danger fw-bold m-0" id="resultTitle">
                    <span id="liveIndicator" class="live-title-indicator d-none">
                        <span class="live-title-dot"></span>
                        <span style="color:#dc3545;font-size:0.75rem;font-weight:700;">LIVE</span>
                    </span>
                    KẾT QUẢ XỔ SỐ MIỀN BẮC
                    @if($latestDraw)
                        - Ngày {{ \Carbon\Carbon::parse($latestDraw->draw_date)->format('d/m/Y') }}
                    @else
                        - Ngày {{ $selectedCarbon->format('d/m/Y') }} (Chưa có)
                    @endif
                </h4>
            </div>
            
            <div class="card-body p-0">
                <table class="table table-bordered table-xsmb m-0">
                    <tbody>
                        <tr id="row-GDB"><th>Đặc biệt</th><td class="gdb-text prize-slot text-danger" data-tier="GDB" data-index="0" data-digits="5">{{ $groupedResults['GDB'][0] ?? '...' }}</td></tr>
                        <tr id="row-G1"><th>Giải Nhất</th><td class="prize-slot" data-tier="G1" data-index="0" data-digits="5">{{ $groupedResults['G1'][0] ?? '...' }}</td></tr>
<tr id="row-G2"><th>Giải Nhì</th><td><div class="row"><div class="col-6 prize-slot" data-tier="G2" data-index="0" data-digits="5">{{ $groupedResults['G2'][0] ?? '...' }}</div><div class="col-6 prize-slot" data-tier="G2" data-index="1" data-digits="5">{{ $groupedResults['G2'][1] ?? '...' }}</div></div></td></tr>
                        <tr id="row-G3"><th>Giải Ba</th><td>
                            <div class="row mb-2">
                                <div class="col-4 prize-slot" data-tier="G3" data-index="0" data-digits="5">{{ $groupedResults['G3'][0] ?? '...' }}</div><div class="col-4 prize-slot" data-tier="G3" data-index="1" data-digits="5">{{ $groupedResults['G3'][1] ?? '...' }}</div><div class="col-4 prize-slot" data-tier="G3" data-index="2" data-digits="5">{{ $groupedResults['G3'][2] ?? '...' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-4 prize-slot" data-tier="G3" data-index="3" data-digits="5">{{ $groupedResults['G3'][3] ?? '...' }}</div><div class="col-4 prize-slot" data-tier="G3" data-index="4" data-digits="5">{{ $groupedResults['G3'][4] ?? '...' }}</div><div class="col-4 prize-slot" data-tier="G3" data-index="5" data-digits="5">{{ $groupedResults['G3'][5] ?? '...' }}</div>
                            </div>
                        </td></tr>
                        <tr id="row-G4"><th>Giải Tư</th><td><div class="row"><div class="col-3 prize-slot" data-tier="G4" data-index="0" data-digits="4">{{ $groupedResults['G4'][0] ?? '...' }}</div><div class="col-3 prize-slot" data-tier="G4" data-index="1" data-digits="4">{{ $groupedResults['G4'][1] ?? '...' }}</div><div class="col-3 prize-slot" data-tier="G4" data-index="2" data-digits="4">{{ $groupedResults['G4'][2] ?? '...' }}</div><div class="col-3 prize-slot" data-tier="G4" data-index="3" data-digits="4">{{ $groupedResults['G4'][3] ?? '...' }}</div></div></td></tr>
                        <tr id="row-G5"><th>Giải Năm</th><td>
                            <div class="row mb-2">
                                <div class="col-4 prize-slot" data-tier="G5" data-index="0" data-digits="4">{{ $groupedResults['G5'][0] ?? '...' }}</div><div class="col-4 prize-slot" data-tier="G5" data-index="1" data-digits="4">{{ $groupedResults['G5'][1] ?? '...' }}</div><div class="col-4 prize-slot" data-tier="G5" data-index="2" data-digits="4">{{ $groupedResults['G5'][2] ?? '...' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-4 prize-slot" data-tier="G5" data-index="3" data-digits="4">{{ $groupedResults['G5'][3] ?? '...' }}</div><div class="col-4 prize-slot" data-tier="G5" data-index="4" data-digits="4">{{ $groupedResults['G5'][4] ?? '...' }}</div><div class="col-4 prize-slot" data-tier="G5" data-index="5" data-digits="4">{{ $groupedResults['G5'][5] ?? '...' }}</div>
</div>
                        </td></tr>
                        <tr id="row-G6"><th>Giải Sáu</th><td><div class="row"><div class="col-4 prize-slot" data-tier="G6" data-index="0" data-digits="3">{{ $groupedResults['G6'][0] ?? '...' }}</div><div class="col-4 prize-slot" data-tier="G6" data-index="1" data-digits="3">{{ $groupedResults['G6'][1] ?? '...' }}</div><div class="col-4 prize-slot" data-tier="G6" data-index="2" data-digits="3">{{ $groupedResults['G6'][2] ?? '...' }}</div></div></td></tr>
                        <tr id="row-G7"><th>Giải Bảy</th><td><div class="row"><div class="col-3 prize-slot text-danger fw-bold" data-tier="G7" data-index="0" data-digits="2">{{ $groupedResults['G7'][0] ?? '...' }}</div><div class="col-3 prize-slot text-danger fw-bold" data-tier="G7" data-index="1" data-digits="2">{{ $groupedResults['G7'][1] ?? '...' }}</div><div class="col-3 prize-slot text-danger fw-bold" data-tier="G7" data-index="2" data-digits="2">{{ $groupedResults['G7'][2] ?? '...' }}</div><div class="col-3 prize-slot text-danger fw-bold" data-tier="G7" data-index="3" data-digits="2">{{ $groupedResults['G7'][3] ?? '...' }}</div></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Bảng Thống Kê Đầu - Đuôi của kỳ quay --}}
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-danger text-white fw-bold text-center py-2">
                THỐNG KÊ ĐẦU - ĐUÔI
            </div>
            <div class="card-body p-0">
                <div class="row g-0">
                    {{-- Bảng Đầu --}}
                    <div class="col-6 border-end">
                        <table class="table table-bordered table-sm m-0 text-center">
                            <thead class="bg-light">
                                <tr><th style="width:15%">Đầu</th><th>Lô tô</th><th style="width:15%">SL</th></tr>
                            </thead>
                            <tbody>
                                @for($i = 0; $i <= 9; $i++)
                                <tr>
                                    <td class="fw-bold text-danger">{{ $i }}</td>
                                    <td class="text-start ps-2">
                                        @foreach($dauStats[$i] as $num)
                                            <span class="badge me-1 {{ $num == $gdbLoto ? 'bg-danger text-white' : 'bg-light text-dark border' }}">{{ $num }}</span>
                                        @endforeach
                                    </td>
                                    <td><span class="badge bg-danger">{{ count($dauStats[$i]) }}</span></td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                    {{-- Bảng Đuôi --}}
                    <div class="col-6">
<table class="table table-bordered table-sm m-0 text-center">
                            <thead class="bg-light">
                                <tr><th style="width:15%">Đuôi</th><th>Lô tô</th><th style="width:15%">SL</th></tr>
                            </thead>
                            <tbody>
                                @for($i = 0; $i <= 9; $i++)
                                <tr>
                                    <td class="fw-bold text-primary">{{ $i }}</td>
                                    <td class="text-start ps-2">
                                        @foreach($duoiStats[$i] as $num)
                                            <span class="badge me-1 {{ $num == $gdbLoto ? 'bg-danger text-white' : 'bg-light text-dark border' }}">{{ $num }}</span>
                                        @endforeach
                                    </td>
                                    <td><span class="badge bg-primary">{{ count($duoiStats[$i]) }}</span></td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- LÔ GAN --}}
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-danger text-white fw-bold text-center py-1" style="font-size: 14px;">
                LÔ GAN - Các số lâu chưa về
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-sm table-striped m-0 text-center" style="font-size: 13px;">
                    <thead>
                        <tr class="bg-light">
                            <th style="width:10%">STT</th>
                            <th style="width:20%">Số</th>
                            <th style="width:25%">Số ngày gan</th>
                            <th style="width:25%">Gan cực đại</th>
                            <th style="width:20%">Lần cuối</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($loGanTop as $i => $gan)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-bold text-danger" style="font-size: 16px;">{{ $gan->loto_number }}</td>
                            <td><strong class="text-danger">{{ $gan->current_gan_days }}</strong> ngày</td>
                            <td>{{ $gan->max_gan_days }} ngày</td>
                            <td style="font-size: 12px;">{{ $gan->last_appeared_date ? \Carbon\Carbon::parse($gan->last_appeared_date)->format('d/m/Y') : '-' }}</td>
                        </tr>
                        @endforeach
                        @if(count($loGanTop) == 0)
                        <tr><td colspan="5" class="text-muted">Chưa có dữ liệu</td></tr>
                        @endif
</tbody>
                </table>
            </div>
        </div>

        {{-- SỐ NÓNG / SỐ LẠNH --}}
        <div class="row mt-3 g-2">
            <div class="col-6">
                <div class="card shadow-sm border-danger h-100">
                    <div class="card-header bg-danger text-white fw-bold text-center py-1" style="font-size: 14px;">
                        Số về nhiều nhất
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered table-sm m-0 text-center" style="font-size: 13px;">
                            <thead><tr class="bg-light"><th>Số</th><th>Số lần</th></tr></thead>
                            <tbody>
                                @foreach($hotNumbers as $hot)
                                <tr>
                                    <td class="fw-bold text-danger" style="font-size: 15px;">{{ $hot->loto_number }}</td>
                                    <td>{{ $hot->total_appearances }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card shadow-sm border-secondary h-100">
                    <div class="card-header bg-secondary text-white fw-bold text-center py-1" style="font-size: 14px;">
                        Số về ít nhất
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered table-sm m-0 text-center" style="font-size: 13px;">
                            <thead><tr class="bg-light"><th>Số</th><th>Số lần</th></tr></thead>
                            <tbody>
                                @foreach($coldNumbers as $cold)
                                <tr>
                                    <td class="fw-bold" style="font-size: 15px;">{{ $cold->loto_number }}</td>
                                    <td>{{ $cold->total_appearances }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- GỢI Ý SỐ --}}
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-danger text-white fw-bold text-center py-1" style="font-size: 14px;">
                GỢI Ý SỐ - Ngày {{ $tomorrowDate }}
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-sm m-0 text-center" style="font-size: 13px;">
                    <thead>
                        <tr class="bg-light">
                            <th style="width:20%">Loại</th>
                            <th style="width:45%">Số gợi ý</th>
<th style="width:35%">Lý do</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($suggestions as $group)
                        <tr>
                            <td class="fw-bold text-danger">{{ $group['label'] }}</td>
                            <td>
                                @foreach($group['numbers'] as $num)
                                    <span class="fw-bold text-danger" style="font-size: 17px; margin: 0 4px;">{{ $num }}</span>
                                @endforeach
                            </td>
                            <td class="text-muted" style="font-size: 12px;">{{ $group['reason'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="text-center py-1 bg-light text-muted" style="font-size: 11px;">
                    * Gợi ý dựa trên thống kê, chỉ mang tính tham khảo
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-danger text-white fw-bold py-3 fs-5">
                KẾT QUẢ THEO NGÀY
            </div>
            <div class="card-body p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <a href="?date={{ $selectedCarbon->copy()->subMonth()->format('Y-m-d') }}" class="btn btn-outline-secondary px-3 fw-bold">&lt;</a>
                    
                    <select id="cal-month" class="form-select w-auto fw-bold" onchange="changeMonthYear()">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $currentMonth == $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endfor
                    </select>

                    <select id="cal-year" class="form-select w-auto fw-bold" onchange="changeMonthYear()">
                        @for($y = now()->year; $y >= now()->year - 30; $y--)
                            <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>

                    <a href="?date={{ now()->format('Y-m-d') }}" class="btn btn-light border fw-bold">Hôm nay</a>
                    <a href="?date={{ $selectedCarbon->copy()->addMonth()->format('Y-m-d') }}" class="btn btn-outline-secondary px-3 fw-bold">&gt;</a>
                </div>

                <table class="table table-bordered text-center mb-0 calendar-grid">
                    <thead class="bg-light">
                        <tr><th>Hai</th><th>Ba</th><th>Tư</th><th>Năm</th><th>Sáu</th><th>Bảy</th><th>C.N</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                        @php $dayCount = 1; @endphp
@for($i = 1; $i <= 42; $i++)
                            @if($i >= $dayOfWeek && $dayCount <= $daysInMonth)
                                @php
                                    $loopDate = \Carbon\Carbon::createFromDate($currentYear, $currentMonth, $dayCount)->format('Y-m-d');
                                    $isSelected = $loopDate == $selectedDate;
                                    $isToday = $loopDate == now()->format('Y-m-d');
                                    $isPast = $loopDate <= now()->format('Y-m-d');
                                    $hasData = in_array($loopDate, $datesWithData);
                                @endphp
                                <td class="p-0 {{ $isSelected ? 'bg-warning' : '' }}" style="height: 60px; vertical-align: middle;">
                                    <a href="?date={{ $loopDate }}" class="d-block w-100 h-100 text-decoration-none text-dark fs-5 position-relative pt-2">
                                        {{ $dayCount }}
                                        @if($isToday) <span class="position-absolute top-0 end-0 mt-1 me-1 badge bg-danger" style="font-size: 0.4rem;">&bull;</span> @endif
                                        @if($isPast && !$hasData)
                                            <span class="position-absolute bottom-0 start-50 translate-middle-x mb-1 cal-warn" title="Chưa có dữ liệu">⚠</span>
                                        @endif
                                    </a>
                                </td>
                                @php $dayCount++; @endphp
                            @else
                                <td class="bg-light"></td>
                            @endif
                            @if($i % 7 == 0)
                                </tr>
                                @if($dayCount > $daysInMonth) @break @endif
                                <tr>
                            @endif
                        @endfor
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- CẦU LOTO ĐẸP NHẤT --}}
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-danger text-white fw-bold text-center py-1" style="font-size: 14px;">
                CẦU LOTO ĐẸP NHẤT
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-sm m-0 text-center" style="font-size: 13px;">
                    <thead><tr class="bg-light"><th>Số</th><th>Về liên tiếp</th></tr></thead>
                    <tbody>
                        @forelse($cauLoto as $cau)
                        <tr>
                            <td class="fw-bold text-danger" style="font-size: 16px;">{{ $cau['number'] }}</td>
                            <td>{{ $cau['streak'] }} ngày liên tiếp</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="text-muted">Chưa có cầu đẹp</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- CẦU ĐẶC BIỆT ĐẸP NHẤT --}}
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-danger text-white fw-bold text-center py-1" style="font-size: 14px;">
                CẦU ĐẶC BIỆT ĐẸP NHẤT
</div>
            <div class="card-body p-0">
                <table class="table table-bordered table-sm m-0 text-center" style="font-size: 13px;">
                    <thead><tr class="bg-light"><th>Số ĐB</th><th>Xuất hiện</th></tr></thead>
                    <tbody>
                        @forelse($cauDB as $cau)
                        <tr>
                            <td class="fw-bold text-danger" style="font-size: 16px;">{{ $cau['number'] }}</td>
                            <td>{{ $cau['count'] }} lần / 10 kỳ</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="text-muted">Chưa có dữ liệu</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- CẦU 2 NHÁY ĐẸP NHẤT --}}
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-danger text-white fw-bold text-center py-1" style="font-size: 14px;">
                CẦU 2 NHÁY ĐẸP NHẤT
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-sm m-0 text-center" style="font-size: 13px;">
                    <thead><tr class="bg-light"><th>Số</th><th>Ngày về 2 nháy</th></tr></thead>
                    <tbody>
                        @forelse($cau2Nhay as $cau)
                        <tr>
                            <td class="fw-bold text-danger" style="font-size: 16px;">{{ $cau['number'] }}</td>
                            <td>{{ \Carbon\Carbon::parse($cau['date'])->format('d/m/Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="text-muted">Chưa có dữ liệu</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ========== DỰ ĐOÁN NGÀY MAI ========== --}}
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm" style="overflow: hidden;">
            <div class="card-header bg-danger text-white fw-bold text-center py-3" style="font-size: 18px; letter-spacing: 1px;">
                DỰ ĐOÁN LÔ ĐỀ NGÀY MAI — {{ $tomorrowPrediction['date'] }} ({{ ucfirst($tomorrowPrediction['dow']) }})
            </div>
            <div class="card-body p-0">
                <div class="row g-0">
                    {{-- TOP DỰ ĐOÁN (điểm cao nhất) --}}
                    <div class="col-lg-7 border-end">
                        <div class="p-3">
                            <h6 class="fw-bold mb-2 text-danger">
                                TOP SỐ ĐÁNG CHÚ Ý <small class="text-muted">(xếp theo điểm phân tích)</small>
                            </h6>
                            @if(count($tomorrowPrediction['top']) > 0)
                            <div class="table-responsive">
<table class="table table-sm table-bordered mb-0 text-center" style="font-size: 13px;">
                                    <thead>
                                        <tr class="bg-light">
                                            <th style="width:8%">#</th>
                                            <th style="width:15%">Số</th>
                                            <th style="width:15%">Điểm</th>
                                            <th>Lý do</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tomorrowPrediction['top'] as $i => $item)
                                        <tr>
                                            <td class="{{ $i < 3 ? 'fw-bold text-danger' : '' }}">
                                                {{ $i + 1 }}
                                            
                                            </td>
                                            <td>
                                                <span class="fw-bold text-danger" style="font-size: {{ $i < 3 ? '20px' : '16px' }};">
                                                    {{ $item['number'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center justify-content-center gap-1">
                                                    <span class="fw-bold text-danger">{{ $item['score'] }}đ</span>
                                                </div>
                                            </td>
                                            <td class="text-start" style="font-size: 12px;">
                                                @foreach($item['reasons'] as $reason)
                                                    <span class="badge bg-light text-dark border me-1" style="font-size: 11px;">{{ $reason }}</span>
                                                @endforeach
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <p class="text-muted text-center py-3">Chưa đủ dữ liệu để phân tích</p>
                            @endif
                        </div>
                    </div>

                    {{-- CỘT PHẢI: ĐB + Đầu Đuôi nóng --}}
                    <div class="col-lg-5">
                        {{-- Dự đoán ĐB --}}
                        <div class="p-3 border-bottom">
                            <h6 class="fw-bold mb-2 text-danger">Dự đoán Đặc Biệt</h6>
<div class="d-flex gap-3 justify-content-center">
                                @forelse($tomorrowPrediction['gdb'] as $g)
                                <div class="text-center">
                                    <div style="width:52px; height:52px; line-height:52px; border-radius:50%; background:#dc3545; color:#fff; font-size:22px; font-weight:900; margin:0 auto;">
                                        {{ $g['number'] }}
                                    </div>
                                    <div style="font-size:11px; color:#888; margin-top:4px;">{{ $g['reason'] }}</div>
                                </div>
                                @empty
                                <span class="text-muted">Chưa có dữ liệu</span>
                                @endforelse
                            </div>
                        </div>

                        {{-- Đầu đuôi nóng --}}
                        <div class="p-3 border-bottom">
                            <h6 class="fw-bold mb-2 text-danger">Đầu - Đuôi nóng (7 ngày)</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="text-center mb-1 text-danger" style="font-size:12px; font-weight:700;">Đầu nóng</div>
                                    <div class="d-flex justify-content-center gap-2">
                                        @foreach($tomorrowPrediction['hotHeads'] as $h)
                                        <div style="width:36px; height:36px; line-height:36px; border-radius:50%; background:#f8d7da; color:#dc3545; font-weight:900; font-size:16px; text-align:center; border:1px solid #dc3545;">
                                            {{ $h }}
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center mb-1" style="font-size:12px; font-weight:700; color:#333;">Đuôi nóng</div>
                                    <div class="d-flex justify-content-center gap-2">
                                        @foreach($tomorrowPrediction['hotTails'] as $t)
                                        <div style="width:36px; height:36px; line-height:36px; border-radius:50%; background:#f5f5f5; color:#333; font-weight:900; font-size:16px; text-align:center; border:1px solid #999;">
                                            {{ $t }}
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Số hay về theo thứ --}}
                        <div class="p-3">
<h6 class="fw-bold mb-2">Hay về vào {{ ucfirst($tomorrowPrediction['dow']) }} <small class="text-muted">({{ $tomorrowPrediction['dowCount'] }} kỳ)</small></h6>
                            <div class="d-flex flex-wrap gap-2 justify-content-center">
                                @foreach($tomorrowPrediction['topDow'] as $d)
                                <span style="display:inline-block; width:42px; height:42px; line-height:42px; border-radius:8px; background:#dc3545; color:#fff; font-size:18px; font-weight:900; text-align:center;">
                                    {{ $d }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center py-1 bg-light text-muted" style="font-size: 11px;">
                * Phân tích dựa trên thống kê nhiều chiều, chỉ mang tính tham khảo. Điểm = Gan + Nóng + Cầu + Thứ + Tần suất.
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Ngày mà trang đang hiển thị (Blade → JS)
    const PAGE_DATE = '{{ $selectedDate }}';
    const TODAY_DATE = new Date().toISOString().slice(0, 10);
    const IS_TODAY_PAGE = (PAGE_DATE === TODAY_DATE);
    // TỰ ĐỘNG HIGHLIGHT SỐ TỪ TRANG PHÂN TÍCH
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        const hlNumber = urlParams.get('hl');
        if (hlNumber) {
            let found = false;
            const slots = document.querySelectorAll('.prize-slot');
            slots.forEach(slot => {
                if (slot.innerText.trim() === hlNumber) {
                    slot.innerHTML = `<span class="highlight-number">${hlNumber}</span>`;
                    found = true;
                    slot.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
            if (found) console.log("Đã bôi xanh số: " + hlNumber);
        }
    });

    // Xử lý chuyển tháng/năm trên lịch
    function changeMonthYear() {
        let m = document.getElementById('cal-month').value;
        let y = document.getElementById('cal-year').value;
        window.location.href = "?date=" + y + "-" + m.padStart(2, '0') + "-01";
    }

    // Xử lý nút Đọc tiếp / Thu gọn
    function toggleText() {
        let summary = document.getElementById('text-summary');
        let full = document.getElementById('text-full');
        if(summary.classList.contains('d-none')) {
            summary.classList.remove('d-none');
            full.classList.add('d-none');
        } else {
            summary.classList.add('d-none');
            full.classList.remove('d-none');
        }
    }

    // NarrationManager đã được gỡ bỏ
    const NarrationManager = { enabled: false, init(){}, narratePrize(){}, narrateStart(){}, narrateEnd(){}, saveState(){}, saveRevealedResult(){}, _savedResults:{}, storageKey:'xsmb_narration_state', _unlocked:false, enable(){}, disable(){}, toggle(){}, wasAlreadyNarrated(){return false}, getRevealedResults(){return {}} };


    // ============================================================
    // === ĐẾM NGƯỢC & LIVE STATUS ===
    // ============================================================
    function updateCountdown() {
        const now = new Date();
        let target = new Date();
        target.setHours(18, 15, 0, 0);

        if (now > target) {
            let endDraw = new Date();
            endDraw.setHours(18, 40, 0, 0);

            if (now < endDraw) {
                document.getElementById('countdown-wrapper').innerHTML = '<h5 class="text-danger fw-bold m-0"><span class="spinner-grow spinner-grow-sm text-danger" role="status"></span> Đang trực tiếp xổ số Miền Bắc...</h5>';
                return;
            } else {
                target.setDate(target.getDate() + 1);
            }
        }

        const diff = target - now;
        const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const s = Math.floor((diff % (1000 * 60)) / 1000);

        let elH = document.getElementById('cd-hours');
        let elM = document.getElementById('cd-minutes');
        let elS = document.getElementById('cd-seconds');

        if(elH && elM && elS) {
            elH.innerText = h.toString().padStart(2, '0');
            elM.innerText = m.toString().padStart(2, '0');
            elS.innerText = s.toString().padStart(2, '0');
        }
    }

    setInterval(updateCountdown, 1000);
    updateCountdown();

    // ============================================================
    // === LIVE DRAW — ANIMATION & WEBSOCKET ===
    // ============================================================
    const TIER_MAP = {
        'giai_1': 'G1', 'giai_2': 'G2', 'giai_3': 'G3',
        'giai_4': 'G4', 'giai_5': 'G5', 'giai_6': 'G6',
        'giai_7': 'G7', 'giai_dac_biet': 'GDB'
    };
    const rollingIntervals = {};
    const revealedSlots = {};
    let isLiveActive = false;

    function randomDigits(len) {
        let r = '';
        for (let i = 0; i < len; i++) r += Math.floor(Math.random() * 10);
        return r;
    }

    function findSlot(tier, index) {
        return document.querySelector('.prize-slot[data-tier="' + tier + '"][data-index="' + index + '"]');
    }

    function startSlotRolling(tier, index) {
        const slot = findSlot(tier, index);
        if (!slot) return;
        const key = tier + '_' + index;
        if (revealedSlots[key]) return;
        const digits = parseInt(slot.dataset.digits) || 2;
        slot.classList.add('rolling');
        slot.classList.remove('revealed');
        if (rollingIntervals[key]) clearInterval(rollingIntervals[key]);
        rollingIntervals[key] = setInterval(() => {
            slot.textContent = randomDigits(digits);
        }, 50);
    }

    function stopSlotRolling(tier, index, value) {
        const slot = findSlot(tier, index);
        if (!slot) return;
        const key = tier + '_' + index;
        if (revealedSlots[key]) return;
        revealedSlots[key] = true;
        if (rollingIntervals[key]) { clearInterval(rollingIntervals[key]); delete rollingIntervals[key]; }
        let count = 0;
        const digits = parseInt(slot.dataset.digits) || 2;
        const slow = setInterval(() => {
            slot.textContent = randomDigits(digits);
            count++;
            if (count >= 4) {
                clearInterval(slow);
                slot.classList.remove('rolling');
                slot.textContent = value;
                void slot.offsetWidth;
                slot.classList.add('revealed', 'prize-new');
                if (tier === 'GDB') slot.style.color = 'red';
                setTimeout(() => slot.classList.remove('prize-new'), 2000);
            }
        }, 150);
    }

    // Hiển thị số ngay (không animation) — dùng khi restore sau reload
    function revealSlotInstant(tier, index, value) {
        const slot = findSlot(tier, index);
        if (!slot) return;
        const key = tier + '_' + index;
        revealedSlots[key] = true;
        if (rollingIntervals[key]) { clearInterval(rollingIntervals[key]); delete rollingIntervals[key]; }
        slot.classList.remove('rolling');
        slot.textContent = value;
        slot.classList.add('revealed');
        if (tier === 'GDB') slot.style.color = 'red';
    }

    function resetAllSlots() {
        Object.keys(revealedSlots).forEach(k => delete revealedSlots[k]);
        Object.keys(rollingIntervals).forEach(k => {
            clearInterval(rollingIntervals[k]);
            delete rollingIntervals[k];
        });
        document.querySelectorAll('.prize-slot[data-tier]').forEach(slot => {
            slot.textContent = '...';
            slot.classList.remove('rolling', 'revealed', 'prize-new');
            slot.style.color = '';
        });
    }

    function startAllRolling() {
        resetAllSlots();
        document.querySelectorAll('.prize-slot[data-tier]').forEach(slot => {
            startSlotRolling(slot.dataset.tier, parseInt(slot.dataset.index));
        });
    }

    function showLiveIndicator(show) {
        const el = document.getElementById('liveIndicator');
        if (el) el.classList.toggle('d-none', !show);
    }

    // ============================================================
    // === RESUME SAU RELOAD — FETCH LIVE STATUS TỪ SERVER ===
    // ============================================================
    let _resumePending = false; // Flag: có resume cần user gesture không

    async function checkAndResumeLive() {
        // CHỈ chạy resume khi đang xem trang NGÀY HÔM NAY
        if (!IS_TODAY_PAGE) {
            console.log('[Resume] Không phải trang hôm nay (' + PAGE_DATE + '), bỏ qua resume.');
            return;
        }

        try {
            const resp = await fetch('/api/live-status');
            const data = await resp.json();
            console.log('[Resume] Live status:', data);

            // Lấy danh sách giải đã narrate từ localStorage
            const savedNarrated = NarrationManager._savedResults || {};
            const savedCount = Object.keys(savedNarrated).length;

            // Kiểm tra: có results từ server VÀ (đang updating HOẶC vừa completed mà chưa đọc hết)
            const hasResults = data.results && data.results.length > 0;
            const isUpdating = data.status === 'updating';
            const isCompleted = data.status === 'completed';
            const hasUnnarrated = hasResults && data.results.some(r => {
                const key = r.tier + '_' + r.index;
                return !savedNarrated[key];
            });

            if (hasResults && (isUpdating || (isCompleted && hasUnnarrated && savedCount > 0))) {
                console.log('[Resume] Cần resume! Server:', data.results.length, 'giải. Đã đọc:', savedCount);

                // Restore UI: hiển thị live indicator + kết quả
                isLiveActive = isUpdating; // Chỉ giữ live nếu đang updating
                if (isUpdating) {
                    showLiveIndicator(true);
                    const cdWrapper = document.getElementById('countdown-wrapper');
                    if (cdWrapper) cdWrapper.innerHTML = '<h5 class="text-danger fw-bold m-0"><span class="spinner-grow spinner-grow-sm text-danger" role="status"></span> ĐANG TƯỜNG THUẬT TRỰC TIẾP...</h5>';
                }

                // TỐI ƯU HIỂN THỊ: Chỉ điền số vào những ô mà Pusher chưa kịp update
                // Điều này giúp hiệu ứng quay số (rolling) của Pusher không bị đứng hình
                data.results.forEach(r => {
                    const key = r.tier + '_' + r.index;
                    if (!revealedSlots[key]) {
                        revealSlotInstant(r.tier, r.index, r.value);
                    }
                });

                // Tìm các giải CHƯA đọc
                const unnarratedResults = data.results.filter(r => {
                    const key = r.tier + '_' + r.index;
                    return !savedNarrated[key];
                });

                if (unnarratedResults.length > 0 && NarrationManager.enabled) {
                    console.log('[Resume] Có', unnarratedResults.length, 'giải chưa đọc.');

                    // Thử đọc trực tiếp trước
                    if (NarrationManager._unlocked) {
                        // Speech đã được unlock → đọc luôn
                        console.log('[Resume] Speech đã unlock → đọc trực tiếp.');
                        unnarratedResults.forEach((r, i) => {
                            setTimeout(() => {
                                NarrationManager.narratePrize(r.tier, r.index, r.value);
                            }, i * 500);
                        });
                    } else {
                        // Chưa unlock → lưu pending, sẽ tự đọc khi user tương tác
                        _resumePending = true;
                        window._pendingNarrations = unnarratedResults;
                        showResumeBanner(unnarratedResults.length);
                    }
                } else {
                    // Đã đọc hết, chỉ cần đánh dấu
                    data.results.forEach(r => {
                        NarrationManager.saveRevealedResult(r.tier, r.index, r.value);
                    });
                }

                // Nếu đang rolling và đang updating, start rolling cho các ô chưa reveal
                if (isUpdating) {
                    document.querySelectorAll('.prize-slot[data-tier]').forEach(slot => {
                        const key = slot.dataset.tier + '_' + parseInt(slot.dataset.index);
                        if (!revealedSlots[key]) {
                            startSlotRolling(slot.dataset.tier, parseInt(slot.dataset.index));
                        }
                    });
                }

            } else if (isCompleted && !hasUnnarrated) {
                console.log('[Resume] Kỳ quay đã hoàn tất và đã đọc hết.');
                // Xóa state cũ
                localStorage.removeItem(NarrationManager.storageKey);
            }
        } catch (e) {
            console.warn('[Resume] Không thể fetch live-status:', e);
        }
    }



    // ============================================================
    // === KHỞI TẠO KHI PAGE LOAD & LẶP DỰ PHÒNG MỖI 5 GIÂY ===
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Gọi lần đầu khi vừa vào trang
        checkAndResumeLive();

        // 2. VÒNG LẶP DỰ PHÒNG MỖI 5 GIÂY (Chỉ chạy trong ngày hôm nay)
        // Nó sẽ liên tục đọc số từ Cache siêu nhẹ do Daemon trả về. Lỡ Pusher xịt mạng, bảng vẫn nhảy số ầm ầm.
        if (IS_TODAY_PAGE) {
            setInterval(() => {
                // Chỉ chạy vòng lặp lấy số khi đang cập nhật hoặc khi bảng chưa đủ số
                if (isLiveActive || document.getElementById('countdown-wrapper').innerText.includes('ĐANG TƯỜNG THUẬT')) {
                    checkAndResumeLive();
                }
            }, 5000);
        }
    });
</script>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>

<script>
    // Khởi tạo Echo với Pusher
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: 'c56440d950fea2b4010f',
        cluster: 'ap1',
        forceTLS: true
    });

    // === LARAVEL ECHO — LẮNG NGHE TƯỜNG THUẬT TRỰC TIẾP ===
    if (typeof window.Echo !== 'undefined') {
        window.Echo.channel('xsmb-channel')
            .listen('.xsmb.update', (data) => {
                const { prize_name, value, index } = data;
                console.log('[Echo] Nhận:', prize_name, value, index);

                // === Signal bắt đầu ===
                if (prize_name === '_start') {
                    isLiveActive = true;
                    showLiveIndicator(true);

                    // Chỉ reset slots nếu chưa có kết quả nào hiển thị
                    const anyRevealed = Object.keys(revealedSlots).length > 0;
                    if (!anyRevealed) {
                        startAllRolling();
                    } else {
                        // Đã có kết quả từ resume → chỉ rolling các ô chưa reveal
                        document.querySelectorAll('.prize-slot[data-tier]').forEach(slot => {
                            const key = slot.dataset.tier + '_' + parseInt(slot.dataset.index);
                            if (!revealedSlots[key]) {
                                startSlotRolling(slot.dataset.tier, parseInt(slot.dataset.index));
                            }
                        });
                    }

                    const cdWrapper = document.getElementById('countdown-wrapper');
                    if (cdWrapper) cdWrapper.innerHTML = '<h5 class="text-danger fw-bold m-0"><span class="spinner-grow spinner-grow-sm text-danger" role="status"></span> ĐANG TƯỜNG THUẬT TRỰC TIẾP...</h5>';

                    return;
                }

                // === Signal kết thúc ===
                if (prize_name === '_end') {
                    isLiveActive = false;
                    showLiveIndicator(false);
                    Object.keys(rollingIntervals).forEach(k => {
                        clearInterval(rollingIntervals[k]);
                        delete rollingIntervals[k];
                    });

                    const cdWrapper = document.getElementById('countdown-wrapper');
                    if (cdWrapper) cdWrapper.innerHTML = '<h5 class="text-success fw-bold m-0">✅ Đã tường thuật xong!</h5>';


                    return;
                }

                // === Kết quả giải thưởng ===
                const tier = TIER_MAP[prize_name];
                if (!tier) return;

                if (!isLiveActive) {
                    isLiveActive = true;
                    showLiveIndicator(true);

                    // Chỉ rolling các ô chưa reveal
                    document.querySelectorAll('.prize-slot[data-tier]').forEach(slot => {
                        const key = slot.dataset.tier + '_' + parseInt(slot.dataset.index);
                        if (!revealedSlots[key]) {
                            startSlotRolling(slot.dataset.tier, parseInt(slot.dataset.index));
                        }
                    });


                }

                // Hiển thị animation
                stopSlotRolling(tier, index, value);


            });
    }
</script>
@endpush