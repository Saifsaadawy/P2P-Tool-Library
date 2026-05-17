<?php
require_once '../config.php';
require_once '../includes/auth_check.php';
requireRole('member');
require_once '../includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/tools.css">

<div class="container">
    <div id="detail-wrapper" style="margin-top:1.5rem">
        <p style="color:#888">Loading tool details…</p>
    </div>
</div>

<script src="<?= BASE_PATH ?>/assets/js/api.js"></script>
<script>
(async () => {
    const id   = new URLSearchParams(location.search).get('id');
    const wrap = document.getElementById('detail-wrapper');
    if (!id) { wrap.innerHTML = '<p class="alert alert-danger">No tool selected.</p>'; return; }

    const data = await apiFetch(`/api/tools/get_tool.php?id=${id}`);
    const t    = data.data.tool;
    const basePath = window.BASE_PATH ?? '';
    const img  = basePath + (t.images?.[0] ?? '/assets/img/placeholder.svg');

    wrap.innerHTML = `
        <a href="tools.php" style="font-size:.88rem;color:#3b5bdb">← Back to Tools</a>
        <div class="tool-detail-layout">
            <div>
                <img class="tool-detail-img" src="${img}" alt="${t.Name}">
            </div>
            <div class="tool-info">
                <h2>${t.Name}</h2>
                <p class="daily-rate">$${t.DailyRate} / day</p>
                <p style="color:#555;margin-bottom:1rem">${t.Description ?? ''}</p>

                <div class="tool-meta">
                    <div class="tool-meta-row"><span>Category</span><span>${t.Category}</span></div>
                    <div class="tool-meta-row"><span>Condition</span><span>${t.Condition}</span></div>
                    <div class="tool-meta-row"><span>Security Deposit</span><span>$${t.SecurityDeposit}</span></div>
                    <div class="tool-meta-row"><span>Owner</span><span>${t.owner_name}</span></div>
                    <div class="tool-meta-row"><span>Status</span><span><span class="badge badge-${t.CurrentStatus}">${t.CurrentStatus}</span></span></div>
                </div>

                ${t.CurrentStatus === 'available' ? `
                <div style="margin-top:1.2rem">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" id="start-date" class="form-control" min="${new Date().toISOString().split('T')[0]}">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" id="end-date" class="form-control">
                    </div>
                    <div id="cost-preview" style="margin:.8rem 0;color:#3b5bdb;font-weight:500"></div>
                    <button id="reserve-btn" class="btn btn-primary">Reserve Now</button>
                    <div id="res-msg" class="alert" style="display:none;margin-top:.8rem"></div>
                </div>` : `<p class="alert alert-info" style="margin-top:1rem">This tool is currently not available.</p>`}
            </div>
        </div>`;

    // Cost preview
    const dailyRate = parseFloat(t.DailyRate);
    const deposit   = parseFloat(t.SecurityDeposit);
    function updateCost() {
        const s = document.getElementById('start-date')?.value;
        const e = document.getElementById('end-date')?.value;
        if (!s || !e || s >= e) return;
        const days = Math.ceil((new Date(e)-new Date(s))/(1000*60*60*24));
        const cost = (dailyRate * days + deposit).toFixed(2);
        document.getElementById('cost-preview').textContent = `Estimated total: $${cost} (${days} days + $${deposit} deposit)`;
    }
    document.getElementById('start-date')?.addEventListener('change', updateCost);
    document.getElementById('end-date')?.addEventListener('change', updateCost);

    document.getElementById('reserve-btn')?.addEventListener('click', async () => {
        const start = document.getElementById('start-date').value;
        const end   = document.getElementById('end-date').value;
        const msg   = document.getElementById('res-msg');
        msg.style.display = 'none';
        try {
            await apiFetch('/api/reservations/create_reservation.php', 'POST', { tool_id: id, start_date: start, end_date: end });
            msg.textContent = '✅ Reservation created! Redirecting…';
            msg.className   = 'alert alert-success';
            msg.style.display = 'block';
            setTimeout(() => location.href = 'my-reservations.php', 1500);
        } catch (err) {
            msg.textContent   = err.message;
            msg.className     = 'alert alert-danger';
            msg.style.display = 'block';
        }
    });
})();
</script>
<?php require_once '../includes/footer.php'; ?>