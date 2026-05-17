// ── reports.js ─────────────────────────────────
let currentReport = 'reservations';
let currentData   = [];

const ENDPOINTS = {
    reservations: '/api/reports/reservations_report.php',
    maintenance:  '/api/reports/maintenance_report.php',
    damage:       '/api/reports/damage_report.php',
    members:      '/api/reports/member_activity_report.php',
};

const COLUMNS = {
    reservations: ['ReservationID','member_name','tool_name','StartDate','EndDate','Status','TotalCost','payment_status'],
    maintenance:  ['RecordID','tool_name','staff_name','Date','Description','Cost','damage_severity'],
    damage:       ['ReportID','tool_name','Severity','Description','maintenance_cost'],
    members:      ['MemberID','member_name','Email','MembershipTier','TrustScore','total_reservations','total_spent','damage_reports'],
};

async function loadReport(type, btn = null) {
    currentReport = type;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    else {
        const btns = document.querySelectorAll('.tab-btn');
        btns.forEach(b => { if (b.textContent.toLowerCase().includes(type.slice(0,4))) b.classList.add('active'); });
    }

    const from = document.getElementById('from').value;
    const to   = document.getElementById('to').value;

    try {
        const res  = await fetch(`${ENDPOINTS[type]}?from=${from}&to=${to}`);
        const json = await res.json();
        if (!json.success) { alert('Failed to load report.'); return; }
        currentData = json.data;
        renderSummary(json.summary);
        renderTable(type, json.data);
    } catch (err) {
        alert('Error loading report: ' + err.message);
    }
}

function renderSummary(summary) {
    document.getElementById('summary-cards').innerHTML =
        Object.entries(summary).map(([k, v]) => `
            <div class="card">
                <span class="card-label">${k.replace(/_/g,' ')}</span>
                <span class="card-value">${
                    (k.includes('cost') || k.includes('revenue') || k.includes('spent'))
                    ? '$' + parseFloat(v ?? 0).toFixed(2) : v
                }</span>
            </div>`).join('');
}

function renderTable(type, data) {
    const cols  = COLUMNS[type];
    document.getElementById('report-thead').innerHTML =
        '<tr>' + cols.map(c => `<th>${c.replace(/_/g,' ')}</th>`).join('') + '</tr>';
    const noData = document.getElementById('no-data');
    if (!data.length) {
        document.getElementById('report-tbody').innerHTML = '';
        noData.style.display = 'block';
        return;
    }
    noData.style.display = 'none';
    document.getElementById('report-tbody').innerHTML = data.map(row =>
        '<tr>' + cols.map(c => `<td>${row[c] ?? '—'}</td>`).join('') + '</tr>'
    ).join('');
}

function exportCSV() {
    if (!currentData.length) return;
    const cols = COLUMNS[currentReport];
    const csv  = [
        cols.join(','),
        ...currentData.map(r => cols.map(c => `"${r[c] ?? ''}"`).join(','))
    ].join('\n');
    const a = Object.assign(document.createElement('a'), {
        href:     URL.createObjectURL(new Blob([csv], { type: 'text/csv' })),
        download: `${currentReport}_${document.getElementById('from').value}.csv`
    });
    a.click();
}

// Load default on page open
loadReport('reservations');
