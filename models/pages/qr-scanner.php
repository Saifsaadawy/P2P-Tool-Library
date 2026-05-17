<?php
require_once '../config.php';
require_once '../includes/auth_check.php';
requireRole('librarian');
require_once '../includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<!-- jsQR library for QR decoding from camera -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsqr/1.4.0/jsQR.min.js"></script>

<style>
:root {
    --orange: #e8431a;
    --green:  #1a9e6b;
    --red:    #d04040;
    --yellow: #c9920a;
    --border: #e5e2de;
    --muted:  #7a7570;
    --card:   #f8f7f6;
    --text:   #1a1917;
}

body { background: #f3f2f0; color: var(--text); font-family: 'Syne', sans-serif; }

.qr-wrap {
    max-width: 600px;
    margin: 2.5rem auto;
    padding: 0 1.25rem 4rem;
}

.qr-hero {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 1.75rem 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.qr-hero h1 { font-size: 1.4rem; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 0.25rem; }
.qr-hero p  { color: var(--muted); font-size: 0.88rem; font-family: 'DM Mono', monospace; }

/* Camera scanner */
.scanner-box {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.scanner-box h2 { font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--muted); margin-bottom: 1rem; }

#video-container {
    position: relative;
    width: 100%;
    border-radius: 10px;
    overflow: hidden;
    background: #111;
    aspect-ratio: 4/3;
    display: none;
}
#video-container.active { display: block; }
#qr-video  { width: 100%; height: 100%; object-fit: cover; display: block; }
#qr-canvas { display: none; }

.scan-overlay {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    pointer-events: none;
}
.scan-frame {
    width: 55%; aspect-ratio: 1;
    border: 2.5px solid var(--orange);
    border-radius: 12px;
    box-shadow: 0 0 0 9999px rgba(0,0,0,0.35);
    animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
    0%, 100% { border-color: var(--orange); }
    50%       { border-color: #f07050; }
}

.scan-status {
    text-align: center;
    font-size: 0.82rem;
    font-family: 'DM Mono', monospace;
    color: var(--muted);
    margin-top: 0.75rem;
}

.btn-scan {
    width: 100%;
    padding: 0.8rem;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 700;
    font-family: 'Syne', sans-serif;
    letter-spacing: 0.04em;
    transition: opacity 0.15s, transform 0.1s;
}
.btn-scan:hover  { opacity: 0.87; transform: translateY(-1px); }
.btn-scan:active { transform: translateY(0); }
.btn-start { background: var(--orange); color: #fff; }
.btn-stop  { background: #444; color: #fff; }

/* Manual token input */
.manual-box {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.manual-box h2 { font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--muted); margin-bottom: 1rem; }

.token-row { display: flex; gap: 0.6rem; }
.token-input {
    flex: 1;
    padding: 0.6rem 0.9rem;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 0.85rem;
    font-family: 'DM Mono', monospace;
    color: var(--text);
    transition: border-color 0.15s;
}
.token-input:focus { outline: none; border-color: var(--orange); }

.btn-submit {
    padding: 0.6rem 1.2rem;
    background: var(--orange);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 700;
    font-family: 'Syne', sans-serif;
    cursor: pointer;
    transition: opacity 0.15s;
    white-space: nowrap;
}
.btn-submit:hover { opacity: 0.87; }

/* Result card */
.result-card {
    border-radius: 14px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    display: none;
    animation: slideUp 0.25s ease;
}
.result-card.show { display: block; }
.result-card.success { background: #e8f8f2; border: 1px solid #a3dfc4; }
.result-card.error   { background: #fdf0f0; border: 1px solid #f0b8b8; }
.result-card.warning { background: #fffbea; border: 1px solid #f0d980; }

.result-action {
    font-size: 1.3rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
}
.result-action.checkin  { color: var(--orange); }
.result-action.checkout { color: var(--green);  }
.result-action.error    { color: var(--red);    }
.result-action.warning  { color: var(--yellow); }

.result-detail {
    font-size: 0.85rem;
    font-family: 'DM Mono', monospace;
    color: #444;
    line-height: 1.6;
}

@keyframes slideUp {
    from { transform: translateY(12px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}

/* History */
.history-box {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.history-box h2 { font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--muted); margin-bottom: 1rem; }
.history-empty  { font-size: 0.85rem; color: var(--muted); font-family: 'DM Mono', monospace; }

.history-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.6rem 0;
    border-bottom: 1px solid var(--border);
    font-size: 0.83rem;
}
.history-item:last-child { border-bottom: none; }
.history-dot {
    width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.history-dot.checkin  { background: var(--orange); }
.history-dot.checkout { background: var(--green);  }
.history-dot.error    { background: var(--red);    }
.history-info { flex: 1; }
.history-tool { font-weight: 700; }
.history-meta { color: var(--muted); font-family: 'DM Mono', monospace; font-size: 0.78rem; }
.history-time { color: var(--muted); font-family: 'DM Mono', monospace; font-size: 0.75rem; white-space: nowrap; }
</style>

<div class="qr-wrap">

    <div class="qr-hero">
        <h1>📷 QR Scanner</h1>
        <p>Scan a member's QR code to check-in (pickup) or check-out (return) a tool.</p>
    </div>

    <!-- Result card -->
    <div class="result-card" id="resultCard">
        <div class="result-action" id="resultAction"></div>
        <div class="result-detail" id="resultDetail"></div>
    </div>

    <!-- Camera scanner -->
    <div class="scanner-box">
        <h2>📹 Camera Scanner</h2>
        <div id="video-container">
            <video id="qr-video" playsinline muted></video>
            <canvas id="qr-canvas"></canvas>
            <div class="scan-overlay"><div class="scan-frame"></div></div>
        </div>
        <div class="scan-status" id="scanStatus">Camera is off</div>
        <br>
        <button class="btn-scan btn-start" id="btnStart" onclick="startCamera()">▶ Start Camera</button>
        <button class="btn-scan btn-stop"  id="btnStop"  onclick="stopCamera()" style="display:none; margin-top:0.5rem">⏹ Stop Camera</button>
    </div>

    <!-- Manual token input -->
    <div class="manual-box">
        <h2>⌨️ Manual Token Entry</h2>
        <div class="token-row">
            <input type="text" class="token-input" id="manualToken" placeholder="Paste QR token here…">
            <button class="btn-submit" onclick="submitToken()">Scan</button>
        </div>
    </div>

    <!-- Scan history -->
    <div class="history-box">
        <h2>🕐 Recent Scans</h2>
        <div id="historyList"><p class="history-empty">No scans yet.</p></div>
    </div>

</div>

<script>
const bp       = window.BASE_PATH || '';
let stream     = null;
let scanning   = false;
let scanTimer  = null;
const history  = [];

// ── Camera ──
async function startCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        const video = document.getElementById('qr-video');
        video.srcObject = stream;
        await video.play();
        document.getElementById('video-container').classList.add('active');
        document.getElementById('btnStart').style.display = 'none';
        document.getElementById('btnStop').style.display  = 'block';
        document.getElementById('scanStatus').textContent = '🔍 Scanning for QR code…';
        scanning = true;
        requestAnimationFrame(scanFrame);
    } catch(e) {
        document.getElementById('scanStatus').textContent = '⚠️ Camera access denied or unavailable.';
    }
}

function stopCamera() {
    scanning = false;
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
    document.getElementById('video-container').classList.remove('active');
    document.getElementById('btnStart').style.display = 'block';
    document.getElementById('btnStop').style.display  = 'none';
    document.getElementById('scanStatus').textContent = 'Camera is off';
}

function scanFrame() {
    if (!scanning) return;
    const video  = document.getElementById('qr-video');
    const canvas = document.getElementById('qr-canvas');
    if (video.readyState !== video.HAVE_ENOUGH_DATA) { requestAnimationFrame(scanFrame); return; }

    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const code      = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'dontInvert' });

    if (code && code.data) {
        document.getElementById('scanStatus').textContent = '✅ QR detected! Processing…';
        stopCamera();
        processToken(code.data);
    } else {
        requestAnimationFrame(scanFrame);
    }
}

// ── Manual submit ──
function submitToken() {
    const token = document.getElementById('manualToken').value.trim();
    if (!token) { showResult('error', '⚠️ No Token', 'Please enter a QR token.'); return; }
    document.getElementById('manualToken').value = '';
    processToken(token);
}

// Enter key on manual input
document.getElementById('manualToken').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') submitToken();
});

// ── API call ──
async function processToken(token) {
    try {
        const res  = await fetch(`${bp}/api/reservations/scan_qr.php`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ qr_token: token }),
        });
        const data = await res.json();
        handleResponse(data, token);
    } catch(e) {
        showResult('error', '❌ Network Error', 'Could not reach the server. Check your connection.');
        addHistory('error', '—', 'Network error');
    }
}

function handleResponse(data, token) {
    if (data.success && data.data?.action === 'checkin') {
        showResult('checkin', '📦 Checked In!',
            `Tool: ${data.data.tool_name}\nMember: ${data.data.member_name}\nAt: ${data.data.checked_in_at}`
        );
        addHistory('checkin', data.data.tool_name, `${data.data.member_name} · check-in`);

    } else if (data.success && data.data?.action === 'checkout') {
        showResult('checkout', '✅ Checked Out!',
            `Tool: ${data.data.tool_name}\nMember: ${data.data.member_name}\nAt: ${data.data.checked_out_at}`
        );
        addHistory('checkout', data.data.tool_name, `${data.data.member_name} · check-out`);

    } else if (data.data?.action === 'already_completed') {
        showResult('warning', '⚠️ Already Completed',
            `Reservation for "${data.data.tool_name}" was already completed.`
        );
        addHistory('error', data.data.tool_name, 'Already completed');

    } else {
        showResult('error', '❌ Scan Failed', data.message || 'Unknown error.');
        addHistory('error', '—', data.message || 'Failed');
    }
}

// ── UI helpers ──
function showResult(type, action, detail) {
    const card   = document.getElementById('resultCard');
    const actEl  = document.getElementById('resultAction');
    const detEl  = document.getElementById('resultDetail');

    card.className  = `result-card show ${type === 'checkin' || type === 'checkout' ? 'success' : type === 'warning' ? 'warning' : 'error'}`;
    actEl.className = `result-action ${type}`;
    actEl.textContent = action;
    detEl.style.whiteSpace = 'pre-line';
    detEl.textContent = detail;

    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function addHistory(type, tool, meta) {
    const now = new Date().toLocaleTimeString();
    history.unshift({ type, tool, meta, time: now });
    renderHistory();
}

function renderHistory() {
    const el = document.getElementById('historyList');
    if (!history.length) { el.innerHTML = '<p class="history-empty">No scans yet.</p>'; return; }
    el.innerHTML = history.slice(0, 10).map(h => `
        <div class="history-item">
            <div class="history-dot ${h.type}"></div>
            <div class="history-info">
                <div class="history-tool">${h.tool}</div>
                <div class="history-meta">${h.meta}</div>
            </div>
            <div class="history-time">${h.time}</div>
        </div>
    `).join('');
}
</script>

<?php require_once '../includes/footer.php'; ?>
