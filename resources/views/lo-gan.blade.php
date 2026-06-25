@extends('layouts.app')

@php
/** @var array $yesterdayData */
/** @var array $todayData */
/** @var array $tomorrowData */
/** @var string $yesterday */
/** @var string $today */
/** @var string $tomorrowDate */
/** @var \Illuminate\Support\Collection $statsMap */
@endphp

@section('content')

<style>
    .lotop-tabs { display: flex; border-bottom: 3px solid #dc3545; margin-bottom: 0; }
    .lotop-tabs .tab-btn { flex: 1; text-align: center; padding: 10px 0; font-weight: bold; font-size: 14px; cursor: pointer; border: 1px solid #ddd; border-bottom: none; background: #f8f9fa; color: #555; transition: all 0.2s; }
    .lotop-tabs .tab-btn.active { background: #dc3545; color: #fff; border-color: #dc3545; }
    .lotop-tabs .tab-btn:hover:not(.active) { background: #fce4ec; }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }
    .tomorrow-reason { font-size: 11px; color: #00897b; font-style: italic; }
</style>

<div class="card shadow-sm">
    {{-- Tabs --}}
    <div class="lotop-tabs">
        <div class="tab-btn" onclick="switchTab('yesterday')" id="tab-yesterday">
            Hôm qua<br><small>{{ \Carbon\Carbon::parse($yesterday)->format('d/m') }}</small>
        </div>
        <div class="tab-btn active" onclick="switchTab('today')" id="tab-today">
            Hôm nay<br><small>{{ \Carbon\Carbon::parse($today)->format('d/m') }}</small>
        </div>
        <div class="tab-btn" onclick="switchTab('tomorrow')" id="tab-tomorrow">
            Ngày mai<br><small>{{ \Carbon\Carbon::parse($tomorrowDate)->format('d/m') }}</small>
        </div>
    </div>

    {{-- Tab Hôm qua --}}
    <div class="tab-panel" id="panel-yesterday">
        <div class="card-header text-center fw-bold py-1" style="background:#fff3e0; color:#e65100; font-size: 14px;">
            LÔ TOP - {{ \Carbon\Carbon::parse($yesterday)->format('d/m/Y') }}
        </div>
        @if(count($yesterdayData) > 0)
        <table class="table table-striped table-hover text-center m-0" style="font-size: 13px;">
            <thead class="table-dark">
                <tr><th>STT</th><th>Số</th><th>Lần về</th><th>Giải</th><th>Tổng</th></tr>
            </thead>
            <tbody>
                @foreach($yesterdayData as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="fw-bold text-danger" style="font-size: 17px;">{{ $item['number'] }}</td>
                    <td>
                        @if($item['count'] >= 3)
                            <span class="badge bg-danger">{{ $item['count'] }} nháy</span>
                        @elseif($item['count'] == 2)
                            <span class="badge bg-warning text-dark">{{ $item['count'] }} nháy</span>
                        @else
                            <span class="badge bg-secondary">{{ $item['count'] }}</span>
                        @endif
                    </td>
                    <td style="font-size: 11px;">
                        @foreach($item['prizes'] as $p)
                            <span class="badge {{ $p == 'GDB' ? 'bg-danger' : 'bg-light text-dark border' }}">{{ $p }}</span>
                        @endforeach
                    </td>
                    <td>{{ isset($statsMap[$item['number']]) ? $statsMap[$item['number']]->total_appearances : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="text-center text-muted py-4">Chưa có dữ liệu</div>
        @endif
    </div>

    {{-- Tab Hôm nay --}}
    <div class="tab-panel active" id="panel-today">
        <div class="card-header text-center fw-bold py-1" style="background:#e8f5e9; color:#2e7d32; font-size: 14px;">
            LÔ TOP HÔM NAY - {{ \Carbon\Carbon::parse($today)->format('d/m/Y') }}
        </div>
        @if(count($todayData) > 0)
        <table class="table table-striped table-hover text-center m-0" style="font-size: 13px;">
            <thead style="background: #2e7d32; color: #fff;">
                <tr><th>STT</th><th>Số</th><th>Lần về</th><th>Giải</th><th>Tổng</th></tr>
            </thead>
            <tbody>
                @foreach($todayData as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="fw-bold text-danger" style="font-size: 17px;">{{ $item['number'] }}</td>
                    <td>
                        @if($item['count'] >= 3)
                            <span class="badge bg-danger">{{ $item['count'] }} nháy</span>
                        @elseif($item['count'] == 2)
                            <span class="badge bg-warning text-dark">{{ $item['count'] }} nháy</span>
                        @else
                            <span class="badge bg-secondary">{{ $item['count'] }}</span>
                        @endif
                    </td>
                    <td style="font-size: 11px;">
                        @foreach($item['prizes'] as $p)
                            <span class="badge {{ $p == 'GDB' ? 'bg-danger' : 'bg-light text-dark border' }}">{{ $p }}</span>
                        @endforeach
                    </td>
                    <td>{{ isset($statsMap[$item['number']]) ? $statsMap[$item['number']]->total_appearances : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="text-center py-4">
            <div class="text-muted mb-2">Chưa có kết quả hôm nay</div>
            <small class="text-muted">Kết quả sẽ cập nhật sau 18:15</small>
        </div>
        @endif
    </div>

    {{-- Tab Ngày mai (Dự đoán) --}}
    <div class="tab-panel" id="panel-tomorrow">
        <div class="card-header text-center fw-bold py-1" style="background:#ede7f6; color:#4527a0; font-size: 14px;">
            DỰ ĐOÁN LÔ TOP - {{ \Carbon\Carbon::parse($tomorrowDate)->format('d/m/Y') }}
        </div>
        @if(count($tomorrowData) > 0)
        <table class="table table-hover text-center m-0" style="font-size: 13px;">
            <thead style="background: #4527a0; color: #fff;">
                <tr><th>STT</th><th>Số</th><th>Lý do</th><th>Gan hiện tại</th></tr>
            </thead>
            <tbody>
                @foreach($tomorrowData as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="fw-bold" style="font-size: 17px; color:#4527a0;">{{ $item['number'] }}</td>
                    <td class="tomorrow-reason">{{ $item['reason'] ?? '' }}</td>
                    <td>
                        @if(isset($statsMap[$item['number']]) && $statsMap[$item['number']]->current_gan_days > 0)
                            <span class="text-danger fw-bold">{{ $statsMap[$item['number']]->current_gan_days }} ngày</span>
                        @else
                            <span class="text-success">Mới về</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="text-center py-1" style="font-size: 11px; background: #ede7f6; color: #666;">
            * Dự đoán dựa trên thống kê lô gan & số nóng, chỉ mang tính tham khảo
        </div>
        @else
        <div class="text-center text-muted py-4">Chưa có dữ liệu phân tích</div>
        @endif
    </div>

    <div class="card-footer text-muted text-center" style="font-size: 12px;">
        Cập nhật mỗi ngày sau 18:30
    </div>
</div>

@endsection

@push('scripts')
<script>
    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById('panel-' + tab).classList.add('active');
    }
</script>
@endpush