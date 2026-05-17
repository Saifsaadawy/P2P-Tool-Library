<?php
require_once '../config.php';
require_once '../includes/auth_check.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = $GLOBALS['pdo'];

// Single optimized query instead of 3 separate ones
$stmt = $pdo->prepare("
    SELECT
        m.MembershipTier, m.TrustScore, m.Balance,
        (SELECT COUNT(*) FROM Reservation WHERE MemberID = m.MemberID) AS totalRes,
        (SELECT COUNT(*) FROM Reservation WHERE MemberID = m.MemberID AND Status='pending') AS pendingRes
    FROM Member m
    WHERE m.MemberID = ?
");
$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch();

// Guard: if member not found, force logout
if (!$member) {
    header('Location: ../api/auth/logout.php');
    exit;
}

require_once '../includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/tools.css">

<div class="container" style="margin-top:2rem">
    <h2 style="margin-bottom:1.5rem">Welcome back, <?= htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') ?> 👋</h2>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Reservations</div>
            <div class="stat-value"><?= (int)$member['totalRes'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?= (int)$member['pendingRes'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Membership Tier</div>
            <div class="stat-value" style="font-size:1.2rem;text-transform:capitalize">
                <?= htmlspecialchars($member['MembershipTier'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Balance</div>
            <div class="stat-value">$<?= number_format($member['Balance'], 2) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Trust Score</div>
            <div class="stat-value" style="color:#2f9e44"><?= (int)$member['TrustScore'] ?></div>
        </div>
    </div>

    <!-- Quick actions -->
    <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:2rem">
        <a href="tools.php" class="btn btn-primary">🔍 Browse Tools</a>
        <a href="my-reservations.php" class="btn btn-outline">📋 My Reservations</a>
        <a href="add-tool.php" class="btn btn-outline">➕ List a Tool</a>
        <a href="profile.php" class="btn btn-outline">👤 My Profile</a>
    </div>

    <!-- Latest tools -->
    <h3 style="margin-bottom:1rem">Recently Available Tools</h3>
    <div class="tools-grid" id="tools-grid">
        <p style="color:#888">Loading...</p>
    </div>
</div>

<script src="<?= BASE_PATH ?>/assets/js/api.js"></script>
<script>
function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
}

function safeUrl(url) {
    if (!url) return '/tool-library-fixed/assets/img/placeholder.svg';
    if (url.startsWith('http://') || url.startsWith('https://')) return url;
    if (url.startsWith('/tool-library-fixed/')) return url;
    if (url.startsWith('/')) return '/tool-library-fixed' + url;
    return '/tool-library-fixed/' + url;
}

(async () => {
    const grid = document.getElementById('tools-grid');
    try {
        const data = await apiFetch('/api/tools/search_tools.php?status=available');

        if (!data || !data.data || !data.data.length) {
            grid.innerHTML = '<p style="color:#888">No tools available right now.</p>';
            return;
        }

        grid.innerHTML = data.data.slice(0, 6).map(t => {
            const id   = encodeURIComponent(t.ToolID);
            const name = escapeHtml(t.Name);
            const cat  = escapeHtml(t.Category);
            const rate = escapeHtml(t.DailyRate);
            const img  = safeUrl(t.MediaURL);
            return `
                <div class="tool-card" role="button" tabindex="0"
                    data-href="tool-detail.php?id=${id}"
                    style="cursor:pointer">
                    <img src="${img}" alt="${name}">
                    <div class="tool-card-body">
                        <div class="category">${cat}</div>
                        <h3>${name}</h3>
                        <div class="price">$${rate}/day</div>
                    </div>
                </div>`;
        }).join('');

        document.querySelectorAll('.tool-card[data-href]').forEach(card => {
            const go = () => location.href = card.dataset.href;
            card.addEventListener('click', go);
            card.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') go(); });
        });

    } catch (err) {
        console.error('Failed to load tools:', err);
        grid.innerHTML = '<p style="color:red">⚠️ Failed to load tools. Please refresh and try again.</p>';
    }
})();
</script>

<?php require_once '../includes/footer.php'; ?>