// ── Auth Pages (login / register) ────────────────
document.getElementById('login-form')?.addEventListener('submit', async e => {
    e.preventDefault();
    const email    = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    try {
        const role = document.getElementById('role-input')?.value ?? 'member';
        const data = await apiFetch('../api/auth/login.php', 'POST', { email, password, role });
        location.href = data.data?.redirect ?? '../pages/index.php';
    } catch (err) {
        document.getElementById('error-msg').textContent = err.message;
    }
});

document.getElementById('register-form')?.addEventListener('submit', async e => {
    e.preventDefault();
    const form = Object.fromEntries(new FormData(e.target));
    try {
        await apiFetch('../api/auth/register.php', 'POST', form);
        location.href = 'login.php';
    } catch (err) {
        document.getElementById('error-msg').textContent = err.message;
    }
});