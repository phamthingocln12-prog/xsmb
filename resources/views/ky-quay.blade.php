@extends('layouts.app')

@php
/** @var array $drawsData */
/** @var string $fromDate */
/** @var string $toDate */
/** @var string $searchNumber */
/** @var int $totalCount */
/** @var int $page */
/** @var int $totalPages */
/** @var string $onlyGdb */
/** @var string $showDauDuoi */
/** @var array $numberResults */
/** @var array $searchNumbers */
/** @var int $numPage */
/** @var array $numTotalPages */
/** @var string $viewDate */
/** @var array|null $viewDrawData */
@endphp

@section('content')
<style>
    .kq-filter {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 16px;
        margin-bottom: 20px;
    }
    .kq-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        margin-bottom: 16px;
        overflow: hidden;
    }
    .kq-card-header {
        background: #dc3545;
        color: #fff;
        padding: 8px 14px;
        font-weight: 700;
        font-size: 0.95rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .kq-card-header .gdb-badge {
        background: #ffc107;
        color: #333;
        font-size: 0.8rem;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: 700;
    }
    .table-kq {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }
    .table-kq th {
        background: #f8d7da;
        color: #dc3545;
        padding: 5px 8px;
        text-align: center;
        font-weight: 700;
        width: 50px;
        border: 1px solid #dee2e6;
    }
    .table-kq td {
        padding: 5px 8px;
        text-align: center;
        font-weight: 600;
        border: 1px solid #dee2e6;
    }
    .table-kq .gdb-row td {
        font-size: 1.3rem;
        color: #dc3545;
        font-weight: 800;
    }
    .dd-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.82rem;
    }
    .dd-table th {
        background: #e9ecef;
        padding: 3px 6px;
        text-align: center;
        font-weight: 700;
        border: 1px solid #dee2e6;
    }
    .dd-table td {
        padding: 3px 6px;
        text-align: center;
        border: 1px solid #dee2e6;
    }
    .dd-table .hcol {
        background: #fff3cd;
        font-weight: 700;
        width: 30px;
        color: #856404;
    }
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #999;
    }
    .opt-btn.active {
        background: #dc3545;
        color: #fff;
        border-color: #dc3545;
    }
    .num-result-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }
    .num-result-table th {
        background: #dc3545;
        color: #fff;
        padding: 8px 12px;
        text-align: center;
        font-weight: 700;
        border: 1px solid #c82333;
    }
    .num-result-table td {
        padding: 6px 12px;
        text-align: center;
        border: 1px solid #dee2e6;
        font-weight: 600;
    }
    .num-result-table tbody tr:nth-child(even) {
        background: #f8f9fa;
    }
    .num-badge {
        display: inline-block;
        background: #dc3545;
        color: #fff;
        font-weight: 700;
        padding: 2px 10px;
        border-radius: 4px;
        font-size: 1rem;
    }
    .view-draw-link {
        color: #0d6efd;
        text-decoration: none;
        font-size: 0.8rem;
    }
    .view-draw-link:hover {
        text-decoration: underline;
    }
</style>

@php
    $baseParams = [
        'search_number' => $searchNumber,
        'from_date' => $fromDate,
        'to_date' => $toDate,
        'only_gdb' => $onlyGdb,
        'show_dau_duoi' => $showDauDuoi,
    ];
    $dayNames = ['CN', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
    $dayNamesShort = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
@endphp

<div class="kq-filter">
    <h5 class="mb-3 fw-bold">Kỳ Quay</h5>
    <form method="GET" action="{{ route('ky-quay') }}" id="filterForm">
        <input type="hidden" name="only_gdb" id="only_gdb" value="{{ $onlyGdb }}">
        <input type="hidden" name="show_dau_duoi" id="show_dau_duoi" value="{{ $showDauDuoi }}">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Số cần tìm (cách nhau bởi dấu phẩy)</label>
                <input type="text" name="search_number" class="form-control form-control-sm" placeholder="VD: 68, 15, 23" value="{{ $searchNumber }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Từ ngày</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}" min="{{ now()->subYears(20)->format('Y-m-d') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Đến ngày</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}" min="{{ now()->subYears(20)->format('Y-m-d') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-danger btn-sm w-100 fw-bold">Tra cứu</button>
            </div>
            <div class="col-md-1">
                <a href="{{ route('ky-quay') }}" class="btn btn-outline-secondary btn-sm w-100">Đặt lại</a>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <a href="#" class="btn btn-outline-danger btn-sm {{ $onlyGdb === '1' ? 'active' : '' }}" id="btnOnlyGdb">Chỉ giải ĐB</a>
            <a href="#" class="btn btn-outline-danger btn-sm {{ $showDauDuoi === '1' ? 'active' : '' }}" id="btnDauDuoi">Bảng đầu đuôi</a>
        </div>
    </form>
</div>

@php
    $hasNumberSearch = !empty($searchNumbers);
    $hasDateSearch = ($fromDate !== '' && $toDate !== '');
    $hasAny = $hasNumberSearch || $hasDateSearch;
@endphp

@if(!$hasAny)
    <div class="empty-state">
        <h6>Chọn số, khoảng thời gian hoặc cả hai để tra cứu kỳ quay</h6>
    </div>
@else

    {{-- === BẢNG KẾT QUẢ TÌM THEO SỐ === --}}
    @if($hasNumberSearch && !empty($numberResults))
        @foreach($numberResults as $num => $results)
            @php
                $totalForNum = isset($numTotalPages[$num]) ? $numTotalPages[$num] : 1;
                $totalResultsForNum = ($numPage - 1) * 20 + count($results);
                if ($numPage < $totalForNum) {
                    $totalResultsForNum = '20+';
                }
            @endphp
            <div class="kq-card">
                <div class="kq-card-header">
                    <span>Số <span class="num-badge">{{ $num }}</span> &mdash; 2 số cuối Giải ĐB</span>
                    <span>Trang {{ $numPage }}/{{ $totalForNum }}</span>
                </div>
                <div class="p-2">
                    @if(count($results) === 0)
                        <p class="text-muted text-center py-3 mb-0">Không tìm thấy kỳ nào</p>
                    @else
                        <table class="num-result-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px">STT</th>
                                    <th>Ngày</th>
                                    <th>Thứ</th>
                                    <th>Giải ĐB</th>
                                    <th style="width: 100px">Xem kỳ quay</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($results as $idx => $r)
                                    @php
                                        $d = \Carbon\Carbon::parse($r['date']);
                                        $stt = ($numPage - 1) * 20 + $idx + 1;
                                    @endphp
                                    <tr>
                                        <td>{{ $stt }}</td>
                                        <td>{{ $d->format('d/m/Y') }}</td>
                                        <td>{{ $dayNames[$d->dayOfWeek] }}</td>
                                        <td class="text-danger fw-bold" style="font-size: 1.1rem">{{ $r['full_number'] }}</td>
                                        <td>
                                            <a href="{{ route('ky-quay', array_merge($baseParams, ['view_date' => $r['date'], 'num_page' => $numPage])) }}" class="view-draw-link">
                                                Xem
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- Phân trang bảng số --}}
                        @if($totalForNum > 1)
                            <nav class="d-flex justify-content-center mt-2">
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item {{ $numPage <= 1 ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ route('ky-quay', array_merge($baseParams, ['num_page' => $numPage - 1])) }}">Trước</a>
                                    </li>
                                    @for($p = 1; $p <= $totalForNum; $p++)
                                        <li class="page-item {{ $p == $numPage ? 'active' : '' }}">
                                            <a class="page-link" href="{{ route('ky-quay', array_merge($baseParams, ['num_page' => $p])) }}">{{ $p }}</a>
                                        </li>
                                    @endfor
                                    <li class="page-item {{ $numPage >= $totalForNum ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ route('ky-quay', array_merge($baseParams, ['num_page' => $numPage + 1])) }}">Sau</a>
                                    </li>
                                </ul>
                            </nav>
                        @endif
                    @endif
                </div>
            </div>
        @endforeach

        {{-- === KỲ QUAY CHI TIẾT KHI BẤM "XEM" === --}}
        @if($viewDrawData)
            @php
                $vDraw = $viewDrawData['draw'];
                $vGrouped = $viewDrawData['grouped'];
                $vDauStats = $viewDrawData['dauStats'];
                $vDuoiStats = $viewDrawData['duoiStats'];
                $vGdbNumber = $viewDrawData['gdbNumber'];
                $vDate = \Carbon\Carbon::parse($vDraw->draw_date);
                $vPrizeOrder = $onlyGdb === '1' ? ['GDB'] : ['GDB', 'G1', 'G2', 'G3', 'G4', 'G5', 'G6', 'G7'];
                $prizeLabels = ['GDB'=>'ĐB','G1'=>'G1','G2'=>'G2','G3'=>'G3','G4'=>'G4','G5'=>'G5','G6'=>'G6','G7'=>'G7'];
            @endphp
            <div class="kq-card" style="border: 2px solid #ffc107;">
                <div class="kq-card-header" style="background: #e67e22;">
                    <span>{{ $dayNamesShort[$vDate->dayOfWeek] }}, {{ $vDate->format('d/m/Y') }} &mdash; Chi tiết kỳ quay</span>
                    @if($vGdbNumber)
                        <span class="gdb-badge">GĐB: {{ $vGdbNumber }}</span>
                    @endif
                </div>
                <div class="row g-0">
                    <div class="{{ $showDauDuoi === '1' ? 'col-md-7' : 'col-12' }} p-2">
                        <table class="table-kq">
                            @foreach($vPrizeOrder as $prize)
                                @if(isset($vGrouped[$prize]))
                                    <tr class="{{ $prize === 'GDB' ? 'gdb-row' : '' }}">
                                        <th>{{ $prizeLabels[$prize] }}</th>
                                        <td>
                                            @foreach($vGrouped[$prize] as $n)
                                                <span class="me-2">{{ $n }}</span>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </table>
                    </div>
                    @if($showDauDuoi === '1')
                    <div class="col-md-5 p-2" style="border-left: 1px solid #eee;">
                        <div class="row g-1">
                            <div class="col-6">
                                <table class="dd-table">
                                    <thead><tr><th>Đầu</th><th>Lô tô</th></tr></thead>
                                    <tbody>
                                        @for($i = 0; $i < 10; $i++)
                                            <tr>
                                                <td class="hcol">{{ $i }}</td>
                                                <td>{{ count($vDauStats[$i]) > 0 ? implode(', ', $vDauStats[$i]) : '-' }}</td>
                                            </tr>
                                        @endfor
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-6">
                                <table class="dd-table">
                                    <thead><tr><th>Đuôi</th><th>Lô tô</th></tr></thead>
                                    <tbody>
                                        @for($i = 0; $i < 10; $i++)
                                            <tr>
                                                <td class="hcol">{{ $i }}</td>
                                                <td>{{ count($vDuoiStats[$i]) > 0 ? implode(', ', $vDuoiStats[$i]) : '-' }}</td>
                                            </tr>
                                        @endfor
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        @endif
    @endif

    {{-- === BẢNG KỲ QUAY (chỉ khi có chọn ngày mà không nhập số) === --}}
    @if($hasDateSearch && !$hasNumberSearch)
        @if(count($drawsData) === 0)
            <div class="empty-state">
                <h6>Không tìm thấy kỳ quay nào trong khoảng ngày đã chọn</h6>
            </div>
        @else
            <p class="text-muted small mb-3">
                Trang {{ $page }}/{{ $totalPages }} &mdash; Tổng <strong>{{ $totalCount }}</strong> kỳ quay
            </p>

            @php
                $prizeOrder = $onlyGdb === '1' ? ['GDB'] : ['GDB', 'G1', 'G2', 'G3', 'G4', 'G5', 'G6', 'G7'];
                $prizeLabels = ['GDB'=>'ĐB','G1'=>'G1','G2'=>'G2','G3'=>'G3','G4'=>'G4','G5'=>'G5','G6'=>'G6','G7'=>'G7'];
            @endphp

            @foreach($drawsData as $item)
                @php
                    $draw = $item['draw'];
                    $grouped = $item['grouped'];
                    $dauStats = $item['dauStats'];
                    $duoiStats = $item['duoiStats'];
                    $gdbNumber = $item['gdbNumber'];
                    $drawDate = \Carbon\Carbon::parse($draw->draw_date);
                    $dayName = $dayNamesShort[$drawDate->dayOfWeek];
                @endphp
                <div class="kq-card">
                    <div class="kq-card-header">
                        <span>{{ $dayName }}, {{ $drawDate->format('d/m/Y') }}</span>
                        @if($gdbNumber)
                            <span class="gdb-badge">GĐB: {{ $gdbNumber }}</span>
                        @endif
                    </div>
                    <div class="row g-0">
                        <div class="{{ $showDauDuoi === '1' ? 'col-md-7' : 'col-12' }} p-2">
                            <table class="table-kq">
                                @foreach($prizeOrder as $prize)
                                    @if(isset($grouped[$prize]))
                                        <tr class="{{ $prize === 'GDB' ? 'gdb-row' : '' }}">
                                            <th>{{ $prizeLabels[$prize] }}</th>
                                            <td>
                                                @foreach($grouped[$prize] as $num)
                                                    <span class="me-2">{{ $num }}</span>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </table>
                        </div>
                        @if($showDauDuoi === '1')
                        <div class="col-md-5 p-2" style="border-left: 1px solid #eee;">
                            <div class="row g-1">
                                <div class="col-6">
                                    <table class="dd-table">
                                        <thead><tr><th>Đầu</th><th>Lô tô</th></tr></thead>
                                        <tbody>
                                            @for($i = 0; $i < 10; $i++)
                                                <tr>
                                                    <td class="hcol">{{ $i }}</td>
                                                    <td>{{ count($dauStats[$i]) > 0 ? implode(', ', $dauStats[$i]) : '-' }}</td>
                                                </tr>
                                            @endfor
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-6">
                                    <table class="dd-table">
                                        <thead><tr><th>Đuôi</th><th>Lô tô</th></tr></thead>
                                        <tbody>
                                            @for($i = 0; $i < 10; $i++)
                                                <tr>
                                                    <td class="hcol">{{ $i }}</td>
                                                    <td>{{ count($duoiStats[$i]) > 0 ? implode(', ', $duoiStats[$i]) : '-' }}</td>
                                                </tr>
                                            @endfor
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            @endforeach

            {{-- Phân trang --}}
            @if($totalPages > 1)
                <nav class="d-flex justify-content-center mb-4">
                    <ul class="pagination pagination-sm">
                        <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ route('ky-quay', array_merge($baseParams, ['page' => $page - 1])) }}">Trước</a>
                        </li>
                        @for($p = 1; $p <= $totalPages; $p++)
                            <li class="page-item {{ $p == $page ? 'active' : '' }}">
                                <a class="page-link" href="{{ route('ky-quay', array_merge($baseParams, ['page' => $p])) }}">{{ $p }}</a>
                            </li>
                        @endfor
                        <li class="page-item {{ $page >= $totalPages ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ route('ky-quay', array_merge($baseParams, ['page' => $page + 1])) }}">Sau</a>
                        </li>
                    </ul>
                </nav>
            @endif
        @endif
    @endif

@endif

@push('scripts')
<script>
    document.getElementById('btnOnlyGdb').addEventListener('click', function(e) {
        e.preventDefault();
        var input = document.getElementById('only_gdb');
        input.value = input.value === '1' ? '0' : '1';
        document.getElementById('filterForm').submit();
    });
    document.getElementById('btnDauDuoi').addEventListener('click', function(e) {
        e.preventDefault();
        var input = document.getElementById('show_dau_duoi');
        input.value = input.value === '1' ? '0' : '1';
        document.getElementById('filterForm').submit();
    });
</script>
@endpush
@endsection
