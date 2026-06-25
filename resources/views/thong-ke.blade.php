@extends('layouts.app')

@php
/** @var array $gridData */
/** @var array $frequency */
/** @var int $maxFreq */
/** @var int $days */
/** @var string $mode */
/** @var string $capSo */
/** @var string $fromDate */
/** @var string $toDate */
/** @var array $filterNumbers */
@endphp

@section('content')

<style>
    .tk-wrapper { background: #fff; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-left: calc(-50vw + 50%); margin-right: calc(-50vw + 50%); width: 100vw; }
    .tk-header { background: linear-gradient(135deg, #dc3545, #e91e63); color: #fff; padding: 15px 20px; }
    .tk-controls { background: #fff0f3; padding: 12px 20px; border-bottom: 2px solid #f8d7da; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .tk-controls label { font-weight: 600; color: #dc3545; font-size: 13px; white-space: nowrap; }
    .tk-controls select, .tk-controls input[type="text"] { border: 1px solid #e91e63; border-radius: 4px; padding: 5px 8px; font-size: 13px; outline: none; }
    .tk-controls input[type="date"] { border: 1px solid #e91e63; border-radius: 4px; padding: 5px 8px; font-size: 13px; outline: none; }
    .tk-controls select:focus, .tk-controls input:focus { border-color: #dc3545; box-shadow: 0 0 0 2px rgba(220,53,69,0.15); }
    .tk-controls .btn-tk { background: #dc3545; color: #fff; border: none; padding: 6px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 13px; }
    .tk-controls .btn-tk:hover { background: #c82333; }
    .tk-separator { width: 1px; height: 28px; background: #e91e63; opacity: 0.3; }
    .tk-mode-toggle { display: flex; border-radius: 4px; overflow: hidden; border: 1px solid #e91e63; }
    .tk-mode-toggle label { padding: 5px 14px; font-size: 13px; cursor: pointer; margin: 0; font-weight: 600; transition: all 0.2s; }
    .tk-mode-toggle input[type="radio"] { display: none; }
    .tk-mode-toggle input[type="radio"]:checked + label { background: #dc3545; color: #fff; }
    .tk-mode-toggle input[type="radio"]:not(:checked) + label { background: #fff; color: #dc3545; }

    /* Lưới thống kê */
    .tk-grid-container { overflow-x: auto; max-height: 70vh; overflow-y: auto; }
    .tk-grid { border-collapse: collapse; font-size: 12px; width: max-content; }
    .tk-grid th, .tk-grid td { border: 1px solid #f8d7da; text-align: center; padding: 0; width: 28px; min-width: 28px; max-width: 28px; height: 22px; }
    .tk-grid thead th { position: sticky; top: 0; z-index: 2; background: linear-gradient(180deg, #dc3545, #e91e63); color: #fff; font-weight: 700; font-size: 11px; padding: 4px 2px; }
    .tk-grid .col-date { position: sticky; left: 0; z-index: 3; background: #fff0f3; color: #c82333; font-weight: 700; width: 90px; min-width: 90px; max-width: 90px; font-size: 11px; text-align: left; padding-left: 6px; }
    .tk-grid thead .col-date { z-index: 4; background: #b71c2e; }

    /* Thanh tần suất */
    .freq-bar td { background: #fff0f3; padding: 0; vertical-align: bottom; height: 40px; position: sticky; top: 24px; z-index: 1; }
    .freq-bar .col-date { background: #fff0f3; font-size: 10px; color: #999; vertical-align: middle; position: sticky; top: 24px; z-index: 3; }
    .freq-fill { background: linear-gradient(180deg, #e91e63, #dc3545); width: 18px; margin: 0 auto; border-radius: 2px 2px 0 0; }
    .freq-num-row td { background: #fff0f3; position: sticky; top: 64px; z-index: 1; }
    .freq-num-row .col-date { position: sticky; top: 64px; z-index: 3; }
    .freq-num { font-size: 9px; color: #c82333; font-weight: bold; }

    /* Ô kết quả */
    .tk-grid td.has-result { background: #fce4ec; color: #c82333; font-weight: 700; }
    .tk-grid td.has-multi { background: #e91e63; color: #fff; font-weight: 700; }
    .tk-grid td.has-gdb { background: #dc3545; color: #fff; font-weight: 900; text-shadow: 0 0 4px rgba(255,255,255,0.5); }

    /* Cột bị ẩn khi lọc cặp số */
    .tk-grid .col-hidden { display: none; }

    /* Highlight cột */
    .tk-grid .col-highlight { background: rgba(233, 30, 99, 0.08) !important; }
    .tk-grid thead .col-highlight { background: #b71c2e !important; }
</style>

<div class="tk-wrapper">

    <form id="tk-form" class="tk-controls" method="GET" action="{{ route('thong-ke') }}">
        {{-- Chọn thời gian --}}
        <label>Từ ngày:</label>
        <input type="date" name="from_date" value="{{ $fromDate }}" id="tk-from" min="{{ now()->subYears(20)->format('Y-m-d') }}">

        <label>Đến:</label>
        <input type="date" name="to_date" value="{{ $toDate }}" id="tk-to" min="{{ now()->subYears(20)->format('Y-m-d') }}">

        <div class="tk-separator"></div>

        {{-- Cặp số --}}
        <label>Cặp số:</label>
        <input type="text" name="cap_so" value="{{ $capSo }}" placeholder="VD: 00,15,88" style="width: 130px;" id="tk-capso">

        <div class="tk-separator"></div>

        {{-- LT / ĐB --}}
        <div class="tk-mode-toggle">
            <input type="radio" name="mode" value="lt" id="mode-lt" {{ $mode == 'lt' ? 'checked' : '' }}>
            <label for="mode-lt">LT</label>
            <input type="radio" name="mode" value="db" id="mode-db" {{ $mode == 'db' ? 'checked' : '' }}>
            <label for="mode-db">ĐB</label>
        </div>

        <button type="submit" class="btn-tk">Thống kê</button>

        <span class="ms-auto text-muted" style="font-size: 13px;">
            {{ $mode == 'db' ? 'Đặc biệt' : 'Lô tô' }} |
            <strong class="text-danger">{{ $days }}</strong> kỳ quay
            @if(!empty($capSo))
                | Lọc: <strong class="text-danger">{{ $capSo }}</strong>
            @endif
        </span>
    </form>

    <div class="tk-grid-container">
        <table class="tk-grid" id="tk-table">
            <thead>
                <tr>
                    <th class="col-date">Ngày</th>
                    @for($n = 0; $n < 100; $n++)
                        @php
                            $numStr = str_pad($n, 2, '0', STR_PAD_LEFT);
                            $hidden = !empty($filterNumbers) && !in_array($n, $filterNumbers);
                        @endphp
                        <th class="num-col-{{ $n }} {{ $hidden ? 'col-hidden' : '' }}"
                            onmouseenter="highlightCol({{ $n }})" onmouseleave="unhighlightCol({{ $n }})">
                            {{ $numStr }}
                        </th>
                    @endfor
                </tr>
            </thead>

            <tbody>
                {{-- Hàng thanh tần suất --}}
                <tr class="freq-bar">
                    <td class="col-date" style="font-size:10px; color:#999;">Số lần về</td>
                    @for($n = 0; $n < 100; $n++)
                        @php $hidden = !empty($filterNumbers) && !in_array($n, $filterNumbers); @endphp
                        <td class="num-col-{{ $n }} {{ $hidden ? 'col-hidden' : '' }}">
                            <div class="freq-fill" style="height: {{ round($frequency[$n] / max($maxFreq, 1) * 36) }}px;"
                                 title="{{ str_pad($n, 2, '0', STR_PAD_LEFT) }}: {{ $frequency[$n] }} lần"></div>
                        </td>
                    @endfor
                </tr>
                <tr class="freq-num-row">
                    <td class="col-date" style="background:#fff0f3; font-size:10px; color:#999;">Tổng</td>
                    @for($n = 0; $n < 100; $n++)
                        @php $hidden = !empty($filterNumbers) && !in_array($n, $filterNumbers); @endphp
                        <td class="freq-num num-col-{{ $n }} {{ $hidden ? 'col-hidden' : '' }}">{{ $frequency[$n] }}</td>
                    @endfor
                </tr>

                {{-- Dữ liệu theo ngày --}}
                @foreach($gridData as $day)
                <tr>
                    <td class="col-date">{{ \Carbon\Carbon::parse($day['date'])->format('d-m-Y') }}</td>
                    @for($n = 0; $n < 100; $n++)
                        @php
                            $cell = $day['numbers'][$n];
                            $cls = '';
                            if ($cell['isGDB']) $cls = 'has-gdb';
                            elseif ($cell['count'] > 1) $cls = 'has-multi';
                            elseif ($cell['count'] == 1) $cls = 'has-result';
                            $hidden = !empty($filterNumbers) && !in_array($n, $filterNumbers);
                        @endphp
                        <td class="{{ $cls }} num-col-{{ $n }} {{ $hidden ? 'col-hidden' : '' }}">
                            {{ $cell['count'] > 0 ? $cell['count'] : '' }}
                        </td>
                    @endfor
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function highlightCol(n) {
        document.querySelectorAll('.num-col-' + n).forEach(el => el.classList.add('col-highlight'));
    }
    function unhighlightCol(n) {
        document.querySelectorAll('.num-col-' + n).forEach(el => el.classList.remove('col-highlight'));
    }
</script>
@endpush