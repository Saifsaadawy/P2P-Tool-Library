// ── Dashboard Page (Librarian) ────────────────────
async function loadPendingReservations() {
    try {
        const data  = await apiFetch('/api/reservations/get_reservations.php?status=pending');
        const tbody = document.getElementById('reservations-tbody');
        if (!tbody) return;
        if (!data.data.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:#888">No pending reservations.</td></tr>';
            return;
        }
        tbody.innerHTML = data.data.map(r => `
            <tr>
                <td>${r.ReservationID}</td>
                <td>${r.member_name}</td>
                <td>${r.tool_name}</td>
                <td>${r.StartDate} → ${r.EndDate}</td>
                <td>$${r.TotalCost}</td>
                <td style="display:flex;gap:6px">
                    <button class="btn btn-success" style="font-size:12px;padding:4px 10px" onclick="approveReservation(${r.ReservationID})">Approve</button>
                    <button class="btn btn-danger"  style="font-size:12px;padding:4px 10px" onclick="cancelReservation(${r.ReservationID})">Reject</button>
                </td>
            </tr>`).join('');
    } catch (err) {
        console.error('loadPendingReservations:', err.message);
    }
}

async function approveReservation(id) {
    if (!confirm('Approve this reservation?')) return;
    try {
        await apiFetch('/api/reservations/approve_reservation.php', 'POST', { reservation_id: id });
        loadPendingReservations();
    } catch (err) { alert(err.message); }
}

async function cancelReservation(id) {
    if (!confirm('Reject this reservation?')) return;
    try {
        await apiFetch('/api/reservations/cancel_reservation.php', 'POST', { reservation_id: id });
        loadPendingReservations();
    } catch (err) { alert(err.message); }
}

async function approveListing(toolId) {
    await apiFetch('/api/tools/approve_listing.php', 'POST', { tool_id: toolId });
    location.reload();
}

loadPendingReservations();
