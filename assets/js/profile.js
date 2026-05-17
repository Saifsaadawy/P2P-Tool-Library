// ── Profile Page ──────────────────────────────────
async function loadProfile() {
    const data = await apiFetch('/api/auth/get_profile.php');
    const m    = data.data.member;

    document.getElementById('fname').value  = m.Fname  ?? '';
    document.getElementById('lname').value  = m.Lname  ?? '';
    document.getElementById('phone').value  = m.Phone  ?? '';
    document.getElementById('city').value   = m.City   ?? '';
    document.getElementById('street').value = m.Street ?? '';

    document.getElementById('full-name').textContent = `${m.Fname} ${m.Lname}`;
    document.getElementById('email').textContent     = m.Email;
    document.getElementById('avatar').textContent    = (m.Fname?.[0] ?? '?').toUpperCase();

    // Trust score bar
    const score = parseInt(m.TrustScore ?? 0);
    document.getElementById('trust-value').textContent    = score;
    document.getElementById('trust-bar-fill').style.width = score + '%';

    // KYC badge
    const kyc = document.getElementById('kyc-badge');
    if (m.Verified == 1) {
        kyc.textContent = '✓ Identity Verified';
        kyc.className   = 'kyc-badge kyc-verified';
    } else {
        kyc.textContent = '⚠ Not Verified';
        kyc.className   = 'kyc-badge kyc-unverified';
    }
}

document.getElementById('profile-form')?.addEventListener('submit', async e => {
    e.preventDefault();
    const errEl  = document.getElementById('error-msg');
    const succEl = document.getElementById('success-msg');
    errEl.style.display = succEl.style.display = 'none';
    try {
        await apiFetch('/api/auth/update_profile.php', 'POST', {
            fname:  document.getElementById('fname').value,
            lname:  document.getElementById('lname').value,
            phone:  document.getElementById('phone').value,
            city:   document.getElementById('city').value,
            street: document.getElementById('street').value,
        });
        succEl.style.display = 'block';
        loadProfile();
    } catch (err) {
        errEl.textContent   = err.message;
        errEl.style.display = 'block';
    }
});

loadProfile();
