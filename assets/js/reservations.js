// ── My Reservations Page ──────────────────────────
async function loadReservations(status = '') {
    const url  = `/api/reservations/get_reservations.php${status ? '?status=' + status : ''}`;
    const list = document.getElementById('reservations-list');
    if (!list) return;
    try {
        const data = await apiFetch(url);
        if (!data.data.length) {
            list.innerHTML = '<p style="color:#888;text-align:center;padding:2rem">No reservations found.</p>';
            return;
        }
        list.innerHTML = data.data.map(r => `
            <div class="reservation-card">
                <div class="reservation-info">
                    <h3>${r.tool_name}</h3>
                    <p>📅 ${r.StartDate} → ${r.EndDate}</p>
                    <p>💰 Total: <strong>$${r.TotalCost}</strong></p>
                    ${r.PickupDate ? `<p>🚗 Pickup: ${r.PickupDate}</p>` : ''}
                </div>
                <div class="reservation-actions">
                    <span class="badge badge-${r.Status}">${r.Status}</span>
                    ${r.Status === 'pending' ? `
                        <button class="btn btn-danger" style="font-size:12px;padding:4px 10px"
                                onclick="cancelReservation(${r.ReservationID})">Cancel</button>` : ''}
                    ${r.Status === 'approved' ? `
                        <a href="damage-report.php?reservation_id=${r.ReservationID}"
                           class="btn btn-outline" style="font-size:12px;padding:4px 10px">Report Damage</a>` : ''}
                </div>
            </div>`).join('');
    } catch (err) {
        list.innerHTML = `<p style="color:#e03131">${err.message}</p>`;
    }
}

async function cancelReservation(id) {
    if (!confirm('Cancel this reservation?')) return;
    try {
        await apiFetch('/api/reservations/cancel_reservation.php', 'POST', { reservation_id: id });
        loadReservations(window._currentFilter ?? '');
    } catch (err) { alert(err.message); }
}
