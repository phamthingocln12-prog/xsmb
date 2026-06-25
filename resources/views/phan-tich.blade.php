@extends('layouts.app')
@section('content')

<style>
    .pt-wrap { background:#f4f6f9; color:#222; padding:1rem 0; font-family:'Segoe UI',sans-serif; min-height: 100vh; }
    .pt-title { text-align:center; font-size:1.8rem; font-weight:900; color:#c0392b; margin-bottom:0.5rem; text-transform: uppercase; }
    .pt-sub { text-align:center; color:#888; font-size:.9rem; margin-bottom:1.5rem; }
    .pt-controls { display:flex; justify-content:center; align-items:center; gap:1rem; margin-bottom:1.5rem; padding:12px; background:#fff; border-radius:6px; border:1px solid #f0d0d0; border-top: 3px solid #c0392b; box-shadow: 0 2px 5px rgba(0,0,0,0.03); }
    .pt-controls label { font-size:.9rem; color:#555; font-weight: 600; }
    .pt-controls input { border:1px solid #ced4da; padding:4px 12px; border-radius:4px; font-size:.95rem; outline:none; font-weight:bold; }

    .btn-pt { background:#e74c3c; color:#fff; border:none; padding:5px 20px; border-radius:4px; font-weight:700; cursor:pointer; }
    .btn-pt:hover { background:#c0392b; }

    .pt-section { margin-bottom: 2rem; }
    .pt-section-title { font-size:1.1rem; font-weight:800; color:#c0392b; border-bottom:2px solid #f0d0d0; padding-bottom:6px; margin-bottom:1rem; }
    .pt-results { display:flex; gap:1.5rem; justify-content:center; }
    .pt-prize { flex:1; text-align:center; padding:1.5rem; border:1px solid #f5c6cb; border-radius:8px; background:#fffafa; box-shadow: 0 3px 8px rgba(0,0,0,0.04); }
    .pt-prize-label { font-size:.85rem; color:#999; text-transform:uppercase; font-weight:700; }
    .pt-prize-num { font-size:2.4rem; font-weight:900; color:#c0392b; font-family:Consolas,monospace; margin-bottom: 0.5rem; }
    .pt-extracted { display:flex; justify-content:center; gap:.8rem; align-items: center; margin-top:10px; }
    .pt-ext { padding: 4px 12px; border-radius:4px; font-size:1.2rem; font-weight:800; font-family:Consolas; background:#fff; border:1px solid #e74c3c; color:#c0392b; }
    .pt-arrow { color:#ccc; font-size:1.2rem; }
    .pt-patterns { display:grid; grid-template-columns:repeat(4, 1fr); gap:1rem; }
    .pt-pat-box { padding:1rem; border:1px solid #f0d0d0; border-radius:8px; background:#fff; }
    .pt-pat-title { font-weight:800; font-size:.95rem; color:#c0392b; display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; text-transform: uppercase; }
    .pt-pat-num { font-size:1.6rem; font-family:Consolas; border:1px solid #e74c3c; color:#c0392b; padding:2px 8px; border-radius:4px; }
    .pt-pat-row { font-size:.85rem; color:#555; margin-bottom:0.6rem; display:flex; align-items:center; }
    .pt-pat-row-lbl { width: 90px; color:#888; }
 
    .badge-e { display:inline-block; padding:2px 6px; border-radius:3px; font-size:.75rem; font-weight:700; background:#ffeaea; color:#c0392b; margin-left:4px; }
    .badge-o { display:inline-block; padding:2px 6px; border-radius:3px; font-size:.75rem; font-weight:700; background:#f0e0ff; color:#7b2ff7; margin-left:4px; }
    .badge-b { display:inline-block; padding:2px 6px; border-radius:3px; font-size:.75rem; font-weight:700; background:#e0fff5; color:#00856f; margin-left:4px; }
    .badge-s { display:inline-block; padding:2px 6px; border-radius:3px; font-size:.75rem; font-weight:700; background:#fff5e0; color:#c67c00; margin-left:4px; }

    /* NÚT CHỌN CHẾ ĐỘ PHÂN TÍCH (DẠNG PILL) */
    .mode-selector-wrap { display: flex; justify-content: center; align-items: center; gap: 15px; margin: 30px 0 20px 0; }
    .mode-lbl { font-weight: 900; color: #555; text-transform: uppercase; font-size: 1.1rem; }

    .pill-group { display: flex; background: #fff; border: 2px solid #dc3545; border-radius: 50px; overflow: hidden; box-shadow: 0 3px 6px rgba(0,0,0,0.05); }
    .pill-group input[type="radio"] { display: none; }
    .pill-group label { padding: 10px 30px; margin: 0; font-weight: 800; font-size: 0.95rem; color: #dc3545; cursor: pointer; transition: all 0.2s ease; }
    .pill-group input[type="radio"]:checked + label { background: #dc3545; color: #fff; }

    /* TABS CHIẾN LƯỢC */
    .nav-tabs-wrapper { display: flex; justify-content: center; gap: 10px; margin-bottom: 2rem; flex-wrap: wrap; }

    .tab-btn { background: #fff; color: #495057; border: 2px solid #dee2e6; padding: 10px 20px; font-size: 0.9rem; font-weight: 800; border-radius: 6px; cursor: pointer; text-transform: uppercase; transition: 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .tab-btn:hover { background: #f8f9fa; transform: translateY(-1px); }
    .tab-btn.active { background: #c0392b; color: #fff; border-color: #c0392b; box-shadow: 0 4px 10px rgba(192,57,43,0.3); }

    /* BẢNG GAN ĐỀ (ĐUÔI ĐẶC BIỆT) - PHỤC HỒI GIAO DIỆN GỐC CỦA BÁC */
    .gan-container { background: #fffdfd; border: 1px solid #f0e6d2; border-radius: 8px; padding: 20px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    .gan-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f0e6d2; padding-bottom: 12px; margin-bottom: 20px; }
    .gan-title { color: #c0392b; font-weight: 900; font-size: 1.2rem; text-transform: uppercase; margin: 0; }
    .gan-filter { display: flex; align-items: center; gap: 8px; font-size: 0.95rem; font-weight: 700; color: #555; }
    .gan-filter input { width: 60px; padding: 4px; border: 1px solid #dc3545; border-radius: 4px; text-align: center; color: #dc3545; font-weight: bold;}
    .gan-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
    .gan-item { display: flex; border: 1px solid #28a745; background: #fff; border-radius: 4px; overflow: hidden; height: 34px; line-height: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: transform 0.2s; }
    .gan-item:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .gan-item-num { width: 35%; text-align: center; font-weight: 900; font-size: 1.1rem; color: #dc3545; border-right: 1px solid #28a745; background: #fffafa; font-family: Consolas, monospace; }
    .gan-item-day { width: 65%; text-align: center; font-weight: bold; font-size: 0.95rem; color: #198754; background: #fff; }

    /* MEGA TABLE */
    .mega-table-container { overflow-x: auto; background: #fff; border: 1px solid #dee2e6; border-radius: 4px; box-shadow: 0 3px 10px rgba(0,0,0,0.05); padding: 5px; margin-bottom: 2rem; display: none; animation: fadeIn 0.3s ease; }
    .mega-table { width: 100%; border-collapse: collapse; text-align: center; font-size: 0.75rem; min-width: 900px; table-layout: fixed; }
    .mega-table th, .mega-table td { border: 1px solid #e9ecef; padding: 2px 1px; vertical-align: middle; height: 24px; white-space: nowrap; overflow: hidden; }
    .mega-table thead th { background: #f8f9fa; color: #333; font-weight: 800; text-transform: uppercase; padding: 4px 1px; font-size: 0.7rem; }
   
    .border-thick-right { border-right: 2px solid #adb5bd !important; }

    .bg-gdb { background: #fff5f5 !important; color: #c0392b !important; }
    .bg-g1 { background: #f0f8ff !important; color: #0d6efd !important; }
   
    .col-date { font-weight: 700; color: #555; background: #f8f9fa; position: sticky; left: 0; z-index: 2; box-shadow: 2px 0 5px rgba(0,0,0,0.05); width: 60px; font-size: 0.75rem; }
    
    .gan-row td { font-weight: 900; color: #c0392b; font-size: 0.85rem; background: #fffdfd; }

    .max-row td { font-weight: 800; color: #6c757d; font-size: 0.75rem; background: #f8f9fa; }

    .val-hot { color: #198754; font-size: 0.75rem; }
   
    .cell-hit { font-family: Consolas, monospace; font-weight: 900; font-size: 0.8rem; background: #c0392b; color: #fff; border-radius: 2px; display: block; padding: 1px 0; margin: 0 auto; width: 100%; max-width: 30px; box-shadow: 0 1px 2px rgba(192,57,43,0.3); }
    .cell-hit.g1 { background: #0d6efd; box-shadow: 0 1px 2px rgba(13,110,253,0.3); }
    .cell-miss { color: #dee2e6; font-size: 0.8rem; }

    .charts-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-top: 1rem; }

    @media(max-width: 1200px) { .charts-grid { grid-template-columns: repeat(2, 1fr); } }
    @media(max-width: 768px) { .charts-grid { grid-template-columns: 1fr; } }

    .chart-box { background: #fff; border: 1px solid #dee2e6; border-radius: 6px; padding: 10px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.03); }
    .chart-title { font-weight: 800; font-size: 0.85rem; margin-bottom: 5px; color: #333; text-transform: uppercase; }

    .line-chart-wrapper { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    .max-gan-summary-container { font-family: 'Segoe UI', sans-serif; background: #fff; border: 1px solid #cce5ff; border-radius: 8px; margin-bottom: 2rem; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); display: none; animation: fadeInSummary 0.3s ease;}
    .max-gan-title { background: #e7f1ff; color: #084298; padding: 12px 15px; font-weight: 800; font-size: 1.05rem; margin: 0; border-bottom: 1px solid #cce5ff; text-transform: uppercase; }
    .max-gan-content { padding: 15px 25px; display: grid; grid-template-columns: 1fr; gap: 20px; }

    @media(max-width: 768px) { .max-gan-content { grid-template-columns: 1fr; } }

    .max-gan-group-title { font-weight: 800; color: #c0392b; margin-bottom: 8px; font-size: 1rem; border-bottom: 1px dashed #f0d0d0; padding-bottom: 5px; text-transform: uppercase; }
    .max-gan-list { list-style: none; padding: 0; margin: 0; font-size: 0.9rem; color: #444; line-height: 2.2; }
    .max-gan-list li { position: relative; padding-left: 15px; margin-bottom: 6px; }
    .max-gan-list li::before { content: "•"; color: #084298; font-weight: bold; position: absolute; left: 0; }

    .text-gan-now { color: #008000; font-weight: 800; font-size: 0.95rem;}
    .text-gan-max { color: #dc3545; font-weight: 800; font-size: 0.95rem; }
    .text-gan-date { color: #6c757d; font-style: italic; font-size: 0.85rem; }

    @keyframes fadeInSummary { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

    /* === BẢNG LIỆT KÊ SỐ THUỘC BỘ (COLLAPSIBLE) === */
    .numset-section { margin-bottom: 2rem; display: none; animation: fadeIn 0.3s ease; }
    
    /* Ép tất cả các hộp nằm ngang trên 1 dòng, chia đều không gian */
    .numset-grid { display: flex; flex-wrap: nowrap; gap: 1rem; }
    
    @media(max-width: 992px) { .numset-grid { flex-wrap: wrap; } .numset-box { flex: 1 1 calc(50% - 1rem); } }
    @media(max-width: 768px) { .numset-grid { flex-direction: column; } .numset-box { flex: 1 1 100%; } }
    
    /* flex: 1 giúp các hộp tự động co giãn bằng nhau */
    .numset-box { flex: 1; min-width: 0; border: 1px solid #f0d0d0; border-radius: 8px; background: #fff; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); transition: box-shadow 0.2s; }
    
    .numset-box:hover { box-shadow: 0 4px 14px rgba(192,57,43,0.12); }
    .numset-header { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: linear-gradient(135deg, #c0392b, #e74c3c); color: #fff; cursor: pointer; user-select: none; }
    .numset-header:hover { background: linear-gradient(135deg, #a93226, #c0392b); }
    .numset-header-title { font-weight: 900; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .numset-header-count { font-size: 0.8rem; font-weight: 700; background: rgba(255,255,255,0.25); padding: 2px 10px; border-radius: 20px; }
    .numset-header-arrow { font-size: 1.2rem; font-weight: 900; transition: transform 0.3s ease; }
    .numset-header-arrow.collapsed { transform: rotate(-90deg); }
    .numset-body { padding: 12px; display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; animation: fadeIn 0.2s ease; }
    .numset-body.hidden { display: none; }
    .numset-num { text-align: center; font-family: Consolas, monospace; font-weight: 800; font-size: 1rem; color: #c0392b; background: #fffafa; border: 1px solid #f5c6cb; border-radius: 4px; padding: 5px 0; transition: all 0.15s ease; }
    .numset-num:hover { background: #c0392b; color: #fff; transform: scale(1.08); box-shadow: 0 2px 6px rgba(192,57,43,0.3); }
    .numset-toggle-all { display: flex; justify-content: center; margin-bottom: 12px; }
    .numset-toggle-btn { background: #fff; border: 2px solid #c0392b; color: #c0392b; padding: 8px 24px; border-radius: 50px; font-weight: 800; font-size: 0.9rem; cursor: pointer; transition: all 0.2s ease; text-transform: uppercase; }
    .numset-toggle-btn:hover { background: #c0392b; color: #fff; }

    /* --- PHẦN THÊM MỚI: Sidebar và bố cục biểu đồ tròn --- */
    .pie-analysis-wrapper { 
        display: flex; 
        gap: 20px; 
        margin-top: 2rem; 
        background: #fff; 
        padding: 20px; 
        border-radius: 12px; 
        border: 1px solid #dee2e6; 
    }
    .pie-sidebar { 
        width: 180px; 
        flex-shrink: 0; 
        display: flex; 
        flex-direction: column; 
        gap: 10px; 
        border-right: 2px dashed #eee; 
        padding-right: 15px; 
    }
    .filter-pie-btn { 
        background: #f8f9fa; 
        color: #333; 
        border: 1px solid #ddd; 
        padding: 12px; 
        border-radius: 8px; 
        font-weight: 800; 
        text-align: center; 
        cursor: pointer; 
        transition: all 0.2s; 
    }
    .filter-pie-btn:hover { background: #e9ecef; border-color: #c0392b; color: #c0392b; }
    .filter-pie-btn.active { 
        background: #c0392b; 
        color: #fff; 
        border-color: #c0392b; 
        box-shadow: 0 4px 8px rgba(192,57,43,0.3); 
        transform: translateX(5px); 
    }
    .pie-charts-main { flex-grow: 1; }
    .pie-charts-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    
    /* Hiệu ứng viền và bàn tay cho thẻ */
    .pie-chart-box { 
        cursor: pointer !important; 
        border: 2px solid #e9ecef !important; 
        transition: all 0.3s ease !important; 
    }
    .pie-chart-box:hover { 
        border-color: #0d6efd !important; 
        background: #f8fbff !important; 
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(13, 110, 253, 0.15) !important;
    }

    /* Vùng hiển thị bảng số liệu tập trung phía dưới 4 biểu đồ */
    .pie-mega-table-container {
        margin-top: 20px;
        background: #fff;
        border: 2px solid #0d6efd;
        border-radius: 8px;
        padding: 20px;
        display: none; /* Ẩn mặc định */
        animation: fadeInDown 0.4s ease;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    
    .pie-mega-table-title {
        color: #0d6efd;
        font-weight: 800;
        text-transform: uppercase;
        margin-bottom: 15px;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .pie-scroll-wrapper {
        overflow-x: auto;
        width: 100%;
    }

    .pie-mega-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px; /* Đảm bảo bảng trải dài */
    }

    .pie-mega-table th {
        background: #f8f9fa;
        color: #333;
        font-weight: 800;
        padding: 10px;
        border: 1px solid #dee2e6;
        width: 100px;
        text-align: center;
    }

    .pie-mega-table td {
        border: 1px solid #dee2e6;
        padding: 10px;
        text-align: center;
        font-family: Consolas, monospace;
        font-weight: 700;
        font-size: 1rem;
    }

    .pie-row-days td { background: #fffafa; color: #c0392b; }
    .pie-row-hits td { background: #f0f7ff; color: #0d6efd; }

    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="pt-wrap container-fluid px-4">
    <div class="pt-title">Phân Tích Giải ĐB & Giải Nhất</div>
    <div class="pt-controls">
        <form method="GET" action="{{ route('phan-tich') }}" style="display:flex; gap:1rem; align-items:center;">
            <label>Ngày chốt số:</label>
            <input type="date" name="date" value="{{ is_object($selectedDate) ? $selectedDate->format('Y-m-d') : $selectedDate }}">
            <input type="hidden" name="mode" value="{{ request('mode', '25') }}">
            <button type="submit" class="btn-pt">Phân Tích</button>
        </form>
    </div>

    {{-- KẾT QUẢ GĐB VÀ G1 --}}
    <div class="pt-section">
        <div class="pt-results">
            <div class="pt-prize">
                <div class="pt-prize-label">GIẢI ĐẶC BIỆT</div>
                <div class="pt-prize-num">{{ $gdbFull ?: '-----' }}</div>
                <div class="pt-extracted">
                    @if($extraction)
                        <div class="pt-ext">{{ $extraction->gdb_first2 }}</div><span class="pt-arrow">→</span><div class="pt-ext">{{ $extraction->gdb_last2 }}</div>
                    @endif
                </div>
            </div>
            <div class="pt-prize">
                <div class="pt-prize-label">GIẢI NHẤT</div>
                <div class="pt-prize-num">{{ $g1Full ?: '-----' }}</div>
                <div class="pt-extracted">
                    @if($extraction)
                       <div class="pt-ext" style="border-color:#0d6efd; color:#0d6efd;">{{ $extraction->g1_first2 }}</div><span class="pt-arrow">→</span><div class="pt-ext" style="border-color:#0d6efd; color:#0d6efd;">{{ $extraction->g1_last2 }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- 4 CARD PHÂN TÍCH ĐẦU - ĐUÔI --}}
    @if($extraction)
    <div class="pt-section">
        <div class="pt-section-title">Phân Tích Đầu - Đuôi</div>
        <div class="pt-patterns">
            @foreach([
                ['label' => 'ĐẦU ĐẶC BIỆT', 'val' => $extraction->gdb_first2],
                ['label' => 'CUỐI ĐẶC BIỆT', 'val' => $extraction->gdb_last2],
                ['label' => 'ĐẦU GIẢI NHẤT', 'val' => $extraction->g1_first2],
                ['label' => 'CUỐI GIẢI NHẤT', 'val' => $extraction->g1_last2],
            ] as $item)
                @php
                    $nVal = $item['val'];
                    $nInfo = $numbersMap[$nVal] ?? null;
                    $nFreq = isset($stats['frequency'][$nVal]) ? $stats['frequency'][$nVal] : 0;
                @endphp             
                @if($nInfo)
                <div class="pt-pat-box">
                    <div class="pt-pat-title">
                        {{ $item['label'] }} <span class="pt-pat-num">{{ $nVal }}</span>
                    </div>
                    <div class="pt-pat-row mt-2">
                        <span class="pt-pat-row-lbl">Hàng chục:</span> 
                        <b style="font-size:1.1rem; color:#333; width:15px; display:inline-block;">{{ $nInfo['head'] }}</b> 
                        <span class="badge-{{ $nInfo['head']%2==0 ? 'e' : 'o' }}">{{ $nInfo['head']%2==0 ? 'Chẵn' : 'Lẻ' }}</span>
                        <span class="badge-{{ $nInfo['head']>=5 ? 'b' : 's' }}">{{ $nInfo['head']>=5 ? 'Lớn' : 'Nhỏ' }}</span>
                    </div>
                    <div class="pt-pat-row">
                        <span class="pt-pat-row-lbl">Hàng đơn vị:</span> 
                        <b style="font-size:1.1rem; color:#333; width:15px; display:inline-block;">{{ $nInfo['tail'] }}</b> 
                        <span class="badge-{{ $nInfo['tail']%2==0 ? 'e' : 'o' }}">{{ $nInfo['tail']%2==0 ? 'Chẵn' : 'Lẻ' }}</span>
                        <span class="badge-{{ $nInfo['tail']>=5 ? 'b' : 's' }}">{{ $nInfo['tail']>=5 ? 'Lớn' : 'Nhỏ' }}</span>
                    </div>
                    <div class="pt-pat-row" style="margin-top:0.4rem;">
                        <span class="pt-pat-row-lbl">Nhịp 30 ngày:</span> 
                        <span style="font-size: 0.85rem;">Nổ <b style="color:#e74c3c;">{{ $nFreq }}</b> lần | <b style="color:#00856f">Vừa nổ xong</b></span>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- CHẾ ĐỘ PHÂN TÍCH (DẠNG PILL TUYỆT ĐẸP) --}}
    <div class="mode-selector-wrap">
        <span class="mode-lbl">Chế độ phân tích:</span>
        <div class="pill-group">
            <input type="radio" name="mode_chia_bo" id="mode25" value="25" autocomplete="off" 
                   {{ request('mode', '25') == '25' ? 'checked' : '' }} onchange="switchMode('25')">
            <label for="mode25">Chia 4 Bộ (25 Số)</label>
            <input type="radio" name="mode_chia_bo" id="mode20" value="20" autocomplete="off" 
                   {{ request('mode') == '20' ? 'checked' : '' }} onchange="switchMode('20')">
            <label for="mode20">Chia 5 Bộ (20 Số)</label>
        </div>
    </div>

    {{-- BẢNG LIỆT KÊ SỐ THUỘC BỘ (COLLAPSIBLE) --}}
    @php
        // Khởi tạo mảng nếu chưa có
        $numberSets = $numberSets ?? [];

        // 1. TỰ ĐỘNG SINH DỮ LIỆU CHO BỘ CHIA 4 (Dư 0, 1, 2, 3)
        if(!isset($numberSets['mod4'])) {
            $mod4Sets = ['Dư 1' => [], 'Dư 2' => [], 'Dư 3' => [], 'Dư 0' => []];
            for($i = 0; $i < 100; $i++) {
                $m = $i % 4; 
                $mod4Sets['Dư '.$m][] = str_pad($i, 2, '0', STR_PAD_LEFT);
            }
            $numberSets['mod4'] = [
                'name' => 'Chia 4',
                'cols' => ['Dư 1', 'Dư 2', 'Dư 3', 'Dư 0'],
                'sets' => $mod4Sets
            ];
        }
    @endphp

    @if(isset($numberSets) && count($numberSets) > 0)
    <script>window.numberSetsData = @json($numberSets);</script>
    @foreach($numberSets as $nsKey => $nsData)
    <div id="numset-section-{{$nsKey}}" class="numset-section">
        <div class="numset-toggle-all">
            <button class="numset-toggle-btn" onclick="toggleAllNumsets('{{$nsKey}}')" id="numset-toggle-btn-{{$nsKey}}">📋 Xem danh sách số thuộc bộ {{ $nsData['name'] }}</button>
        </div>
        <div id="numset-panels-{{$nsKey}}" style="display: none;">
            <div class="numset-grid">
                @foreach($nsData['cols'] as $col)
                <div class="numset-box">
                    <div class="numset-header" onclick="toggleNumsetBody(this)">
                        <span class="numset-header-title">{{ $col }}</span>
                        <span class="numset-header-count">{{ count($nsData['sets'][$col]) }} số</span>
                        <span class="numset-header-arrow">▼</span>
                    </div>
                    <div class="numset-body">
                        @foreach($nsData['sets'][$col] as $num)
                        <div class="numset-num">{{ $num }}</div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach
    @endif

    {{-- TABS CHIẾN LƯỢC --}}
    @if(isset($excelData) && count($excelData) > 0)
    <div class="nav-tabs-wrapper">
        @foreach($excelData as $strKey => $data)
            <button class="tab-btn" id="tab-btn-{{$strKey}}" onclick="openMegaTable('{{$strKey}}')">{{ $data['name'] }}</button>
        @endforeach
    </div>

    {{-- MEGA EXCEL KHU VỰC --}}
    @foreach($excelData as $strKey => $data)
        @php 
            $f1 = $data['fields']['gdb_first2']; $f2 = $data['fields']['gdb_last2'];
            $f3 = $data['fields']['g1_first2'];  $f4 = $data['fields']['g1_last2'];
            $colCount = count($data['cols']); 
        @endphp
        <script>
            window.megaData = window.megaData || {};
            window.megaData["{{$strKey}}"] = @json($data);
        </script>
        <div id="mega-{{$strKey}}" class="mega-table-container">
            <table class="mega-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="col-date border-thick-right" style="vertical-align: bottom; padding-bottom: 5px;">Ngày</th>
                        <th colspan="{{ $colCount }}" class="bg-gdb border-thick-right">{{ $f1['name'] }}</th>
                        <th colspan="{{ $colCount }}" class="bg-gdb border-thick-right">{{ $f2['name'] }}</th>
                        <th colspan="{{ $colCount }}" class="bg-g1 border-thick-right">{{ $f3['name'] }}</th>
                        <th colspan="{{ $colCount }}" class="bg-g1">{{ $f4['name'] }}</th>
                    </tr>
                    <tr>
                        @foreach([$f1, $f2, $f3, $f4] as $idx => $f)
                            @foreach($data['cols'] as $cIdx => $col)
                                <th class="{{ $cIdx == $colCount - 1 && $idx < 3 ? 'border-thick-right' : '' }}">{{ $col }}</th>
                            @endforeach
                        @endforeach
                    </tr>
                    <tr class="gan-row">
                        <td class="col-date border-thick-right">Gan HT</td>
                        @foreach([$f1, $f2, $f3, $f4] as $idx => $f)
                            @foreach($data['cols'] as $cIdx => $col)
                                <td class="{{ $cIdx == $colCount - 1 && $idx < 3 ? 'border-thick-right' : '' }}">
                                    @if($f['gan'][$col] === 0) <span class="val-hot">Vừa nổ</span> @else {{ $f['gan'][$col] }} @endif
                                </td>
                            @endforeach
                        @endforeach
                    </tr>
                    <tr class="max-row">
                        <td class="col-date border-thick-right">Max Gan</td>
                        @foreach([$f1, $f2, $f3, $f4] as $idx => $f)
                            @foreach($data['cols'] as $cIdx => $col)
                                <td class="{{ $cIdx == $colCount - 1 && $idx < 3 ? 'border-thick-right' : '' }}">{{ $f['maxGan'][$col] }}</td>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($f1['history'] as $i => $row1)
                        @php $row2 = $f2['history'][$i]; $row3 = $f3['history'][$i]; $row4 = $f4['history'][$i]; @endphp
                        <tr>
                            <td class="col-date border-thick-right">{{ $row1['date'] }}</td> 
                            @foreach($data['cols'] as $cIdx => $col)
                                <td class="{{ $cIdx == $colCount - 1 ? 'border-thick-right' : '' }}">
                                    @if($row1['pattern'] === $col) <span class="cell-hit">{{ $row1['val'] }}</span> @else <span class="cell-miss">-</span> @endif
                                </td>
                            @endforeach
                            @foreach($data['cols'] as $cIdx => $col)
                                <td class="{{ $cIdx == $colCount - 1 ? 'border-thick-right' : '' }}">
                                    @if($row2['pattern'] === $col) <span class="cell-hit">{{ $row2['val'] }}</span> @else <span class="cell-miss">-</span> @endif
                                </td>
                            @endforeach
                            @foreach($data['cols'] as $cIdx => $col)
                                <td class="{{ $cIdx == $colCount - 1 ? 'border-thick-right' : '' }}">
                                    @if($row3['pattern'] === $col) <span class="cell-hit g1">{{ $row3['val'] }}</span> @else <span class="cell-miss">-</span> @endif
                                </td>
                            @endforeach
                            @foreach($data['cols'] as $cIdx => $col)
                                <td>
                                    @if($row4['pattern'] === $col) <span class="cell-hit g1">{{ $row4['val'] }}</span> @else <span class="cell-miss">-</span> @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="charts-grid">
                @foreach([
                    ['id' => 'gdb1', 'title' => $f1['name'], 'gan' => $f1['gan'], 'color' => '#8b8dfb'],
                    ['id' => 'gdb2', 'title' => $f2['name'], 'gan' => $f2['gan'], 'color' => '#8b8dfb'],
                    ['id' => 'g1_1', 'title' => $f3['name'], 'gan' => $f3['gan'], 'color' => '#6c757d'],
                    ['id' => 'g1_2', 'title' => $f4['name'], 'gan' => $f4['gan'], 'color' => '#6c757d']
                ] as $chart)
                <div class="chart-box">
                    <div class="chart-title" style="color:{{$chart['color']}}">{{ $chart['title'] }}</div>
                    <canvas id="chart_{{$strKey}}_{{$chart['id']}}" width="300" height="200" style="width:100%; height:200px;"></canvas>
                    <script>
                        window.chartDataMap = window.chartDataMap || {};
                        window.chartDataMap["{{$strKey}}_{{$chart['id']}}"] = { gan: @json($chart['gan']), color: "{{$chart['color']}}" };
                    </script>
                </div>
                @endforeach
            </div>

            {{-- BIỂU ĐỒ TRÒN VỚI SIDEBAR LỌC --}}
            <div class="pie-analysis-wrapper">
                <div class="pie-sidebar">
                    <div style="font-size: 0.75rem; font-weight: 900; color: #888; margin-bottom: 5px; text-transform: uppercase;">Lọc theo bộ:</div>
                    @foreach($data['cols'] as $idx => $col)
                        <div class="filter-pie-btn {{ $idx === 0 ? 'active' : '' }}" 
                             onclick="switchPieFilter('{{$strKey}}', '{{$col}}', this)">
                            {{ $col }}
                        </div>
                    @endforeach
                </div>

                <div class="pie-charts-main">
                    <h5 class="text-center fw-bold mb-4" style="color: #333;">
                        TỶ LỆ NGÀY NỔ GAN: <span id="active_col_name_{{$strKey}}" style="color:#c0392b">{{ $data['cols'][0] }}</span>
                    </h5>
                    <div class="pie-charts-grid">
                        @foreach([
                            ['id' => 'pie_gdb1', 'title' => 'ĐẦU ĐẶC BIỆT'],
                            ['id' => 'pie_gdb2', 'title' => 'CUỐI ĐẶC BIỆT'],
                            ['id' => 'pie_g1_1', 'title' => 'ĐẦU GIẢI NHẤT'],
                            ['id' => 'pie_g1_2', 'title' => 'CUỐI GIẢI NHẤT']
                        ] as $pChart)
                        <div class="pie-chart-box" onclick="showPieDataTable('{{$strKey}}_{{$pChart['id']}}', 'Thống kê gan: {{ $pChart['title'] }}', '{{$strKey}}')">
                            <div class="pie-chart-title">{{ $pChart['title'] }}</div>
                            <div style="height: 220px; position: relative;">
                                <canvas id="{{$strKey}}_{{$pChart['id']}}"></canvas>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- VÙNG HIỂN THỊ BẢNG SỐ LIỆU TRẢI DÀI PHÍA DƯỚI --}}
            <div id="{{$strKey}}_pie_mega_container" class="pie-mega-table-container">
                <div class="pie-mega-table-title">
                    <span id="{{$strKey}}_pie_title_text">Số liệu thống kê</span>
                    <button class="btn btn-sm btn-outline-secondary" onclick="this.parentElement.parentElement.style.display='none'">Đóng bảng ✕</button>
                </div>
                <div class="pie-scroll-wrapper">
                    <table class="pie-mega-table">
                        <tr class="pie-row-days" id="{{$strKey}}_pie_row_days">
                            <th>Số ngày</th>
                        </tr>
                        <tr class="pie-row-hits" id="{{$strKey}}_pie_row_hits">
                            <th>Số lần nổ</th>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="line-charts-wrapper mt-5 pt-4" style="border-top: 2px dashed #dee2e6;">
                <h4 class="text-center fw-bold mb-3" style="color:#c0392b;">BIỂU ĐỒ ĐƯỜNG: LỊCH SỬ GAN KHAN (>= 15 NGÀY) TỪ 1996 ĐẾN NAY</h4>
                <div class="d-flex justify-content-center gap-2 mb-3 line-chart-tabs-{{$strKey}}">
                    <button class="tab-btn active" onclick="switchLineChart('{{$strKey}}', 'gdb_first2', this)">ĐẦU ĐẶC BIỆT</button>
                    <button class="tab-btn" onclick="switchLineChart('{{$strKey}}', 'gdb_last2', this)">CUỐI ĐẶC BIỆT</button>
                    <button class="tab-btn" onclick="switchLineChart('{{$strKey}}', 'g1_first2', this)">ĐẦU GIẢI NHẤT</button>
                    <button class="tab-btn" onclick="switchLineChart('{{$strKey}}', 'g1_last2', this)">CUỐI GIẢI NHẤT</button>
                </div>
                <div class="line-charts-single-container">
                    @foreach(['gdb_first2', 'gdb_last2', 'g1_first2', 'g1_last2'] as $idx => $fKey)
                    <div class="line-chart-outer" id="wrap_{{$strKey}}_{{$fKey}}" style="display: {{ $idx == 0 ? 'block' : 'none' }}; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px 0 0 0; box-shadow: 0 4px 10px rgba(0,0,0,0.05); background:#fff;">
                        <div class="text-center mb-1 px-3">
                            <h5 id="chart_title_{{$strKey}}_{{$fKey}}" class="fw-bold" style="color: #333; font-size: 1.1rem; text-transform: uppercase;">-</h5>
                            <div id="chart_legend_{{$strKey}}_{{$fKey}}" class="d-flex justify-content-center flex-wrap mt-1 pb-1" style="border-bottom: 1px dashed #eee;"></div>
                        </div>
                        {{-- Layout 2 Khung: Ép sát rạt vào nhau --}}
                        <div style="position: relative; width: 100%; height: 420px; display: flex; align-items: stretch; background: #fff;">
                            {{-- Khung Trục Y (Ép chữ sát lề phải, không viền) --}}
                            <div style="width: 45px; flex-shrink: 0; z-index: 10; background: #fff;">
                                <canvas id="yAxis_{{$strKey}}_{{$fKey}}" style="width: 100%; height: 100%;"></canvas>
                            </div>
                            {{-- Khung Biểu Đồ Cuộn --}}
                            <div id="scroll_{{$strKey}}_{{$fKey}}" class="custom-scroll" style="flex-grow: 1; overflow-x: auto; overflow-y: hidden;">
                                <div id="inner_{{$strKey}}_{{$fKey}}" style="height: 100%; min-width: 100%;">
                                    <canvas id="line_{{$strKey}}_{{$fKey}}" style="width: 100%; height: 100%;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
    @endif
    <div id="append-summary-section">
        @if(isset($excelData) && count($excelData) > 0)
            @foreach($excelData as $strKey => $data)
                <div id="summary-{{$strKey}}" class="max-gan-summary-container">
                    <h4 class="max-gan-title">TỔNG KẾT KỶ LỤC GAN CỰC ĐẠI - CHIẾN LƯỢC {{ $data['name'] }}</h4>
                    <div class="max-gan-content">
                        @foreach([
                            'gdb_first2' => 'ĐẦU ĐẶC BIỆT', 
                            'gdb_last2'  => 'CUỐI ĐẶC BIỆT', 
                            'g1_first2'  => 'ĐẦU GIẢI NHẤT', 
                            'g1_last2'   => 'CUỐI GIẢI NHẤT'
                        ] as $keyField => $labelField)
                            @php $f = $data['fields'][$keyField]; @endphp
                            <div class="max-gan-group">
                                <div class="max-gan-group-title">{{ $labelField }}</div>
                                <ul class="max-gan-list">
                                    @foreach($data['cols'] as $col)
                                        @php
                                            $tenDayDu = [
                                                'TT' => 'To To', 'NN' => 'Nhỏ Nhỏ', 'TN' => 'To Nhỏ', 'NT' => 'Nhỏ To',
                                                'CC' => 'Chẵn Chẵn', 'LL' => 'Lẻ Lẻ', 'LC' => 'Lẻ Chẵn', 'CL' => 'Chẵn Lẻ',
                                                'TC' => 'To Chẵn', 'TL' => 'To Lẻ', 'NC' => 'Nhỏ Chẵn', 'NL' => 'Nhỏ Lẻ',
                                                'CT' => 'Chẵn To', 'LT' => 'Lẻ To', 'CN' => 'Chẵn Nhỏ', 'LN' => 'Lẻ Nhỏ',
                                                'D0_19' => 'Dải 00-19', 'D20_39' => 'Dải 20-39', 'D40_59' => 'Dải 40-59', 'D60_79' => 'Dải 60-79', 'D80_99' => 'Dải 80-99',
                                                'DU0' => 'Dư 0', 'DU1' => 'Dư 1', 'DU2' => 'Dư 2', 'DU3' => 'Dư 3', 'DU4' => 'Dư 0',
                                                'DAU05' => 'Đầu 0-5', 'DAU16' => 'Đầu 1-6', 'DAU27' => 'Đầu 2-7', 'DAU38' => 'Đầu 3-8', 'DAU49' => 'Đầu 4-9',
                                                'DUOI05' => 'Đuôi 0-5', 'DUOI16' => 'Đuôi 1-6', 'DUOI27' => 'Đuôi 2-7', 'DUOI38' => 'Đuôi 3-8', 'DUOI49' => 'Đuôi 4-9',
                                                'TONG05' => 'Tổng 0-5', 'TONG16' => 'Tổng 1-6', 'TONG27' => 'Tổng 2-7', 'TONG38' => 'Tổng 3-8', 'TONG49' => 'Tổng 4-9',
                                                'HIEU05' => 'Hiệu 0-5', 'HIEU19' => 'Hiệu 1-9', 'HIEU28' => 'Hiệu 2-8', 'HIEU37' => 'Hiệu 3-7', 'HIEU46' => 'Hiệu 4-6'
                                            ];
                                            $tenHienThi = $tenDayDu[$col] ?? $col;
                                        @endphp                                      
                                        <li>
                                            Bộ <strong style="color:#c0392b; font-size:1.05rem;">{{ $tenHienThi }}</strong> 
                                            @if($f['gan'][$col] == 0)
                                                <strong class="text-gan-now">vừa nổ xong</strong>, 
                                            @else
                                                đã <strong class="text-gan-now">{{ $f['gan'][$col] }}</strong> ngày chưa ra, 
                                            @endif
                                            cực đại là <strong class="text-gan-max">{{ $f['maxGan'][$col] }}</strong> ngày 
                                            <span class="text-gan-date">(từ {{ $f['maxGanDates'][$col] ?? '---' }})</span>
                                            <a href="javascript:void(0)" onclick="let panel = this.nextElementSibling; if(panel.style.display === 'none' || panel.style.display === '') { panel.style.display = 'block'; this.innerText = '[Ẩn bớt]'; filterGanCycles(panel.querySelector('button')); } else { panel.style.display = 'none'; this.innerText = '[Chi tiết]'; }" style="color: #0d6efd; font-size: 0.85rem; font-weight: bold; text-decoration: none; margin-left: 5px;">[Chi tiết]</a>                                        
                                            <div class="cycle-panel" style="display: none; margin-top: 8px; border: 1px solid #ced4da; border-radius: 4px; padding: 10px; background: #f8f9fa; box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);">
                                                <div style="display: flex; gap: 8px; align-items: center; border-bottom: 1px solid #dee2e6; padding-bottom: 8px; margin-bottom: 10px; font-size: 0.85rem; flex-wrap: wrap;">
                                                    <span style="font-weight: 600; color: #444;">Từ:</span>
                                                    <input type="date" class="cy-from" value="{{ \Carbon\Carbon::parse($selectedDate ?? now())->subYears(30)->format('Y-m-d') }}" style="padding: 2px 4px; border: 1px solid #ccc; border-radius: 3px; outline: none;">
                                                    <span style="font-weight: 600; color: #444;">đến:</span>

                                                    <input type="date" class="cy-to" value="{{ \Carbon\Carbon::parse($selectedDate ?? now())->format('Y-m-d') }}" style="padding: 2px 4px; border: 1px solid #ccc; border-radius: 3px; outline: none;">

                                                    

                                                    <span style="font-weight: 600; color: #444;">Min:</span>

                                                    <input type="number" class="cy-min" value="10" min="1" style="width: 50px; padding: 2px 4px; border: 1px solid #ccc; border-radius: 3px; outline: none; text-align: center;">

                                                    

                                                    <button type="button" onclick="filterGanCycles(this)" style="background: #0d6efd; color: white; border: none; padding: 3px 12px; border-radius: 3px; font-weight: bold; cursor: pointer;">Thống kê</button>

                                                </div>

                                                <div class="cycle-list" style="max-height: 220px; overflow-y: auto; padding-right: 5px;">

                                                    @if(count($f['ganCycles'][$col]) > 0 || $f['gan'][$col] > 0)

                                                        

                                                        @if($f['gan'][$col] > 0)

                                                            @php 

                                                                $w = $f['maxGan'][$col] > 0 ? ($f['gan'][$col] / $f['maxGan'][$col]) * 100 : 100;

                                                                $isMax = $f['gan'][$col] >= $f['maxGan'][$col];

                                                                $bg = $isMax ? '#e74c3c' : '#4dabf7'; 

                                                            @endphp

                                                            <div class="cy-item" data-len="{{ $f['gan'][$col] }}" data-start="hiện tại" data-end="hiện tại" style="margin-bottom: 5px; display: flex;">

                                                                <div style="background: {{ $bg }}; color: #fff; padding: 4px 8px; border-radius: 2px; width: {{ $w }}%; min-width: max-content; font-size: 0.85rem; {{ $isMax ? 'font-weight: bold; box-shadow: 0 0 5px rgba(231,76,60,0.5);' : '' }}">

                                                                    <strong>{{ $f['gan'][$col] }}</strong> ngày (Gan hiện tại chưa ra)

                                                                </div>

                                                            </div>

                                                        @endif



                                                        @foreach($f['ganCycles'][$col] as $cycle)

                                                            @if($cycle['length'] >= 3)

                                                                @php 

                                                                    $w = $f['maxGan'][$col] > 0 ? ($cycle['length'] / $f['maxGan'][$col]) * 100 : 100;

                                                                    $isMax = $cycle['length'] >= $f['maxGan'][$col];

                                                                    $bg = $isMax ? '#e74c3c' : '#4dabf7'; 

                                                                @endphp

                                                                <div class="cy-item" data-len="{{ $cycle['length'] }}" data-start="{{ $cycle['from'] }}" data-end="{{ $cycle['to'] }}" style="margin-bottom: 5px; display: flex;">

                                                                    <div style="background: {{ $bg }}; color: #fff; padding: 4px 8px; border-radius: 2px; width: {{ $w }}%; min-width: max-content; font-size: 0.85rem; {{ $isMax ? 'font-weight: bold; box-shadow: 0 0 5px rgba(231,76,60,0.5);' : '' }}">

                                                                        <strong>{{ $cycle['length'] }}</strong> ngày (từ {{ $cycle['from'] }} đến {{ $cycle['to'] }})

                                                                    </div>

                                                                </div>

                                                            @endif

                                                        @endforeach

                                                    @else

                                                        <div style="color: #888; font-style: italic; font-size: 0.85rem;">Chưa có chu kỳ gan nào đáng kể.</div>

                                                    @endif

                                                </div>

                                            </div>

                                        </li>

                                    @endforeach

                                </ul>

                            </div>

                        @endforeach

                    </div>

                </div>

            @endforeach

        @endif

    </div>

    {{-- BẢNG LÔ GAN ĐUÔI ĐẶC BIỆT NẰM NGAY DƯỚI TABS --}}

    @if(isset($ganGdbTail))

    <div class="gan-container">

        <div class="gan-header">

            <h4 class="gan-title">Đuôi Đặc Biệt Lâu Chưa Ra</h4>

            <div class="gan-filter">

                <label for="filterGanInput">Lọc biên độ gan >=</label>
  
                <input type="number" id="filterGanInput" value="150" min="0" title="Nhập số ngày gan tối thiểu"> 

                <span>ngày</span>

            </div>

        </div>

        <div class="gan-grid" id="ganGrid">

            @foreach($ganGdbTail as $num => $days)

                <div class="gan-item" data-gan="{{ $days }}">

                    <div class="gan-item-num">{{ $num }}</div>

                    <div class="gan-item-day">{{ $days }} ngày</div>

                </div>

            @endforeach

        </div>

    </div>

    @endif

</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>

    document.getElementById('filterGanInput').addEventListener('input', function() {

        let minGan = parseInt(this.value) || 0;

        let items = document.querySelectorAll('.gan-item');

        items.forEach(function(item) {

            let ganDays = parseInt(item.getAttribute('data-gan'));

            item.style.display = (ganDays >= minGan) ? 'flex' : 'none';

        });

    });

    // Gọi event mồi để cập nhật lại đúng số lượng 

    if(document.getElementById('filterGanInput')) {

        document.getElementById('filterGanInput').dispatchEvent(new Event('input'));

    }

    // Thêm hàm chuyển đổi bộ lọc
    function switchPieFilter(strKey, colName, btnEl) {
        // Cập nhật trạng thái nút
        btnEl.parentElement.querySelectorAll('.filter-pie-btn').forEach(b => b.classList.remove('active'));
        btnEl.classList.add('active');
        
        // Cập nhật tên hiển thị
        document.getElementById(`active_col_name_${strKey}`).innerText = colName;

        // Vẽ lại 4 biểu đồ tròn dựa trên cột được chọn
        let data = window.megaData[strKey];
        let pConfigs = [
            {id: 'pie_gdb1', k: 'gdb_first2'},
            {id: 'pie_gdb2', k: 'gdb_last2'},
            {id: 'pie_g1_1', k: 'g1_first2'},
            {id: 'pie_g1_2', k: 'g1_last2'}
        ];
        
        pConfigs.forEach(c => {
            renderPieChart(`${strKey}_${c.id}`, data.fields[c.k].ganCycles, [colName]);
        });

        // Ẩn bảng số liệu cũ
        document.getElementById(strKey + '_pie_mega_container').style.display = 'none';
    }

    // Cập nhật hàm openMegaTable để nạp mặc định
    function openMegaTable(strKey) {
        document.querySelectorAll('.mega-table-container').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.nav-tabs-wrapper .tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.numset-section').forEach(el => el.style.display = 'none');

        let targetMega = document.getElementById('mega-' + strKey);
        if(targetMega) targetMega.style.display = 'block';
        document.getElementById('tab-btn-' + strKey).classList.add('active');
        let nsSection = document.getElementById('numset-section-' + strKey);
        if(nsSection) nsSection.style.display = 'block';

        if(!window.megaData || !window.megaData[strKey]) return;
        let data = window.megaData[strKey];

        // Vẽ biểu đồ cột (giữ nguyên code cũ của bác)
        let configs = [
            {id:'gdb1',k:'gdb_first2',c:'#8b8dfb'},
            {id:'gdb2',k:'gdb_last2',c:'#8b8dfb'},
            {id:'g1_1',k:'g1_first2',c:'#6c757d'},
            {id:'g1_2',k:'g1_last2',c:'#6c757d'}
        ];
        let allV = []; configs.forEach(c => { for(let k in data.fields[c.k].gan) allV.push(data.fields[c.k].gan[k]); });
        let gMax = Math.max(...allV) || 1;
        configs.forEach(c => drawGanChart(`chart_${strKey}_${c.id}`, data.fields[c.k].gan, c.c, gMax));

        // VẼ PIE CHARTS MẶC ĐỊNH (Cột đầu tiên của bộ)
        let defCol = data.cols[0];
        configs.forEach(c => {
            renderPieChart(`${strKey}_pie_${c.id}`, data.fields[c.k].ganCycles, [defCol]);
        });

        drawLineCharts(strKey);
    }

    // Hàm ẩn/hiện bảng số liệu
    function togglePieTable(wrapperId) {
        let el = document.getElementById(wrapperId);
        if (el.style.display === "none" || el.style.display === "") {
            el.style.display = "block";
        } else {
            el.style.display = "none";
        }
    }

    // Biến toàn cục lưu trữ dữ liệu để truy xuất khi click
    window.pieRawDataMap = window.pieRawDataMap || {};

    function showPieDataTable(chartId, title, strKey) {
        // strKey bây giờ được truyền trực tiếp (ví dụ: 'tn_cl' hoặc 'mod_5')
        let container = document.getElementById(strKey + '_pie_mega_container');
        let titleEl = document.getElementById(strKey + '_pie_title_text');
        let rowDays = document.getElementById(strKey + '_pie_row_days');
        let rowHits = document.getElementById(strKey + '_pie_row_hits');

        if (!container) {
            console.error("Không tìm thấy container cho bộ:", strKey);
            return;
        }

        // Nếu nhấn lại chính cái đang mở thì ẩn đi
        if (container.style.display === 'block' && window.currentPieActive === chartId) {
            container.style.display = 'none';
            window.currentPieActive = null;
            return;
        }

        let data = window.pieRawDataMap[chartId];
        if (!data || data.labels.length === 0) {
            alert("Bộ này chưa có dữ liệu gan >= 15 ngày để hiển thị bảng.");
            return;
        }

        // Cập nhật nội dung bảng
        titleEl.innerText = title;
        rowDays.innerHTML = '<th>Số ngày</th>' + data.labels.map(l => `<td>${l}n</td>`).join('');
        rowHits.innerHTML = '<th>Số lần nổ</th>' + data.values.map(v => `<td>${v}</td>`).join('');

        // Hiển thị bảng
        container.style.display = 'block';
        window.currentPieActive = chartId;
        
        // Cuộn xuống để người dùng thấy bảng ngay
        container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function renderPieChart(canvasId, ganCyclesData, cols) {
        let canvas = document.getElementById(canvasId); 
        if(!canvas) return;

        let dayCounts = {};
        cols.forEach(col => { 
            (ganCyclesData[col] || []).forEach(cy => { 
                if(cy.length >= 15) dayCounts[cy.length] = (dayCounts[cy.length] || 0) + 1; 
            }); 
        });

        let labels = Object.keys(dayCounts).sort((a,b)=>a-b);
        let values = labels.map(l => dayCounts[l]);
        window.pieRawDataMap[canvasId] = { labels, values };

        if(window.myPieCharts && window.myPieCharts[canvasId]) window.myPieCharts[canvasId].destroy();
        window.myPieCharts = window.myPieCharts || {};

        if(labels.length === 0) { 
            let ctx = canvas.getContext('2d'); 
            ctx.clearRect(0,0,canvas.width,canvas.height);
            ctx.font="12px Arial"; ctx.textAlign="center"; 
            ctx.fillText("Không có dữ liệu >= 15 ngày", canvas.width/2, 100); 
            return; 
        }

        // --- PHẦN THAY ĐỔI: TẠO MÀU TỰ ĐỘNG KHÔNG TRÙNG LẶP ---
        let bgColors = labels.map((_, i) => {
            // Chia đều 360 độ của vòng tròn màu cho số lượng miếng bánh
            // Hệ màu HSL: H (Sắc độ 0-360), S (Độ bão hòa 70%), L (Độ sáng 55%)
            let hue = (i * (360 / labels.length)) % 360;
            return `hsl(${hue}, 70%, 55%)`;
        });

        window.myPieCharts[canvasId] = new Chart(canvas, {
            type: 'pie', 
            data: { 
                labels: labels.map(l => l + " ngày"), 
                datasets: [{ 
                    data: values, 
                    backgroundColor: bgColors, // Mỗi phần 1 màu riêng
                    borderColor: '#fff',       // Viền trắng để tách biệt rõ các miếng
                    borderWidth: 2, 
                    hoverOffset: 15 
                }] 
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { 
                        position: 'right', 
                        labels: { 
                            boxWidth: 10, 
                            font: { size: 9 },
                            padding: 10
                        } 
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                return ` ${context.label}: ${context.raw} lần (${((context.raw / total) * 100).toFixed(1)}%)`;
                            }
                        }
                    }
                } 
            }
        });
    }

    // === TOGGLE BẢNG LIỆT KÊ SỐ ===
    function toggleAllNumsets(strKey) {
        let panels = document.getElementById('numset-panels-' + strKey);
        let btn = document.getElementById('numset-toggle-btn-' + strKey);
        if (!panels) return;
        if (panels.style.display === 'none') {
            panels.style.display = 'block';
            if (btn) btn.textContent = '✕ Đóng danh sách số';
        } else {
            panels.style.display = 'none';
            let name = window.numberSetsData && window.numberSetsData[strKey] ? window.numberSetsData[strKey].name : '';
            if (btn) btn.textContent = '📋 Xem danh sách số thuộc bộ ' + name;
        }
    }

    function toggleNumsetBody(headerEl) {
        let body = headerEl.nextElementSibling;
        let arrow = headerEl.querySelector('.numset-header-arrow');
        if (body.classList.contains('hidden')) {
            body.classList.remove('hidden');
            if (arrow) arrow.classList.remove('collapsed');
        } else {
            body.classList.add('hidden');
            if (arrow) arrow.classList.add('collapsed');
        }
    }



    function switchLineChart(strKey, fieldKey, btnElement) {

        let wrappers = document.querySelectorAll(`[id^="wrap_${strKey}_"]`);

        wrappers.forEach(w => w.style.display = 'none');

        

        let btns = document.querySelectorAll(`.line-chart-tabs-${strKey} .tab-btn`);

        btns.forEach(b => b.classList.remove('active'));

        

        let targetWrap = document.getElementById(`wrap_${strKey}_${fieldKey}`);

        if(targetWrap) targetWrap.style.display = 'block';

        if(btnElement) btnElement.classList.add('active');



        let scrollDiv = document.getElementById(`scroll_${strKey}_${fieldKey}`);

        if (scrollDiv) {

            scrollDiv.scrollLeft = scrollDiv.scrollWidth;

        }

    }

    // vẽ nét đứt và hiển thị hộp ngày tháng khi rê chuột
    const crosshairPlugin = {
        id: 'crosshair',
        afterDraw: (chart) => {
            if (chart.tooltip?._active && chart.tooltip._active.length) {
                const activePoint = chart.tooltip._active[0];
                const ctx = chart.ctx;
                const x = activePoint.element.x;
                const y = activePoint.element.y;
                const bottomY = chart.scales.y.bottom; 

                ctx.save();
                ctx.beginPath();
                ctx.moveTo(x, y);
                ctx.lineTo(x, bottomY);
                ctx.lineWidth = 1.5;
                ctx.strokeStyle = 'rgba(220, 53, 69, 0.9)'; 
                ctx.setLineDash([5, 5]);
                ctx.stroke();

                const dateText = chart.data.labels[activePoint.index];
                ctx.font = 'bold 11px Consolas, monospace';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                const tw = ctx.measureText(dateText).width;
                const boxWidth = tw + 14;
                
                let boxX = x - boxWidth/2;
                if (boxX < 0) boxX = 0; 
                if (boxX + boxWidth > chart.width) boxX = chart.width - boxWidth;

                ctx.fillStyle = '#fff';
                ctx.fillRect(boxX, bottomY + 4, boxWidth, 18);
                ctx.strokeStyle = '#dc3545';
                ctx.setLineDash([]);
                ctx.strokeRect(boxX, bottomY + 4, boxWidth, 18);
                ctx.fillStyle = '#c0392b';
                ctx.fillText(dateText, boxX + boxWidth/2, bottomY + 13);
                ctx.restore();
            }
        }
    };

function drawLineCharts(strKey) {
        if(!window.megaData) return;
        let data = window.megaData[strKey];
        if (!data) return;

        let fields = [
            { key: 'gdb_first2', id: `line_${strKey}_gdb_first2`, name: 'ĐẦU ĐẶC BIỆT' },
            { key: 'gdb_last2', id: `line_${strKey}_gdb_last2`, name: 'CUỐI ĐẶC BIỆT' },
            { key: 'g1_first2', id: `line_${strKey}_g1_first2`, name: 'ĐẦU GIẢI NHẤT' },
            { key: 'g1_last2', id: `line_${strKey}_g1_last2`, name: 'CUỐI GIẢI NHẤT' }
        ];

        let colors = ['#e74c3c', '#0d6efd', '#28a745', '#fd7e14', '#f1c40f'];

        fields.forEach(field => {
            let mainCanvas = document.getElementById(field.id);
            let yCanvas = document.getElementById(`yAxis_${strKey}_${field.key}`);
            if (!mainCanvas || !yCanvas) return;

            let ctx = mainCanvas.getContext('2d');
            let yCtx = yCanvas.getContext('2d');

            if (window.myLineCharts && window.myLineCharts[field.id]) window.myLineCharts[field.id].destroy();
            if (window.myYAxisCharts && window.myYAxisCharts[field.id]) window.myYAxisCharts[field.id].destroy();
            
            window.myLineCharts = window.myLineCharts || {};
            window.myYAxisCharts = window.myYAxisCharts || {};

            let fieldData = data.fields[field.key];
            let cols = data.cols;

            document.getElementById(`chart_title_${strKey}_${field.key}`).innerText = field.name;
            let legendHtml = cols.map((col, idx) => {
                return `<div style="display:flex; align-items:center; margin: 0 10px; font-size: 0.85rem; font-weight: 700; color: #555;">
                           <span style="width: 14px; height: 14px; background: ${colors[idx % colors.length]}; border-radius: 50%; display:inline-block; margin-right: 5px;"></span>
                           ${col}
                        </div>`;
            }).join('');
            document.getElementById(`chart_legend_${strKey}_${field.key}`).innerHTML = legendHtml;

            let allPoints = [];
            cols.forEach((col) => {
                let cycles = fieldData.ganCycles[col] || [];
                cycles.forEach(cy => {
                    if (cy.length >= 15 && cy.to) {
                        let p = cy.to.split('/'); 
                        if (p.length === 3) {
                            let y = p[2].length === 2 ? '20' + p[2] : p[2];
                            let dateIso = `${y}-${p[1].padStart(2,'0')}-${p[0].padStart(2,'0')}`;
                            // THÊM fromDate và toDate ĐỂ TRUYỀN LÊN SERVER KHI CLICK
                            allPoints.push({ 
                                col: col, dateStr: cy.to, dateIso: dateIso, timestamp: new Date(dateIso).getTime(), val: cy.length,
                                fromDate: cy.from, toDate: cy.to 
                            });
                        }
                    }
                });
            });

            allPoints.sort((a, b) => a.timestamp - b.timestamp);
            let uniqueDatesIso = [...new Set(allPoints.map(p => p.dateIso))];

            let maxGanVal = allPoints.length > 0 ? Math.max(...allPoints.map(p => p.val)) : 15;
            let syncYMax = Math.ceil((maxGanVal + 2) / 5) * 5; 

            let labels = uniqueDatesIso.map(iso => {
                let d = new Date(iso);
                return `${d.getDate().toString().padStart(2,'0')}/${(d.getMonth()+1).toString().padStart(2,'0')}/${d.getFullYear()}`;
            });

            let innerDiv = document.getElementById(`inner_${strKey}_${field.key}`);
            if (innerDiv) {
                let calcWidth = labels.length * 35; 
                innerDiv.style.width = `max(100%, ${calcWidth}px)`;
            }

            // === TÍNH TOÁN "LẦN THỨ MẤY" CHO TỪNG MỨC GAN THEO THỜI GIAN ===
            let runningCountMap = {};
            allPoints.forEach(pt => {
                if (!runningCountMap[pt.col]) runningCountMap[pt.col] = {};
                if (!runningCountMap[pt.col][pt.val]) runningCountMap[pt.col][pt.val] = 0;
                
                // Tăng biến đếm mỗi khi gặp lại mức gan này
                runningCountMap[pt.col][pt.val]++;
                pt.occurrenceAtThisValue = runningCountMap[pt.col][pt.val];
            });
            
            // Ghi chép luôn tổng số lần để lỡ bác muốn hiện "Lần X / Tổng Y"
            allPoints.forEach(pt => {
                pt.totalAtThisValue = runningCountMap[pt.col][pt.val];
            });

            let datasets = cols.map((col, idx) => {
                let lineData = [];
                let occData = []; // Mảng lưu thông tin "Lần thứ mấy" để truyền cho Tooltip

                uniqueDatesIso.forEach(uIso => {
                    let pt = allPoints.find(p => p.col === col && p.dateIso === uIso);
                    if (pt) {
                        lineData.push(pt.val);
                        occData.push({ occ: pt.occurrenceAtThisValue, total: pt.totalAtThisValue });
                    } else {
                        lineData.push(null);
                        occData.push(null);
                    }
                });

                return {
                    label: col,
                    data: lineData,
                    occurrenceData: occData, // Chèn dữ liệu phụ vào đây
                    borderColor: colors[idx % colors.length],
                    backgroundColor: colors[idx % colors.length],
                    borderWidth: 2,
                    pointRadius: 4, 
                    pointHoverRadius: 7, 
                    pointHoverBorderWidth: 2,
                    pointHoverBackgroundColor: '#fff',
                    spanGaps: true,
                    tension: 0.1 
                };
            });

            let yDatasets = cols.map((col, idx) => ({
                data: datasets[idx].data,
                borderColor: 'transparent', backgroundColor: 'transparent',
                pointRadius: 0, pointHoverRadius: 0, spanGaps: true
            }));

            let paddingY = { top: 20, bottom: 20, left: 0, right: 0 }; 
            let paddingMain = { top: 20, bottom: 20, left: 15, right: 25 };

            window.myYAxisCharts[field.id] = new Chart(yCtx, {
                type: 'line', data: { labels: labels, datasets: yDatasets },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    layout: { padding: paddingY },
                    plugins: { legend: { display: false }, title: { display: false }, tooltip: { enabled: false } },
                    scales: {
                        x: { display: true, offset: true, ticks: { display: false }, grid: { display: false, drawBorder: false }, border: { display: false } },
                        y: { position: 'right', min: 10, max: syncYMax, display: true, ticks: { font: { weight: 'bold', size: 11 }, color: '#555', stepSize: 5, padding: 2 }, grid: { display: false, drawBorder: false }, border: { display: false } }
                    }
                }
            });

            window.myLineCharts[field.id] = new Chart(ctx, {
                type: 'line', data: { labels: labels, datasets: datasets },
                options: {
                    // BẮT SỰ KIỆN CLICK ĐỂ GỌI API TẠO BẢNG
                    onClick: (event, elements, chart) => {
                        if (elements.length > 0) {
                            const datasetIndex = elements[0].datasetIndex;
                            const dataIndex = elements[0].index;
                            const colName = chart.data.datasets[datasetIndex].label;
                            const uIso = uniqueDatesIso[dataIndex];
                            
                            const pt = allPoints.find(p => p.col === colName && p.dateIso === uIso);
                            if (pt) {
                                const currentMode = document.querySelector('input[name="mode_chia_bo"]:checked').value;
                                loadRangeStats(pt.fromDate, pt.toDate, colName, pt.val, strKey, field.key, currentMode, field.name);
                            }
                        }
                    },
                    clip: false, 
                    responsive: true, maintainAspectRatio: false,
                    layout: { padding: paddingMain },
                    interaction: { mode: 'index', intersect: false },
                    plugins: { 
                        legend: { display: false }, title: { display: false },
                        tooltip: { 
                            enabled: true, 
                            callbacks: { 
                                title: () => null,
                                label: function(context) {
                                    let ds = context.dataset;
                                    let val = context.parsed.y;
                                    let colLabel = ds.label;
                                    
                                    if (val === null || val === undefined) return null;
                                    
                                    // Lấy thông tin đếm thứ tự đã tính ở Bước 1
                                    let occInfo = ds.occurrenceData ? ds.occurrenceData[context.dataIndex] : null;
                                    
                                    if (occInfo) {
                                        return `${colLabel}: ${val} ngày — Lần thứ ${occInfo.occ} đạt mức này (trên tổng ${occInfo.total} lần)`;
                                    }
                                    
                                    return `${colLabel}: ${val} ngày`;
                                }
                            } 
                        }
                    },
                    scales: {
                        x: { offset: true, grid: { drawOnChartArea: true, color: '#e9ecef', tickLength: 0 }, ticks: { display: false }, border: { display: true, color: '#ccc' } },
                        y: { position: 'left', min: 10, max: syncYMax, ticks: { display: false, stepSize: 5 }, grid: { drawBorder: false, color: '#e9ecef', tickLength: 0 }, border: { display: false } }
                    }
                },
                plugins: [crosshairPlugin] 
            });

            let scrollDiv = document.getElementById(`scroll_${strKey}_${field.key}`);
            if (scrollDiv) {
                setTimeout(() => { scrollDiv.scrollLeft = scrollDiv.scrollWidth; }, 100);
            }
        });
    }

    function drawGanChart(canvasId, ganDataMap, barColor, globalMax) {

        let canvas = document.getElementById(canvasId);

        if (!canvas) return;

        let ctx = canvas.getContext('2d');

        

        let arr = [];

        for (let k in ganDataMap) { arr.push({ label: k, val: ganDataMap[k] }); }



        let dpr = window.devicePixelRatio || 1;

        let rect = canvas.getBoundingClientRect();

        canvas.width = rect.width * dpr;

        canvas.height = rect.height * dpr;

        ctx.scale(dpr, dpr);

        let W = rect.width, H = rect.height;

        ctx.clearRect(0, 0, W, H);



        let barCount = arr.length;

        let padL = 20, padR = 20, padB = 25, padT = 25;

        let drawAreaW = W - padL - padR;

        let drawAreaH = H - padB - padT;

        let barW = (drawAreaW / barCount) * 0.7;

        let spacing = (drawAreaW / barCount) * 0.3;



        ctx.beginPath(); ctx.moveTo(padL, H - padB); ctx.lineTo(W - padR, H - padB);

        ctx.strokeStyle = '#dee2e6'; ctx.lineWidth = 2; ctx.stroke();



        for (let i = 0; i < barCount; i++) {

            let item = arr[i];

            let x = padL + i * (barW + spacing) + spacing/2;

            

            let bH = (item.val / globalMax) * drawAreaH;

            if(bH < 3 && item.val > 0) bH = 3; 

            let y = H - padB - bH;



            ctx.fillStyle = barColor; 

            ctx.fillRect(x, y, barW, bH);



            ctx.fillStyle = '#0d47a1';

            ctx.font = 'bold 12px sans-serif';

            ctx.textAlign = 'center';

            ctx.fillText(item.val + " ng", x + barW/2, y - 6);



            ctx.fillStyle = '#333';

            ctx.font = 'bold 12px Consolas';

            ctx.fillText(item.label, x + barW/2, H - padB + 16);

        }

    }



    function updateSummaryVisibility(strKey) {

        document.querySelectorAll('.max-gan-summary-container').forEach(el => el.style.display = 'none');

        let target = document.getElementById('summary-' + strKey);

        if(target) target.style.display = 'block';

    }



    document.addEventListener("DOMContentLoaded", function() {

        let firstBtn = document.querySelector('.nav-tabs-wrapper .tab-btn');

        if(firstBtn) firstBtn.click(); 

    });



    function filterGanCycles(btn) {

        let panel = btn.closest('.cycle-panel');

        let minVal = parseInt(panel.querySelector('.cy-min').value) || 1;

        let fromVal = panel.querySelector('.cy-from').value; 

        let toVal = panel.querySelector('.cy-to').value;     



        let dFrom = fromVal ? new Date(fromVal) : null;

        let dTo = toVal ? new Date(toVal) : null;

        if(dFrom) dFrom.setHours(0,0,0,0);

        if(dTo) dTo.setHours(23,59,59,999);



        let items = panel.querySelectorAll('.cy-item');

        

        items.forEach(item => {

            let len = parseInt(item.getAttribute('data-len')) || 0;

            let startStr = item.getAttribute('data-start'); 

            let endStr = item.getAttribute('data-end');     

            

            let isShow = true;



            if (len < minVal) {

                isShow = false;

            }



            if (isShow && (dFrom || dTo) && startStr !== 'hiện tại') {

                let parseDate = (str) => {

                    if(!str || str.includes('Nay') || str.includes('-')) return null;

                    let p = str.split('/');

                    if(p.length === 3) {

                        let year = p[2].length === 2 ? '20' + p[2] : p[2];

                        return new Date(year, p[1]-1, p[0]);

                    }

                    return null;

                };

                

                let itemStart = parseDate(startStr);

                let itemEnd = parseDate(endStr);



                if (itemStart && itemEnd) {

                    if (dFrom && itemEnd < dFrom) isShow = false;

                    if (dTo && itemStart > dTo) isShow = false;

                }

            }



            item.style.display = isShow ? 'flex' : 'none';

        });

    }



    function switchMode(mode) {

        let dateInput = document.querySelector('input[name="date"]');

        let dateVal = dateInput ? dateInput.value : '';

        

        let url = new URL(window.location.href);

        url.searchParams.set('mode', mode);

        if (dateVal) {

            url.searchParams.set('date', dateVal);

        }

        

        window.location.href = url.toString();

    }
    // 1. HÀM PHỤ: DỊCH SỐ THỰC TẾ RA TÊN BỘ SỐ THEO CHẾ ĐỘ
    function getPatternDetails(valStr, strKey, mode) {
        if (!valStr || valStr === '--') return { short: '-', full: '-' };
        let n = parseInt(valStr, 10);
        let h = parseInt(valStr[0], 10);
        let t = parseInt(valStr[1], 10);
        let short = '', full = '';

        if (mode == 20) { // Chế độ 5 bộ
            if (strKey === 'dai_so') {
                if (n <= 19) { short = '00-19'; full = 'Dải 00-19'; }
                else if (n <= 39) { short = '20-39'; full = 'Dải 20-39'; }
                else if (n <= 59) { short = '40-59'; full = 'Dải 40-59'; }
                else if (n <= 79) { short = '60-79'; full = 'Dải 60-79'; }
                else { short = '80-99'; full = 'Dải 80-99'; }
            }
            else if (strKey === 'bong_dau') { short = (h % 5) + ' - ' + ((h % 5)+5); full = 'Bóng Đầu'; }
            else if (strKey === 'bong_duoi') { short = (t % 5) + ' - ' + ((t % 5)+5); full = 'Bóng Đuôi'; }
            else if (strKey === 'bong_tong') { short = (((h + t) % 10) % 5) + ' - ' + ((((h + t) % 10) % 5)+5); full = 'Bóng Tổng'; }
            else if (strKey === 'bong_hieu') {
                let hieu = Math.abs(h - t);
                let c = Math.min(hieu, 10 - hieu);
                if (c === 0 || c === 5) { short = '0 - 5'; full = 'Hiệu 0-5'; }
                else if (c === 1) { short = '1 - 9'; full = 'Hiệu 1-9'; }
                else if (c === 2) { short = '2 - 8'; full = 'Hiệu 2-8'; }
                else if (c === 3) { short = '3 - 7'; full = 'Hiệu 3-7'; }
                else if (c === 4) { short = '4 - 6'; full = 'Hiệu 4-6'; }
            }
        } else { // Chế độ 4 bộ
            let h_tn = h >= 5 ? 'T' : 'N'; let t_tn = t >= 5 ? 'T' : 'N';
            let h_cl = h % 2 === 0 ? 'C' : 'L'; let t_cl = t % 2 === 0 ? 'C' : 'L';

            if (strKey === 'tn') {
                short = h_tn + t_tn;
                let dict = {'TT':'To To','NN':'Nhỏ Nhỏ','TN':'To Nhỏ','NT':'Nhỏ To'};
                full = dict[short];
            } else if (strKey === 'cl') {
                short = h_cl + t_cl;
                let dict = {'CC':'Chẵn Chẵn','LL':'Lẻ Lẻ','LC':'Lẻ Chẵn','CL':'Chẵn Lẻ'};
                full = dict[short];
            } else if (strKey === 'tn_cl') {
                short = h_tn + t_cl;
                let dict = {'TC':'To Chẵn','TL':'To Lẻ','NC':'Nhỏ Chẵn','NL':'Nhỏ Lẻ'};
                full = dict[short];
            } else if (strKey === 'cl_tn') {
                short = h_cl + t_tn;
                let dict = {'CT':'Chẵn To','LT':'Lẻ To','CN':'Chẵn Nhỏ','LN':'Lẻ Nhỏ'};
                full = dict[short];
            } else if (strKey === 'mod4') {
                let m = n % 4; 
                short = 'Dư ' + m; 
                full = 'Chia 4 Dư ' + m;
            } else if (strKey === 'range') {
                if (n <= 24) { short = '00-24'; full = 'Dải 00-24'; }
                else if (n <= 49) { short = '25-49'; full = 'Dải 25-49'; }
                else if (n <= 74) { short = '50-74'; full = 'Dải 50-74'; }
                else { short = '75-99'; full = 'Dải 75-99'; }
            }
        }
        return { short: short || '-', full: full || '-' };
    }

    // 2. HÀM HIỂN THỊ KẾT QUẢ TỪNG NGÀY (CÓ STYLE ĐẸP + THỘC TÍNH BỘ SỐ)
    function loadRangeStats(fromDate, toDate, colName, ganLen, strKey, fieldKey, mode, fieldName) {
        let container = document.getElementById('clicked-stats-container');
        if(!container) {
            container = document.createElement('div');
            container.id = 'clicked-stats-container';
            container.style.cssText = "position: relative; margin-top: 25px; padding: 25px 20px 20px 20px; border: 2px solid #0d6efd; border-radius: 8px; background: #fffafa; box-shadow: 0 4px 10px rgba(0,0,0,0.05);";
        }
        
        let wrapper = document.querySelector(`#wrap_${strKey}_${fieldKey}`).parentElement.parentElement;
        wrapper.appendChild(container);
        
        container.style.display = 'block';
        container.innerHTML = `<h5 class="text-center fw-bold" style="color:#0d6efd;">Đang tải kết quả chu kỳ ${colName}...</h5>`;
        container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        fetch(`{{ url('api/analysis/range-stats') }}?from=${fromDate}&to=${toDate}&field=${fieldKey}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                container.innerHTML = `<h5 class="text-center text-danger">Lỗi Server: ${data.error}</h5>`;
                return;
            }

            let boxes = '';
            if (data.results.length === 0) {
                boxes = `<div class="text-muted w-100 text-center">Không có dữ liệu kết quả nào trong khoảng thời gian này.</div>`;
            } else {
                data.results.forEach((item, index) => {
                    // Kiểm tra xem có phải ô đầu tiên hoặc ô cuối cùng không
                    let isFirst = index === 0;
                    let isLast = index === data.results.length - 1;
                    let isHighlight = isFirst || isLast; 
                    
                    // Dùng hàm phụ để dịch mã số ra thuộc tính
                    let pt = getPatternDetails(item.value, strKey, mode);
                    
                    // Nếu là ô đầu hoặc ô cuối thì bôi màu đỏ, còn lại màu xanh
                    let headerBg = isHighlight ? '#c0392b' : '#1a569d';
                    let borderColor = isHighlight ? '#c0392b' : '#1a569d';
                    let valColor = '#c0392b'; 

                    boxes += `
                        <div style="border: 2px solid ${borderColor}; border-radius: 8px; overflow: hidden; min-width: 90px; text-align: center; box-shadow: 0 3px 6px rgba(0,0,0,0.05); background: #fff;">
                            <div style="background: ${headerBg}; color: #fff; font-weight: bold; font-size: 1rem; padding: 6px 0;">
                                ${item.date.substring(0, 5)}
                            </div>
                            <div style="padding: 12px 5px;">
                                <div style="font-size: 2rem; font-weight: 900; color: ${valColor}; font-family: Consolas, monospace; line-height: 1;">
                                    ${item.value}
                                </div>
                                <div style="font-size: 1.15rem; font-weight: 900; color: #222; margin-top: 12px; line-height: 1;">
                                    ${pt.short}
                                </div>
                                <div style="font-size: 0.95rem; color: #333; margin-top: 6px; line-height: 1;">
                                    ${pt.full}
                                </div>
                            </div>
                        </div>
                    `;
                });
            }

            // NÚT ĐÓNG BẢNG (DẤU X) GÓC TRÊN PHẢI
            const closeButtonHtml = `
                <a href="javascript:void(0)" 
                   onclick="this.closest('#clicked-stats-container').style.display='none';" 
                   style="position: absolute; top: 10px; right: 15px; color: #c0392b; font-size: 2rem; font-weight: bold; text-decoration: none; line-height: 1; transition: 0.2s;" 
                   onmouseover="this.style.color='#a93226'" 
                   onmouseout="this.style.color='#c0392b'">
                   &times;
                </a>
            `;

            let html = `
                ${closeButtonHtml}
                <h5 class="text-center fw-bold mb-4" style="color: #1a569d; text-transform: uppercase;">
                    LỊCH SỬ KẾT QUẢ THỰC TẾ TRONG CHU KỲ GAN - ${colName}
                    <br>
                    <span style="font-size:1rem; color:#c0392b; text-transform: none;"><b>${fieldName}</b> | Từ ${fromDate} đến ${toDate} (${ganLen} ngày)</span>
                </h5>
                <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: center;">
                    ${boxes}
                </div>
            `;
            container.innerHTML = html;
        }).catch(err => {
             container.innerHTML = `<h5 class="text-center text-danger">Lỗi dữ liệu! Vui lòng F5 thử lại.</h5>`;
             console.error("Lỗi AJAX:", err);
        });
    }
</script>

@endsection