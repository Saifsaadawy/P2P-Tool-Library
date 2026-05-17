// ── Maintenance & Damage Pages ────────────────────
async function loadMaintenanceRecords() {
    const tbody = document.getElementById('maintenance-tbody');
    if (!tbody) return;
    try {
        const data = await apiFetch('/api/reports/maintenance_report.php');
        if (!data.data.length) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:#888">No records found.</td></tr>';
            return;
        }
        tbody.innerHTML = data.data.map(r => `
            <tr>
                <td>${r.RecordID}</td>
                <td>${r.tool_name}</td>
                <td>${r.staff_name}</td>
                <td>${r.Date}</td>
                <td>${r.Description}</td>
                <td>$${r.Cost}</td>
                <td>${r.damage_severity ?? '—'}</td>
            </tr>`).join('');
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="8" style="color:#e03131;padding:1rem">${err.message}</td></tr>`;
    }
}
