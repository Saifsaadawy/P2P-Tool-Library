<?php
require_once '../config.php';
require_once '../includes/auth_check.php';
requireRole('member');

$pdo  = $GLOBALS['pdo'];
$user = currentUser();

require_once '../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/reservations.css">

<div class="reservations-container">
    <h2>My Reservations</h2>

    <div style="display:flex;gap:.6rem;margin-bottom:1.2rem;flex-wrap:wrap">
        <button class="btn btn-outline" onclick="filterRes(this,'')">All</button>
        <button class="btn btn-outline" onclick="filterRes(this,'pending')">Pending</button>
        <button class="btn btn-outline" onclick="filterRes(this,'approved')">Approved</button>
        <button class="btn btn-outline" onclick="filterRes(this,'completed')">Completed</button>
        <button class="btn btn-outline" onclick="filterRes(this,'cancelled')">Cancelled</button>
    </div>

    <div id="reservations-list">
        <p style="color:#888;text-align:center">Loading…</p>
    </div>
</div>

<script src="<?= BASE_PATH ?>/assets/js/api.js"></script>
<script>
const BP    = window.BASE_PATH || '';
const MY_ID = <?= (int)$user['id'] ?>;

function filterRes(btn, status) {
    document.querySelectorAll('.reservations-container .btn').forEach(b => b.classList.remove('btn-primary'));
    btn.classList.add('btn-primary');
    loadReservations(status);
}

function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
}

async function loadReservations(status = '') {
    const url  = `/api/reservations/get_reservations.php${status ? '?status=' + status : ''}`;
    try {
        const data = await apiFetch(url);
        const list = document.getElementById('reservations-list');

        if (!data.data.length) {
            list.innerHTML = '<p style="color:#888;text-align:center;padding:2rem">No reservations found.</p>';
            return;
        }

        list.innerHTML = data.data.map(r => {
            const isLender = r.lender_id == MY_ID;
            return `
            <div class="reservation-card">
                <div class="reservation-info">
                    <h3>${escapeHtml(r.tool_name)}</h3>
                    ${isLender ? '<span style="font-size:0.78rem;background:#dbe4ff;color:#364fc7;padding:2px 8px;border-radius:12px">🔧 You are the Lender</span>' : ''}
                    <p>📅 ${r.StartDate} → ${r.EndDate}</p>
                    <p>💰 Total: <strong>$${r.TotalCost}</strong></p>
                    ${r.CheckedInAt ? `<p>📦 Picked up: <strong>${r.CheckedInAt}</strong></p>` : ''}
                </div>
                <div class="reservation-actions">
                    <span class="badge badge-${r.Status}">${r.Status}</span>

                    ${r.Status === 'pending' ? `
                        <button class="btn btn-danger" style="font-size:12px;padding:4px 10px"
                                onclick="cancelReservation(${r.ReservationID})">Cancel</button>` : ''}

                    ${r.Status === 'approved' ? `
                        <a href="${BP}/pages/damage-report.php?reservation_id=${r.ReservationID}"
                           class="btn btn-outline" style="font-size:12px;padding:4px 10px">
                           Report Damage</a>` : ''}

                    ${(r.Status === 'approved' || r.Status === 'completed') ? `
                        <a href="${BP}/pages/chat.php?reservation_id=${r.ReservationID}"
                           class="btn btn-outline"
                           style="font-size:12px;padding:4px 10px;color:#3b5bdb;border-color:#3b5bdb">
                           💬 Chat</a>` : ''}

                    ${r.Status === 'approved' && isLender ? `
                        <a href="${BP}/pages/show-qr.php?reservation_id=${r.ReservationID}"
                           class="btn btn-outline"
                           style="font-size:12px;padding:4px 10px;color:#2f9e44;border-color:#2f9e44">
                           📱 Show QR</a>` : ''}

                    ${r.Status === 'approved' && !isLender ? `
                        <a href="${BP}/pages/scan-qr.php?reservation_id=${r.ReservationID}"
                           class="btn btn-outline"
                           style="font-size:12px;padding:4px 10px;color:#f08c00;border-color:#f08c00">
                           📷 ${r.CheckedInAt ? '⏱ View Timer' : 'Scan QR'}</a>` : ''}
                </div>
            </div>`;
        }).join('');
    } catch(e) {
        document.getElementById('reservations-list').innerHTML =
            '<p style="color:red;text-align:center;padding:2rem">Failed to load reservations.</p>';
    }
}

async function cancelReservation(id) {
    if (!confirm('Are you sure you want to cancel this reservation?')) return;
    try {
        await apiFetch(`/api/reservations/cancel_reservation.php`, 'POST', { reservation_id: id });
        loadReservations();
    } catch(e) {
        alert(e.message || 'Failed to cancel.');
    }
}

loadReservations();
</script>
<?php require_once '../includes/footer.php'; ?>