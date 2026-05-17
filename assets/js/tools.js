// ── Tools Pages (tools.php / tool-detail.php / add-tool.php) ──

// ── tools.php: load & search tools ──
async function loadTools(query = '', category = '') {
    const params = new URLSearchParams({ query, category, status: 'available' });
    const grid   = document.getElementById('tools-grid');
    if (!grid) return;
    try {
        const data = await apiFetch(`/api/tools/search_tools.php?${params}`);
        if (!data.data.length) {
            grid.innerHTML = '<p style="color:#888;text-align:center;padding:2rem">No tools found.</p>';
            return;
        }
        grid.innerHTML = data.data.map(t => `
            <div class="tool-card" onclick="location.href='tool-detail.php?id=${t.ToolID}'">
                <img src="${t.MediaURL ? (t.MediaURL.startsWith('http') ? t.MediaURL : (window.BASE_PATH ?? '') + t.MediaURL) : (window.BASE_PATH ?? '') + '/assets/img/placeholder.svg'}" alt="${t.Name}">
                <div class="tool-card-body">
                    <div class="category">${t.Category}</div>
                    <h3>${t.Name}</h3>
                    <div class="price">$${t.DailyRate}/day</div>
                </div>
            </div>`).join('');
    } catch (err) {
        if (grid) grid.innerHTML = `<p style="color:#e03131">${err.message}</p>`;
    }
}

document.getElementById('search-input')?.addEventListener('input', e => {
    loadTools(e.target.value, document.getElementById('category-select')?.value ?? '');
});

document.getElementById('category-select')?.addEventListener('change', e => {
    loadTools(document.getElementById('search-input')?.value ?? '', e.target.value);
});

// ── add-tool.php: submit new tool ──
document.getElementById('add-tool-form')?.addEventListener('submit', async e => {
    e.preventDefault();
    const errEl  = document.getElementById('error-msg');
    const succEl = document.getElementById('success-msg');
    errEl.style.display = succEl.style.display = 'none';
    const form = new FormData(e.target);
    try {
        const res  = await fetch(_base + '/api/tools/add_tool.php', {
            method: 'POST',
            body: form,
            credentials: 'same-origin'
        });
        const text = await res.text();
        if (!text) throw new Error('Empty response from server.');
        let data;
        try { data = JSON.parse(text); }
        catch { throw new Error('Server error: ' + text.substring(0, 100)); }
        if (!data.success) throw new Error(data.message);
        succEl.textContent   = '✅ Tool submitted for approval!';
        succEl.style.display = 'block';
        e.target.reset();
    } catch (err) {
        errEl.textContent   = err.message;
        errEl.style.display = 'block';
    }
});

if (document.getElementById('tools-grid')) loadTools();