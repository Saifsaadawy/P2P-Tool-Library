<?php
require_once '../config.php';
require_once '../includes/auth_check.php';
requireRole('maintenance_staff');

$pdo    = $GLOBALS['pdo'];
$staffId = $_SESSION['user_id'];

// Fetch tasks assigned to this staff member
$tasks = $pdo->prepare("
    SELECT mr.*, t.Name AS ToolName, t.CurrentStatus,
           t.ToolCondition, t.Category,
           l.Fname AS LibFname, l.Lname AS LibLname
    FROM MaintenanceRecord mr
    JOIN Tool t ON t.ToolID = mr.ToolID
    LEFT JOIN Librarian l ON l.LibrarianID = mr.LibrarianID
    WHERE mr.StaffID = ?
    ORDER BY mr.Date DESC
");
$tasks->execute([$staffId]);
$tasks = $tasks->fetchAll();

// Separate active (tool still in maintenance) vs completed
$activeTasks    = array_filter($tasks, fn($r) => $r['CurrentStatus'] === 'maintenance');
$completedTasks = array_filter($tasks, fn($r) => $r['CurrentStatus'] !== 'maintenance');

require_once '../includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
:root {
    --orange:  #e8431a;
    --dark:    #1a1917;
    --surface: #ffffff;
    --card:    #f8f7f6;
    --border:  #e5e2de;
    --text:    #1a1917;
    --muted:   #7a7570;
    --green:   #1a9e6b;
    --yellow:  #c9920a;
    --red:     #d04040;
    --nav-bg:  #ffffff;
}

body { background: #f3f2f0; color: var(--text); font-family: 'Syne', sans-serif; }

.ms-nav {
    background: #ffffff;
    border-bottom: 1px solid var(--border);
    padding: 0.9rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky; top: 0; z-index: 100;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
.ms-nav .logo { font-size: 1rem; font-weight: 800; letter-spacing: -0.02em; color: var(--dark); }
.ms-nav .logo span { color: var(--orange); }
.ms-nav nav a {
    margin-left: 1.5rem; font-size: 0.82rem; font-weight: 600; letter-spacing: 0.06em;
    text-transform: uppercase; color: var(--muted); transition: color 0.2s; text-decoration: none;
}
.ms-nav nav a:hover, .ms-nav nav a.active { color: var(--dark); }
.ms-nav nav a.logout { color: var(--red); }

.page-wrap { max-width: 1100px; margin: 0 auto; padding: 2rem 1.5rem 4rem; }

.hero {
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    display: flex; align-items: center; gap: 2rem;
    position: relative; overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.hero::before {
    content: '';
    position: absolute; top: -40px; right: -40px;
    width: 200px; height: 200px;
    background: radial-gradient(circle, #e8431a15, transparent 70%);
    pointer-events: none;
}
.hero-icon { font-size: 3rem; }
.hero-text h1 { font-size: 1.6rem; font-weight: 800; letter-spacing: -0.03em; }
.hero-text p { color: var(--muted); font-size: 0.9rem; margin-top: 0.25rem; font-family: 'DM Mono', monospace; }

.stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem; }
.stat-card {
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.stat-card .stat-val { font-size: 2rem; font-weight: 800; letter-spacing: -0.04em; }
.stat-card .stat-lbl { font-size: 0.78rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); margin-top: 0.2rem; }
.stat-card.active .stat-val { color: var(--orange); }
.stat-card.done .stat-val   { color: var(--green); }
.stat-card.total .stat-val  { color: var(--yellow); }

.section-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1rem;
}
.section-title { font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); }

.task-card {
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 0.75rem;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    align-items: start;
    transition: border-color 0.2s, box-shadow 0.2s;
    cursor: pointer;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.task-card:hover { border-color: var(--orange); box-shadow: 0 4px 12px rgba(232,67,26,0.1); }
.task-card.active-task { border-left: 3px solid var(--orange); }
.task-card.completed-task { border-left: 3px solid var(--green); opacity: 0.8; }

.task-tool { font-size: 1rem; font-weight: 700; }
.task-meta { font-size: 0.8rem; color: var(--muted); font-family: 'DM Mono', monospace; margin-top: 0.25rem; }
.task-desc { font-size: 0.85rem; color: #555; margin-top: 0.5rem; line-height: 1.5; }

.task-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end; }

.btn-ms {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.4rem 0.9rem;
    border-radius: 8px; border: none; cursor: pointer;
    font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
    font-family: 'Syne', sans-serif;
    transition: opacity 0.15s, transform 0.1s;
}
.btn-ms:hover { opacity: 0.85; transform: translateY(-1px); }
.btn-ms:active { transform: translateY(0); }
.btn-primary  { background: var(--orange); color: #fff; }
.btn-success  { background: var(--green);  color: #111; }
.btn-outline  { background: transparent; border: 1px solid var(--border); color: var(--muted); }
.btn-danger   { background: var(--red); color: #fff; }

.badge-ms {
    display: inline-block; padding: 2px 10px;
    border-radius: 20px; font-size: 0.72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.05em;
    font-family: 'DM Mono', monospace;
}
.badge-maintenance { background: #e8431a22; color: var(--orange); border: 1px solid #e8431a44; }
.badge-available   { background: #3ecf8e22; color: var(--green);  border: 1px solid #3ecf8e44; }
.badge-low    { background: #3ecf8e22; color: var(--green); border: 1px solid #3ecf8e44; }
.badge-medium { background: #f5c54222; color: var(--yellow); border: 1px solid #f5c54244; }
.badge-high   { background: #e0525222; color: var(--red);    border: 1px solid #e0525244; }

.empty-state {
    text-align: center; padding: 3rem;
    color: var(--muted); font-size: 0.9rem;
    background: #ffffff; border: 1px solid var(--border); border-radius: 12px;
}
.empty-state .empty-icon { font-size: 2.5rem; margin-bottom: 0.75rem; }

/* Modal */
.modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,0.4); z-index: 500;
    align-items: center; justify-content: center;
}
.modal-overlay.open { display: flex; }
.modal {
    background: #ffffff; border: 1px solid var(--border);
    border-radius: 16px; padding: 2rem;
    width: 100%; max-width: 560px; max-height: 90vh; overflow-y: auto;
    animation: slideUp 0.25s ease;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
}
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.modal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
.modal-head h3 { font-size: 1rem; font-weight: 800; }
.modal-close { background: none; border: none; color: var(--muted); cursor: pointer; font-size: 1.2rem; }

.form-group { margin-bottom: 1rem; }
.form-group label { display: block; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); margin-bottom: 0.4rem; }
.form-group input, .form-group select, .form-group textarea {
    width: 100%; padding: 0.6rem 0.9rem;
    background: #f8f7f6; border: 1px solid var(--border);
    border-radius: 8px; color: var(--text); font-size: 0.9rem;
    font-family: 'Syne', sans-serif;
    transition: border-color 0.15s;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    outline: none; border-color: var(--orange);
}
.form-group select option { background: var(--card); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

.toast {
    position: fixed; bottom: 2rem; right: 2rem; z-index: 999;
    padding: 0.9rem 1.5rem; border-radius: 10px; font-size: 0.88rem; font-weight: 600;
    animation: slideUp 0.3s ease;
    display: none;
}
.toast.success { background: var(--green); color: #111; display: block; }
.toast.error   { background: var(--red);   color: #fff; display: block; }

.divider { border: none; border-top: 1px solid var(--border); margin: 2rem 0; }

/* Image upload preview */
.img-preview-grid {
    display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.5rem;
}
.img-thumb {
    width: 70px; height: 70px; border-radius: 8px;
    object-fit: cover; border: 1px solid var(--border);
}
</style>

<nav class="ms-nav">
    <div class="logo">🔧 Tool <span>Library</span></div>
    <nav>
        <a href="maintenance_staff.php" class="active">My Tasks</a>
        <a href="#" onclick="openAllDamageReports(); return false;">Damage Reports</a>
        <a href="<?= BASE_PATH ?>/api/auth/logout.php" class="logout">Logout (<?= htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') ?>)</a>
    </nav>
</nav>

<div class="page-wrap">

    <!-- Hero -->
    <div class="hero">
        <div class="hero-icon">🛠</div>
        <div class="hero-text">
            <h1>Maintenance Dashboard</h1>
            <p><?= htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') ?> · Maintenance Staff</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card active">
            <div class="stat-val"><?= count($activeTasks) ?></div>
            <div class="stat-lbl">Active Tasks</div>
        </div>
        <div class="stat-card done">
            <div class="stat-val"><?= count($completedTasks) ?></div>
            <div class="stat-lbl">Completed</div>
        </div>
        <div class="stat-card total">
            <div class="stat-val"><?= count($tasks) ?></div>
            <div class="stat-lbl">Total Assigned</div>
        </div>
    </div>

    <!-- Active Tasks -->
    <div class="section-head">
        <span class="section-title">🔴 Active Tasks</span>
    </div>

    <?php if ($activeTasks): ?>
        <?php foreach ($activeTasks as $t): ?>
        <div class="task-card active-task" onclick="openLogModal(<?= $t['RecordID'] ?>, '<?= htmlspecialchars($t['ToolName'], ENT_QUOTES, 'UTF-8') ?>')">
            <div>
                <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                    <span class="task-tool"><?= htmlspecialchars($t['ToolName'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="badge-ms badge-maintenance">In Maintenance</span>
                    <?php if ($t['Category']): ?><span style="font-size:0.75rem; color:var(--muted); font-family:'DM Mono',monospace;"><?= htmlspecialchars($t['Category'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                </div>
                <div class="task-meta">
                    Record #<?= $t['RecordID'] ?> · Assigned <?= date('M d, Y', strtotime($t['Date'])) ?>
                    <?php if ($t['LibFname']): ?> · by <?= htmlspecialchars($t['LibFname'] . ' ' . $t['LibLname'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                </div>
                <?php if ($t['Description']): ?>
                <div class="task-desc"><?= htmlspecialchars($t['Description'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
            <div class="task-actions" onclick="event.stopPropagation()">
                <button class="btn-ms btn-primary" onclick="openLogModal(<?= $t['RecordID'] ?>, '<?= htmlspecialchars($t['ToolName'], ENT_QUOTES, 'UTF-8') ?>')">📝 Log Work</button>
                <button class="btn-ms btn-success" onclick="openCompleteModal(<?= $t['RecordID'] ?>, <?= $t['ToolID'] ?>, '<?= htmlspecialchars($t['ToolName'], ENT_QUOTES, 'UTF-8') ?>')">✅ Mark Done</button>
                <button class="btn-ms btn-outline" onclick="openDamageViewModal(<?= $t['ToolID'] ?>)">🔍 Damage</button>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">✅</div>
            <div>No active maintenance tasks — you're all caught up!</div>
        </div>
    <?php endif; ?>

    <hr class="divider">

    <!-- Completed Tasks -->
    <div class="section-head">
        <span class="section-title">✅ Completed Tasks</span>
    </div>

    <?php if ($completedTasks): ?>
        <?php foreach ($completedTasks as $t): ?>
        <div class="task-card completed-task">
            <div>
                <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                    <span class="task-tool"><?= htmlspecialchars($t['ToolName'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="badge-ms badge-available">Available</span>
                </div>
                <div class="task-meta">Record #<?= $t['RecordID'] ?> · <?= date('M d, Y', strtotime($t['Date'])) ?></div>
                <?php if ($t['Description']): ?>
                <div class="task-desc"><?= htmlspecialchars($t['Description'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
            <div style="text-align:right">
                <?php if ($t['Cost'] > 0): ?>
                <div style="font-size:0.85rem; font-family:'DM Mono',monospace; color:var(--yellow)">$<?= number_format($t['Cost'],2) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📋</div>
            <div>No completed tasks yet.</div>
        </div>
    <?php endif; ?>

</div>

<!-- ── LOG WORK MODAL ── -->
<div class="modal-overlay" id="logModal">
    <div class="modal">
        <div class="modal-head">
            <h3>📝 Log Maintenance Work</h3>
            <button class="modal-close" onclick="closeModal('logModal')">✕</button>
        </div>
        <p id="logModalSub" style="font-size:0.82rem; color:var(--muted); margin-bottom:1.25rem; font-family:'DM Mono',monospace;"></p>
        <form id="logForm">
            <input type="hidden" id="logRecordId">
            <div class="form-group">
                <label>Work Description</label>
                <textarea id="logDesc" rows="4" placeholder="Describe what maintenance work was performed..."></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Cost ($)</label>
                    <input type="number" id="logCost" min="0" step="0.01" value="0">
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" id="logDate" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Upload Photos (optional)</label>
                <input type="file" id="logImages" accept="image/*" multiple style="padding:0.4rem">
                <div class="img-preview-grid" id="logImgPreview"></div>
            </div>
            <!-- Link to damage report -->
            <div class="form-group">
                <label>Link to Damage Report (optional)</label>
                <select id="logDamageReport">
                    <option value="">— None —</option>
                </select>
            </div>
            <div style="display:flex; gap:0.75rem; margin-top:1rem">
                <button type="submit" class="btn-ms btn-primary" style="flex:1">Save Log</button>
                <button type="button" class="btn-ms btn-outline" onclick="closeModal('logModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ── COMPLETE MODAL ── -->
<div class="modal-overlay" id="completeModal">
    <div class="modal">
        <div class="modal-head">
            <h3>✅ Complete Maintenance</h3>
            <button class="modal-close" onclick="closeModal('completeModal')">✕</button>
        </div>
        <p id="completeSub" style="font-size:0.85rem; color:var(--muted); margin-bottom:1.5rem;"></p>
        <input type="hidden" id="completeRecordId">
        <input type="hidden" id="completeToolId">
        <div class="form-group">
            <label>Final Notes (optional)</label>
            <textarea id="completeNotes" rows="3" placeholder="Any final notes about the completed maintenance..."></textarea>
        </div>
        <div class="form-group">
            <label>Upload Final Photos (optional)</label>
            <input type="file" id="completeImages" accept="image/*" multiple style="padding:0.4rem">
            <div class="img-preview-grid" id="completeImgPreview"></div>
        </div>
        <p style="font-size:0.82rem; background:var(--card); padding:0.75rem 1rem; border-radius:8px; color:var(--muted); margin-bottom:1rem;">
            ⚠️ This will change the tool status from <b style="color:var(--orange)">maintenance</b> → <b style="color:var(--green)">available</b>
        </p>
        <div style="display:flex; gap:0.75rem">
            <button class="btn-ms btn-success" style="flex:1" onclick="completeTask()">Mark as Available</button>
            <button class="btn-ms btn-outline" onclick="closeModal('completeModal')">Cancel</button>
        </div>
    </div>
</div>

<!-- ── DAMAGE VIEW MODAL ── -->
<div class="modal-overlay" id="damageModal">
    <div class="modal">
        <div class="modal-head">
            <h3>🔍 Damage Reports</h3>
            <button class="modal-close" onclick="closeModal('damageModal')">✕</button>
        </div>
        <div id="damageModalBody" style="font-size:0.88rem; color:var(--muted)">Loading...</div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
const bp = window.BASE_PATH || '';

// ── Modal helpers ──
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function openModal(id)  { document.getElementById(id).classList.add('open'); }

function showToast(msg, type='success') {
    const t = document.getElementById('toast');
    t.textContent = msg; t.className = 'toast ' + type;
    setTimeout(() => { t.className = 'toast'; }, 3500);
}

// ── Image previews ──
function setupImgPreview(inputId, previewId) {
    document.getElementById(inputId).addEventListener('change', function() {
        const grid = document.getElementById(previewId);
        grid.innerHTML = '';
        [...this.files].forEach(f => {
            const img = document.createElement('img');
            img.className = 'img-thumb';
            img.src = URL.createObjectURL(f);
            grid.appendChild(img);
        });
    });
}
setupImgPreview('logImages', 'logImgPreview');
setupImgPreview('completeImages', 'completeImgPreview');

// ── Log Work Modal ──
async function openLogModal(recordId, toolName) {
    document.getElementById('logRecordId').value = recordId;
    document.getElementById('logModalSub').textContent = `Tool: ${toolName} · Record #${recordId}`;
    document.getElementById('logDesc').value = '';
    document.getElementById('logCost').value = '0';
    document.getElementById('logDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('logImgPreview').innerHTML = '';

    // Load damage reports for this tool
    try {
        const res = await fetch(`${bp}/api/maintenance/get_damage_reports.php?record_id=${recordId}`);
        const data = await res.json();
        const sel = document.getElementById('logDamageReport');
        sel.innerHTML = '<option value="">— None —</option>';
        if (data.success && data.data.length) {
            data.data.forEach(d => {
                sel.innerHTML += `<option value="${d.ReportID}">#${d.ReportID} — ${d.Severity} severity — ${d.Description?.slice(0,40) ?? ''}...</option>`;
            });
        }
    } catch(e) {}
    openModal('logModal');
}

// ── Log Form Submit ──
document.getElementById('logForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const recordId  = document.getElementById('logRecordId').value;
    const desc      = document.getElementById('logDesc').value.trim();
    const cost      = document.getElementById('logCost').value;
    const date      = document.getElementById('logDate').value;
    const damageId  = document.getElementById('logDamageReport').value;
    const files     = document.getElementById('logImages').files;

    if (!desc) { showToast('Description is required', 'error'); return; }

    const fd = new FormData();
    fd.append('record_id', recordId);
    fd.append('description', desc);
    fd.append('cost', cost);
    fd.append('date', date);
    if (damageId) fd.append('damage_id', damageId);
    [...files].forEach(f => fd.append('images[]', f));

    try {
        const res  = await fetch(`${bp}/api/maintenance/log_work.php`, { method:'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showToast('Work logged successfully!');
            closeModal('logModal');
        } else {
            showToast(data.message || 'Failed to save', 'error');
        }
    } catch(err) {
        showToast('Network error', 'error');
    }
});

// ── Complete Modal ──
function openCompleteModal(recordId, toolId, toolName) {
    document.getElementById('completeRecordId').value = recordId;
    document.getElementById('completeToolId').value = toolId;
    document.getElementById('completeSub').textContent = `Finishing maintenance for: ${toolName}`;
    document.getElementById('completeNotes').value = '';
    document.getElementById('completeImgPreview').innerHTML = '';
    openModal('completeModal');
}

async function completeTask() {
    const recordId = document.getElementById('completeRecordId').value;
    const toolId   = document.getElementById('completeToolId').value;
    const notes    = document.getElementById('completeNotes').value.trim();
    const files    = document.getElementById('completeImages').files;

    const fd = new FormData();
    fd.append('record_id', recordId);
    fd.append('tool_id', toolId);
    fd.append('notes', notes);
    [...files].forEach(f => fd.append('images[]', f));

    try {
        const res  = await fetch(`${bp}/api/maintenance/complete_task.php`, { method:'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showToast('Tool marked as available! ✅');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(data.message || 'Failed', 'error');
        }
    } catch(err) {
        showToast('Network error', 'error');
    }
}

// ── All Damage Reports (nav button) ──
async function openAllDamageReports() {
    openModal('damageModal');
    document.querySelector('#damageModal .modal-head h3').textContent = '🔍 All Damage Reports';
    const body = document.getElementById('damageModalBody');
    body.innerHTML = '<p style="color:var(--muted)">Loading damage reports...</p>';
    try {
        const res  = await fetch(`${bp}/api/maintenance/get_damage_reports.php`);
        const data = await res.json();
        if (!data.success || !data.data.length) {
            body.innerHTML = '<p style="color:var(--muted)">No damage reports found.</p>';
            return;
        }
        body.innerHTML = data.data.map(d => `
            <div style="background:var(--card);border:1px solid var(--border);border-radius:10px;padding:1rem;margin-bottom:0.75rem;">
                <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;flex-wrap:wrap;">
                    <b style="color:var(--text)">Report #${d.ReportID}</b>
                    <span class="badge-ms badge-${d.Severity}">${d.Severity}</span>
                    ${d.ToolName ? `<span style="font-size:0.78rem;color:var(--muted);font-family:'DM Mono',monospace">${d.ToolName}</span>` : ''}
                </div>
                <p style="font-size:0.85rem;color:#555;line-height:1.5">${d.Description ?? '—'}</p>
                <p style="font-size:0.75rem;font-family:'DM Mono',monospace;color:var(--muted);margin-top:0.4rem">Reservation #${d.ReservationID}</p>
                ${d.images && d.images.length ? `<div class="img-preview-grid">${d.images.map(img => `<img class="img-thumb" src="${bp}${img}">`).join('')}</div>` : ''}
            </div>
        `).join('');
    } catch(e) {
        body.innerHTML = '<p style="color:var(--red)">Error loading reports.</p>';
    }
}

// ── Damage Report Modal ──
async function openDamageViewModal(toolId) {
    openModal('damageModal');
    const body = document.getElementById('damageModalBody');
    body.innerHTML = '<p style="color:var(--muted)">Loading damage reports...</p>';
    try {
        const res  = await fetch(`${bp}/api/maintenance/get_damage_reports.php?tool_id=${toolId}`);
        const data = await res.json();
        if (!data.success || !data.data.length) {
            body.innerHTML = '<p style="color:var(--muted)">No damage reports found for this tool.</p>';
            return;
        }
        body.innerHTML = data.data.map(d => `
            <div style="background:var(--card);border:1px solid var(--border);border-radius:10px;padding:1rem;margin-bottom:0.75rem;">
                <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;">
                    <b style="color:var(--text)">Report #${d.ReportID}</b>
                    <span class="badge-ms badge-${d.Severity}">${d.Severity}</span>
                </div>
                <p style="font-size:0.85rem;color:#555;line-height:1.5">${d.Description ?? '—'}</p>
                <p style="font-size:0.75rem;font-family:'DM Mono',monospace;color:var(--muted);margin-top:0.4rem">Reservation #${d.ReservationID}</p>
                ${d.images && d.images.length ? `<div class="img-preview-grid">${d.images.map(img => `<img class="img-thumb" src="${bp}${img}">`).join('')}</div>` : ''}
            </div>
        `).join('');
    } catch(e) {
        body.innerHTML = '<p style="color:var(--red)">Error loading reports.</p>';
    }
}

// Close modals on overlay click
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>