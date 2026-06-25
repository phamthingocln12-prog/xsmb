<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hệ thống XSMB Siêu Tốc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        .table-xsmb {
            background: #fff;
            border: 2px solid #dc3545;
            text-align: center;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .table-xsmb th {
            background: #f8d7da;
            color: #dc3545;
            vertical-align: middle;
            width: 15%;
        }

        .table-xsmb td {
            vertical-align: middle;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        .gdb-text {
            font-size: 2rem;
            color: red;
        }

        /* CẬP NHẬT DỮ LIỆU */
        .floating-widget {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 9999;
            font-family: 'Segoe UI', sans-serif;
        }

        .floating-btn {
            width: 50px;
            height: 50px;
            background-color: #0d6efd;
            /* Màu xanh nước biển */
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s, background-color 0.2s;
        }

        .floating-btn:hover {
            background-color: #0b5ed7;
            transform: scale(1.05);
        }

        .floating-menu {
            position: absolute;
            bottom: 60px;
            /* Nhô lên trên nút bấm */
            left: 0;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: none;
            /* Ẩn mặc định */
            flex-direction: column;
            min-width: 180px;
            overflow: hidden;
            transform-origin: bottom left;
        }

        .floating-menu.show {
            display: flex;
            animation: popUp 0.2s ease-out;
        }

        .floating-item {
            padding: 12px 15px;
            font-size: 0.95rem;
            font-weight: 700;
            color: #333;
            cursor: pointer;
            border-bottom: 1px solid #f8f9fa;
            transition: background 0.2s, color 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .floating-item:last-child {
            border-bottom: none;
        }

        .floating-item:hover {
            background: #f1f3f5;
            color: #c0392b;
        }

        @keyframes popUp {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('home') }}">Xổ Số Miền Bắc</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Trang Chủ</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('thong-ke') }}">Thống Kê</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('lo-gan') }}">Lô Top</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('dau-duoi') }}">Đầu - Đuôi</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('ky-quay') }}">Kỳ Quay</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('phan-tich') }}">Phân Tích</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        @yield('content')
    </div>

    {{-- THANH TRẠNG THÁI CÀO TỰ ĐỘNG --}}
    <div id="autoCrawlBar"
        style="display:none; position:fixed; top:0; left:0; right:0; z-index:10000; background:linear-gradient(135deg,#e74c3c,#c0392b); color:#fff; padding:8px 20px; font-size:0.9rem; font-weight:600; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.3); font-family:'Segoe UI',sans-serif;">
        <span id="autoCrawlText">🔴 Đang cào dữ liệu tự động...</span>
        <div style="margin-top:4px; background:rgba(255,255,255,0.3); border-radius:10px; height:6px; overflow:hidden;">
            <div id="autoCrawlProgress"
                style="height:100%; background:#fff; border-radius:10px; transition:width 0.5s ease; width:0%;"></div>
        </div>
    </div>

    {{-- NÚT CẬP NHẬT DỮ LIỆU CÓ CHỌN NGÀY (floating) --}}
    <div class="floating-widget">
        <div class="floating-btn" onclick="document.getElementById('floatingMenu').classList.toggle('show')" title="Cập nhật dữ liệu">
            <span style="font-size: 32px; line-height: 1;">⚙️</span>
        </div>
        <div class="floating-menu p-3" id="floatingMenu" style="min-width: 250px;">
            <h6 class="text-danger fw-bold mb-3 border-bottom pb-2">Cập Nhật Dữ Liệu</h6>
            
            <div class="mb-2">
                <label class="form-label text-muted" style="font-size: 0.85rem; font-weight: bold;">Từ ngày:</label>
                <input type="date" id="crawlStartDate" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
            </div>
            
            <div class="mb-3">
                <label class="form-label text-muted" style="font-size: 0.85rem; font-weight: bold;">Đến ngày:</label>
                <input type="date" id="crawlEndDate" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
            </div>
            
            <button class="btn btn-danger btn-sm w-100 fw-bold" id="btnSubmitCrawl">📥 Cào Dữ Liệu</button>
            <div id="crawlStatusMsg" class="mt-2 text-center" style="font-size: 0.8rem; display: none;"></div>
        </div>
    </div>

    @stack('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // ===== Tính năng 1: Nhấn ra ngoài thì cúp menu =====
            document.addEventListener('click', function (event) {
                let widget = document.querySelector('.floating-widget');
                if (widget && !widget.contains(event.target)) {
                    document.getElementById('floatingMenu').classList.remove('show');
                }
            });

            // ===================================================================
            //  TÍNH NĂNG CHÍNH: TỰ ĐỘNG CÀO + TƯỜNG THUẬT TRỰC TIẾP
            //  - Mở web + Laragon là tự cào, KHÔNG cần bấm nút
            //  - Cào từng giải, animate + đọc từng giải mới
            //  - KHÔNG reload trang — cập nhật trực tiếp trên UI
            //  - Reload trang vẫn tiếp tục cào
            // ===================================================================
            const CRAWL_ONCE_URL = '{{ route("crawl.once") }}';
            const POLL_INTERVAL = 15000;  // 15 giây
            const START_HOUR = 18, START_MIN = 08;  // 19:49 (TEST)
            const END_HOUR = 18, END_MIN = 40;       // 20:00
            const STORAGE_KEY = 'xsmb_auto_crawl';

            let isCrawling = false;
            let crawlTimer = null;
            let windowCheckTimer = null;
            let knownResults = {};  // "G7_0" => "37188" — theo dõi kết quả đã biết
            let btnUpdate = document.getElementById('btnUpdateData');
            let statusBar = document.getElementById('autoCrawlBar');
            let statusText = document.getElementById('autoCrawlText');
            let progressBar = document.getElementById('autoCrawlProgress');

            // --- CSRF ---
            function getCSRFToken() {
                let meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.getAttribute('content') : '';
            }
            async function refreshCSRFToken() {
                try {
                    let resp = await fetch(window.location.href, { method: 'GET', headers: { 'Accept': 'text/html' }, credentials: 'same-origin' });
                    let html = await resp.text();
                    let match = html.match(/meta name="csrf-token" content="([^"]+)"/);
                    if (match && match[1]) {
                        let meta = document.querySelector('meta[name="csrf-token"]');
                        if (meta) meta.setAttribute('content', match[1]);
                    }
                } catch (e) { }
            }

            function getTodayStr() {
                let d = new Date();
                return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            }

            function isInCrawlWindow() {
                let now = new Date();
                let minutes = now.getHours() * 60 + now.getMinutes();
                return minutes >= (START_HOUR * 60 + START_MIN) && minutes <= (END_HOUR * 60 + END_MIN);
            }

            // --- localStorage ---
            function saveCrawlState(crawling, completed) {
                localStorage.setItem(STORAGE_KEY, JSON.stringify({
                    date: getTodayStr(), crawling: !!crawling, completed: !!completed, timestamp: Date.now()
                }));
            }
            function loadCrawlState() {
                try {
                    let raw = localStorage.getItem(STORAGE_KEY);
                    if (!raw) return null;
                    let state = JSON.parse(raw);
                    if (state.date !== getTodayStr()) { localStorage.removeItem(STORAGE_KEY); return null; }
                    return state;
                } catch (e) { localStorage.removeItem(STORAGE_KEY); return null; }
            }

            // --- Status bar ---
            function showStatus(text, color, progress) {
                if (statusBar) {
                    statusBar.style.display = 'block';
                    statusBar.style.background = color || 'linear-gradient(135deg,#e74c3c,#c0392b)';
                    document.body.style.paddingTop = '50px';
                }
                if (statusText) statusText.textContent = text;
                if (progressBar && progress !== undefined) progressBar.style.width = Math.min(100, progress) + '%';
            }
            function hideStatus() {
                if (statusBar) { statusBar.style.display = 'none'; document.body.style.paddingTop = '0'; }
            }

            // --- Xóa icon ⚠ trên lịch sau khi cào xong ---
            function removeCalendarWarnings(fromDate, toDate) {
                document.querySelectorAll('.cal-warn').forEach(icon => {
                    // Lấy ngày từ href của thẻ <a> cha
                    let link = icon.closest('a');
                    if (!link) return;
                    let href = link.getAttribute('href') || '';
                    let match = href.match(/date=([\d-]+)/);
                    if (match) {
                        let cellDate = match[1];
                        if (cellDate >= fromDate && cellDate <= toDate) {
                            icon.remove();
                        }
                    }
                });
            }

            // ===================================================================
            //  HELPER: Tìm ô giải thưởng trên bảng kết quả
            // ===================================================================
            function findSlotEl(tier, index) {
                return document.querySelector('.prize-slot[data-tier="' + tier + '"][data-index="' + index + '"]');
            }

            // ===================================================================
            //  XỬ LÝ KẾT QUẢ MỚI: Animate + Tường thuật từng giải
            // ===================================================================
            let animationQueue = [];
            let isAnimating = false;

            function queueNewResult(tier, index, value) {
                animationQueue.push({ tier, index, value });
                if (!isAnimating) processAnimationQueue();
            }

            function processAnimationQueue() {
                if (animationQueue.length === 0) {
                    isAnimating = false;
                    return;
                }
                isAnimating = true;
                let { tier, index, value } = animationQueue.shift();
                let slot = findSlotEl(tier, index);

                if (slot) {
                    // Rolling animation trước khi hiện số
                    let digits = parseInt(slot.dataset.digits) || 2;
                    slot.classList.add('rolling');
                    slot.classList.remove('revealed');
                    let rollCount = 0;
                    let rollInterval = setInterval(() => {
                        let r = '';
                        for (let i = 0; i < digits; i++) r += Math.floor(Math.random() * 10);
                        slot.textContent = r;
                        rollCount++;
                        if (rollCount >= 12) {  // ~600ms rolling
                            clearInterval(rollInterval);
                            // Hiện số thật
                            slot.classList.remove('rolling');
                            slot.textContent = value;
                            void slot.offsetWidth;
                            slot.classList.add('revealed', 'prize-new');
                            if (tier === 'GDB') slot.style.color = 'red';
                            setTimeout(() => slot.classList.remove('prize-new'), 2000);



                            // Xử lý giải tiếp theo sau 800ms
                            setTimeout(() => processAnimationQueue(), 800);
                        }
                    }, 50);
                } else {
                    setTimeout(() => processAnimationQueue(), 300);
                }  
            }

            // ===================================================================
            //  HÀM CÀO CHÍNH: Poll server, detect kết quả mới, animate
            // ===================================================================
            function startAutoCrawl(source) {
                if (isCrawling) return;
                isCrawling = true;
                saveCrawlState(true, false);

                // Scan kết quả đang hiện trên bảng → đưa vào knownResults
                document.querySelectorAll('.prize-slot[data-tier]').forEach(slot => {
                    let tier = slot.dataset.tier;
                    let idx = parseInt(slot.dataset.index);
                    let text = slot.textContent.trim();
                    if (text && text !== '...' && text !== '-----' && text !== '------' && /^\d+$/.test(text)) {
                        knownResults[tier + '_' + idx] = text;
                    }
                });

                let knownCount = Object.keys(knownResults).length;
                let label = source === 'auto' ? '🔴 Tự động cào dữ liệu...' : '⏳ Đang cào dữ liệu...';
                showStatus(label, 'linear-gradient(135deg,#e74c3c,#c0392b)', Math.round((knownCount / 27) * 100));

                // Hiện LIVE indicator
                let liveEl = document.getElementById('liveIndicator');
                if (liveEl) liveEl.classList.remove('d-none');
                let cdWrapper = document.getElementById('countdown-wrapper');
                if (cdWrapper) cdWrapper.innerHTML = '<h5 class="text-danger fw-bold m-0"><span class="spinner-grow spinner-grow-sm text-danger" role="status"></span> ĐANG CÀO DỮ LIỆU TRỰC TIẾP...</h5>';

                // Rolling tất cả ô chưa có kết quả
                document.querySelectorAll('.prize-slot[data-tier]').forEach(slot => {
                    let key = slot.dataset.tier + '_' + parseInt(slot.dataset.index);
                    if (!knownResults[key]) {
                        let digits = parseInt(slot.dataset.digits) || 2;
                        slot.classList.add('rolling');
                        if (!slot._rollInterval) {
                            slot._rollInterval = setInterval(() => {
                                let r = '';
                                for (let i = 0; i < digits; i++) r += Math.floor(Math.random() * 10);
                                slot.textContent = r;
                            }, 50);
                        }
                    }
                });

                if (btnUpdate) {
                    btnUpdate.innerHTML = '⏳ Đang cào... (0/27)';
                    btnUpdate.style.pointerEvents = 'none';
                    btnUpdate.style.color = '#e67e22';
                }


                doCrawlOnce();
            }

            async function doCrawlOnce() {
                if (!isCrawling) return;

                try {
                    let resp = await fetch(CRAWL_ONCE_URL, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': getCSRFToken(), 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    });

                    if (resp.status === 419) {
                        await refreshCSRFToken();
                        crawlTimer = setTimeout(doCrawlOnce, 3000);
                        return;
                    }

                    let data = await resp.json();
                    let count = data.count || 0;
                    let isComplete = data.is_complete || false;
                    let results = data.results || [];
                    let pct = Math.round((count / 27) * 100);
                    let newCount = 0;

                    // So sánh kết quả mới vs đã biết → queueNewResult cho từng giải mới
                    results.forEach(r => {
                        let key = r.tier + '_' + r.index;
                        if (!knownResults[key]) {
                            knownResults[key] = r.value;
                            newCount++;

                            // Dừng rolling cho ô này
                            let slot = findSlotEl(r.tier, r.index);
                            if (slot && slot._rollInterval) {
                                clearInterval(slot._rollInterval);
                                slot._rollInterval = null;
                            }

                            // Queue animation
                            queueNewResult(r.tier, r.index, r.value);
                        }
                    });

                    let totalKnown = Object.keys(knownResults).length;
                    let now = new Date();
                    let timeStr = now.getHours() + ':' + String(now.getMinutes()).padStart(2, '0') + ':' + String(now.getSeconds()).padStart(2, '0');

                    if (isComplete || totalKnown >= 27) {
                        // ✅ ĐỦ 27 GIẢI
                        showStatus(`✅ Đã đủ 27 giải lúc ${timeStr}!`, 'linear-gradient(135deg,#27ae60,#2ecc71)', 100);
                        if (btnUpdate) { btnUpdate.innerHTML = '✅ Đã đủ 27 giải!'; btnUpdate.style.color = '#27ae60'; }

                        // Dừng tất cả rolling
                        document.querySelectorAll('.prize-slot[data-tier]').forEach(slot => {
                            if (slot._rollInterval) { clearInterval(slot._rollInterval); slot._rollInterval = null; }
                            slot.classList.remove('rolling');
                        });

                        // Hoàn tất
                        setTimeout(() => {
                            let liveEl = document.getElementById('liveIndicator');
                            if (liveEl) liveEl.classList.add('d-none');
                            let cdWrapper = document.getElementById('countdown-wrapper');
                            if (cdWrapper) cdWrapper.innerHTML = '<h5 class="text-success fw-bold m-0">✅ Đã cào xong!</h5>';
                        }, animationQueue.length * 1500 + 2000);

                        stopCrawl(true);

                        // Xóa icon ⚠ trên lịch cho ngày hôm nay
                        removeCalendarWarnings(getTodayStr(), getTodayStr());
                    } else {
                        // ⏳ CHƯA ĐỦ
                        let msg = `🔴 Đang cào: ${totalKnown}/27 giải (${pct}%)`;
                        if (newCount > 0) msg += ` — +${newCount} mới!`;
                        msg += ` — ${timeStr}`;
                        showStatus(msg, 'linear-gradient(135deg,#e74c3c,#c0392b)', pct);
                        if (btnUpdate) { btnUpdate.innerHTML = `⏳ ${totalKnown}/27`; btnUpdate.style.color = '#e67e22'; }

                        saveCrawlState(true, false);
                        crawlTimer = setTimeout(doCrawlOnce, POLL_INTERVAL);
                    }
                } catch (err) {
                    console.error('Crawl error:', err);
                    showStatus('⚠️ Lỗi kết nối. Thử lại sau 15s...', 'linear-gradient(135deg,#e67e22,#d35400)', undefined);
                    crawlTimer = setTimeout(doCrawlOnce, POLL_INTERVAL);
                }
            }

            function stopCrawl(completed) {
                isCrawling = false;
                if (completed) { saveCrawlState(false, true); }
                else { localStorage.removeItem(STORAGE_KEY); }
                if (crawlTimer) { clearTimeout(crawlTimer); crawlTimer = null; }
                if (btnUpdate) { btnUpdate.style.pointerEvents = 'auto'; }
            }

            // ===================================================================
            //  KIỂM TRA LIÊN TỤC: Mỗi 30s auto-detect khung giờ
            // ===================================================================
            function periodicWindowCheck() {
                let state = loadCrawlState();
                if (isInCrawlWindow() && !isCrawling && !(state && state.completed)) {
                    console.log('[AUTO-CRAWL] Vào khung giờ → Tự khởi động!');
                    startAutoCrawl('auto');
                }
            }
            windowCheckTimer = setInterval(periodicWindowCheck, 30000);

            // ===================================================================
            //  KHỞI ĐỘNG: Luôn hỏi server trước
            // ===================================================================
            function initAutoCrawl() {
                let savedState = loadCrawlState();

                if (isInCrawlWindow()) {
                    console.log('[AUTO-CRAWL] Trong khung giờ → Kiểm tra server...');
                    if (savedState && savedState.crawling) {
                        startAutoCrawl('auto');
                        return;
                    }
                    // Hỏi server
                    fetch(CRAWL_ONCE_URL, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': getCSRFToken(), 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    })
                        .then(r => r.status === 419 ? refreshCSRFToken().then(() => { setTimeout(() => startAutoCrawl('auto'), 2000); return null; }) : r.json())
                        .then(data => {
                            if (!data) return;
                            if (!data.is_complete) {
                                console.log(`[AUTO-CRAWL] Server: ${data.count || 0}/27 → Bắt đầu cào!`);
                                localStorage.removeItem(STORAGE_KEY);

                                // Nếu đã có kết quả → hiển thị ngay (không animate)
                                if (data.results && data.results.length > 0) {
                                    data.results.forEach(r => {
                                        let key = r.tier + '_' + r.index;
                                        knownResults[key] = r.value;
                                        let slot = findSlotEl(r.tier, r.index);
                                        if (slot) {
                                            slot.textContent = r.value;
                                            slot.classList.add('revealed');
                                            slot.classList.remove('rolling');
                                            if (r.tier === 'GDB') slot.style.color = 'red';
                                        }
                                    });
                                }

                                startAutoCrawl('auto');
                            } else {
                                console.log('[AUTO-CRAWL] Đã đủ 27 giải ✅');
                                saveCrawlState(false, true);
                            }
                        })
                        .catch(err => {
                            console.error('[AUTO-CRAWL] Lỗi:', err);
                            setTimeout(() => startAutoCrawl('auto'), 5000);
                        });
                }
                else if (savedState && savedState.crawling) { startAutoCrawl('auto'); }
                else if (savedState && savedState.completed) { console.log('[AUTO-CRAWL] Đã cào đủ.'); }
            }

            initAutoCrawl();

            // Nút bấm thủ công thông minh
            let btnSubmitCrawl = document.getElementById('btnSubmitCrawl');
            if (btnSubmitCrawl) {
                btnSubmitCrawl.addEventListener('click', async function () {
                    let startDate = document.getElementById('crawlStartDate').value;
                    let endDate = document.getElementById('crawlEndDate').value;
                    let statusMsg = document.getElementById('crawlStatusMsg');
                    let todayStr = getTodayStr();
                    let isLiveTime = isInCrawlWindow(); // Kiểm tra có đang trong giờ quay không

                    if (!startDate || !endDate) return alert('Chọn ngày!');
                    
                    let originalBtnHtml = btnSubmitCrawl.innerHTML;
                    btnSubmitCrawl.innerHTML = '⏳ Đang xử lý...';
                    btnSubmitCrawl.disabled = true;
                    statusMsg.style.display = 'block';

                    try {
                        let resp = await fetch('{{ route("manual.crawl") }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': getCSRFToken(), 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ start_date: startDate, end_date: endDate }),
                            credentials: 'same-origin'
                        });
                        
                        let data = await resp.json();
                        
                        if (data.success) {
                            btnSubmitCrawl.innerHTML = '✅ Xong!';
                            
                            // TRƯỜNG HỢP: Cập nhật cho ngày hôm nay
                            if (endDate === todayStr) {
                                if (data.results && data.results.length > 0) {
                                    data.results.forEach(r => {
                                        let key = r.tier + '_' + r.index;
                                        
                                        // Nếu số này mới (trên UI chưa có)
                                        if (!knownResults[key]) {
                                            knownResults[key] = r.value;
                                            let slot = findSlotEl(r.tier, r.index);
                                            
                                            if (slot) {
                                                if (isLiveTime) {
                                                    // 1. ĐANG TRONG GIỜ QUAY: Dừng xoay của ô này và hiện số (Animate)
                                                    if (slot._rollInterval) {
                                                        clearInterval(slot._rollInterval);
                                                        slot._rollInterval = null;
                                                    }
                                                    queueNewResult(r.tier, r.index, r.value);
                                                } else {
                                                    // 2. NGOÀI GIỜ QUAY: Hiện thẳng số, không xoay
                                                    slot.classList.remove('rolling');
                                                    slot.classList.add('revealed');
                                                    slot.textContent = r.value;
                                                    if (r.tier === 'GDB') slot.style.color = 'red';
                                                }
                                            }
                                        }
                                    });
                                }
                                statusMsg.innerHTML = '<span class="text-success fw-bold">Đã đồng bộ dữ liệu mới nhất!</span>';
                                
                                // Xóa icon ⚠ trên lịch cho các ngày đã cào xong
                                removeCalendarWarnings(startDate, endDate);

                                setTimeout(() => {
                                    btnSubmitCrawl.innerHTML = originalBtnHtml;
                                    btnSubmitCrawl.disabled = false;
                                }, 2000);

                            } else {
                                // Nếu cào ngày cũ trong quá khứ -> Reload để cập nhật toàn bộ thống kê trang
                                statusMsg.innerHTML = '<span class="text-success">Thành công! Đang tải lại...</span>';
                                setTimeout(() => location.reload(), 1000);
                            }
                        } else {
                            btnSubmitCrawl.innerHTML = '❌ Lỗi';
                            setTimeout(() => { btnSubmitCrawl.innerHTML = originalBtnHtml; btnSubmitCrawl.disabled = false; }, 2000);
                        }
                    } catch(e) {
                        btnSubmitCrawl.innerHTML = '❌ Lỗi kết nối';
                        setTimeout(() => { btnSubmitCrawl.innerHTML = originalBtnHtml; btnSubmitCrawl.disabled = false; }, 2000);
                    }
                });
            }
        });
    </script>
</body>

</html>