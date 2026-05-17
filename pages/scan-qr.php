<?php
require_once '../config.php';
require_once '../includes/auth_check.php';
requireRole('member');

$pdo           = $GLOBALS['pdo'];
$user          = currentUser();
$reservationId = (int)($_GET['reservation_id'] ?? 0);

if (!$reservationId) {
    header('Location: ' . BASE_PATH . '/pages/my-reservations.php');
    exit;
}

// Only the BORROWER can scan
$stmt = $pdo->prepare("
    SELECT r.ReservationID, r.Status, r.StartDate, r.EndDate,
           r.CheckedInAt, r.CheckedOutAt, r.TotalCost,
           t.Name AS tool_name, t.DailyRate,
           CONCAT(lender.Fname,' ',lender.Lname) AS lender_name
    FROM Reservation r
    JOIN Tool   t      ON t.ToolID      = r.ToolID
    JOIN Member lender ON lender.MemberID = t.MemberID
    WHERE r.ReservationID = ? AND r.MemberID = ?
");
$stmt->execute([$reservationId, $user['id']]);
$res = $stmt->fetch();

if (!$res || $res['Status'] !== 'approved') {
    header('Location: ' . BASE_PATH . '/pages/my-reservations.php');
    exit;
}

require_once '../includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsqr/1.4.0/jsQR.min.js"></script>

<style>
.scan-wrapper {
    max-width: 520px;
    margin: 2rem auto;
    padding: 0 1rem 4rem;
}

.scan-header {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e0e0e0;
    padding: 1.3rem 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.scan-header h2 { font-size: 1.1rem; font-weight: 700; color: #333; margin-bottom: 0.3rem; }
.scan-header p  { font-size: 0.85rem; color: #888; margin: 0; }

/* Camera box */
.camera-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e0e0e0;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

#video-container {
    position: relative;
    width: 100%;
    border-radius: 12px;
    overflow: hidden;
    background: #111;
    aspect-ratio: 4/3;
    display: none;
    margin-bottom: 1rem;
}
#video-container.active { display: block; }
#qr-video  { width: 100%; height: 100%; object-fit: cover; }
#qr-canvas { display: none; }

.scan-overlay {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    pointer-events: none;
}
.scan-frame {
    width: 60%; aspect-ratio: 1;
    border: 2.5px solid #3b5bdb;
    border-radius: 14px;
    box-shadow: 0 0 0 9999px rgba(0,0,0,0.4);
    animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
    0%,100% { border-color: #3b5bdb; }
    50%      { border-color: #748ffc; }
}

.scan-status {
    text-align: center;
    font-size: 0.82rem;
    font-family: 'DM Mono', monospace;
    color: #888;
    margin-bottom: 0.75rem;
}

.btn-camera {
    width: 100%;
    padding: 0.8rem;
    border-radius: 10px;
    border: none;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    transition: opacity 0.15s;
    font-family: inherit;
}
.btn-camera:hover { opacity: 0.88; }
.btn-start { background: #3b5bdb; color: #fff; }
.btn-stop  { background: #495057; color: #fff; margin-top: 0.5rem; }

/* Timer card — hidden until scan */
.timer-card {
    background: #fff;
    border-radius: 16px;
    border: 2px solid #3b5bdb;
    padding: 2rem 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 4px 20px rgba(59,91,219,0.12);
    text-align: center;
    display: none;
}
.timer-card.show { display: block; animation: slideUp 0.3s ease; }
@keyframes slideUp { from{transform:translateY(16px);opacity:0} to{transform:translateY(0);opacity:1} }

.timer-label { font-size: 0.82rem; color: #888; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 0.5rem; }
.timer-display {
    font-family: 'DM Mono', monospace;
    font-size: 3.5rem;
    font-weight: 500;
    color: #3b5bdb;
    letter-spacing: 0.04em;
    margin-bottom: 0.5rem;
}
.timer-display.warning { color: #f08c00; }
.timer-display.overdue { color: #e03131; animation: blink-red 1s ease-in-out infinite; }
@keyframes blink-red { 0%,100%{opacity:1} 50%{opacity:0.5} }

.timer-sub {
    font-size: 0.85rem;
    color: #555;
    margin-bottom: 1.2rem;
}
.timer-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-top: 1rem;
}
.timer-stat {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 0.75rem;
    font-size: 0.82rem;
}
.timer-stat strong { display: block; font-size: 1rem; color: #333; }
.timer-stat span   { color: #888; }

/* Success state */
.success-card {
    background: #d3f9d8;
    border: 1px solid #8ce99a;
    border-radius: 16px;
    padding: 1.5rem;
    text-align: center;
    display: none;
    margin-bottom: 1rem;
}
.success-card.show { display: block; }
.success-icon { font-size: 2.5rem; margin-bottom: 0.5rem; }
.success-title { font-size: 1.1rem; font-weight: 700; color: #1a7431; margin-bottom: 0.3rem; }
.success-sub   { font-size: 0.85rem; color: #2f9e44; }
</style>

<div class="scan-wrapper">

    <!-- Header -->
    <div class="scan-header">
        <h2>📷 Scan QR to Pick Up Tool</h2>
        <p>🔧 <strong><?= htmlspecialchars($res['tool_name'], ENT_QUOTES, 'UTF-8') ?></strong>
           &nbsp;·&nbsp; 👤 From: <?= htmlspecialchars($res['lender_name'], ENT_QUOTES, 'UTF-8') ?>
           &nbsp;·&nbsp; 📅 <?= $res['StartDate'] ?> → <?= $res['EndDate'] ?>
        </p>
    </div>

    <?php if ($res['CheckedInAt']): ?>
    <!-- Already checked in — show timer from DB -->
    <div class="timer-card show" id="timerCard">
        <div class="timer-label">⏱ Time Since Pickup</div>
        <div class="timer-display" id="timerDisplay">00:00:00</div>
        <div class="timer-sub">Tool picked up at <strong><?= $res['CheckedInAt'] ?></strong></div>
        <div class="timer-info">
            <div class="timer-stat">
                <strong><?= $res['StartDate'] ?></strong>
                <span>Start Date</span>
            </div>
            <div class="timer-stat">
                <strong><?= $res['EndDate'] ?></strong>
                <span>Due Date</span>
            </div>
            <div class="timer-stat">
                <strong>$<?= number_format($res['DailyRate'], 2) ?></strong>
                <span>Daily Rate</span>
            </div>
            <div class="timer-stat">
                <strong>$<?= number_format($res['TotalCost'], 2) ?></strong>
                <span>Total Cost</span>
            </div>
        </div>
    </div>
    <script>
        const checkedInAt = new Date("<?= str_replace(' ', 'T', $res['CheckedInAt']) ?>");
        const dueDate     = new Date("<?= $res['EndDate'] ?>T23:59:59");
        startTimer(checkedInAt, dueDate);
    </script>

    <?php else: ?>
    <!-- Camera scanner -->
    <div class="camera-card">
        <div id="video-container">
            <video id="qr-video" playsinline muted></video>
            <canvas id="qr-canvas"></canvas>
            <div class="scan-overlay"><div class="scan-frame"></div></div>
        </div>
        <div class="scan-status" id="scanStatus">📷 Camera is off — press Start to scan</div>
        <button class="btn-camera btn-start" id="btnStart" onclick="startCamera()">▶ Start Camera & Scan</button>
        <button class="btn-camera btn-stop"  id="btnStop"  onclick="stopCamera()" style="display:none">⏹ Stop Camera</button>
    </div>

    <!-- Timer — shown after scan -->
    <div class="timer-card" id="timerCard">
        <div class="timer-label">⏱ Time Since Pickup</div>
        <div class="timer-display" id="timerDisplay">00:00:00</div>
        <div class="timer-sub" id="timerSub">Tool picked up successfully!</div>
        <div class="timer-info">
            <div class="timer-stat">
                <strong><?= $res['StartDate'] ?></strong>
                <span>Start Date</span>
            </div>
            <div class="timer-stat">
                <strong><?= $res['EndDate'] ?></strong>
                <span>Due Date</span>
            </div>
            <div class="timer-stat">
                <strong>$<?= number_format($res['DailyRate'], 2) ?></strong>
                <span>Daily Rate</span>
            </div>
            <div class="timer-stat">
                <strong>$<?= number_format($res['TotalCost'], 2) ?></strong>
                <span>Total Cost</span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Success card -->
    <div class="success-card" id="successCard">
        <div class="success-icon">🎉</div>
        <div class="success-title">Tool Picked Up Successfully!</div>
        <div class="success-sub">Timer started. Return the tool by <strong><?= $res['EndDate'] ?></strong>.</div>
    </div>

    <a href="my-reservations.php" class="btn btn-outline" style="display:block;text-align:center;margin-top:1rem">← Back to Reservations</a>
</div>

<script src="<?= BASE_PATH ?>/assets/js/api.js"></script>
<script>
const RESERVATION_ID = <?= $reservationId ?>;
const DUE_DATE       = new Date("<?= $res['EndDate'] ?>T23:59:59");
const bp             = window.BASE_PATH || '';
let stream = null, scanning = false;

// ── Camera ──────────────────────────────────────────────────────────────────
async function startCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        const video = document.getElementById('qr-video');
        video.srcObject = stream;
        await video.play();
        document.getElementById('video-container').classList.add('active');
        document.getElementById('btnStart').style.display = 'none';
        document.getElementById('btnStop').style.display  = 'block';
        document.getElementById('scanStatus').textContent = '🔍 Scanning… point camera at QR code';
        scanning = true;
        requestAnimationFrame(scanFrame);
    } catch(e) {
        document.getElementById('scanStatus').textContent = '⚠️ Camera access denied. Please allow camera access.';
    }
}

function stopCamera() {
    scanning = false;
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
    document.getElementById('video-container').classList.remove('active');
    document.getElementById('btnStart').style.display = 'block';
    document.getElementById('btnStop').style.display  = 'none';
    document.getElementById('scanStatus').textContent = '📷 Camera stopped.';
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
        processQR(code.data);
    } else {
        requestAnimationFrame(scanFrame);
    }
}

// ── API call ─────────────────────────────────────────────────────────────────
async function processQR(token) {
    try {
        const res  = await fetch(`${bp}/api/reservations/borrower_scan.php`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ qr_token: token, reservation_id: RESERVATION_ID }),
        });
        const data = await res.json();

        if (data.success) {
            document.getElementById('successCard').classList.add('show');
            document.getElementById('timerCard').classList.add('show');
            const checkedInAt = new Date(data.data.checked_in_at.replace(' ', 'T'));
            startTimer(checkedInAt, DUE_DATE);
        } else {
            document.getElementById('scanStatus').textContent = '❌ ' + (data.message || 'Scan failed. Try again.');
            document.getElementById('btnStart').style.display = 'block';
        }
    } catch(e) {
        document.getElementById('scanStatus').textContent = '❌ Network error. Please try again.';
        document.getElementById('btnStart').style.display = 'block';
    }
}

// ── Timer ─────────────────────────────────────────────────────────────────────
function startTimer(startTime, dueDate) {
    const display = document.getElementById('timerDisplay');

    function tick() {
        const now      = new Date();
        const elapsed  = Math.floor((now - startTime) / 1000);
        const h = Math.floor(elapsed / 3600);
        const m = Math.floor((elapsed % 3600) / 60);
        const s = elapsed % 60;
        display.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;

        // Color warning when close to or past due date
        const remaining = dueDate - now;
        if (remaining < 0) {
            display.className = 'timer-display overdue';
        } else if (remaining < 86400000) { // less than 1 day
            display.className = 'timer-display warning';
        } else {
            display.className = 'timer-display';
        }
    }

    tick();
    setInterval(tick, 1000);
}
</script>

<?php require_once '../includes/footer.php'; ?>