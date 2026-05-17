// ── Shared / Global ───────────────────────────────
// Flash alert then hide after 3s
document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => el.style.opacity = '0', 3000);
});

// Active nav link highlight
document.querySelectorAll('.navbar nav a').forEach(a => {
    if (a.href === location.href) a.classList.add('active');
});
