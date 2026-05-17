<?php
require_once '../config.php';
require_once '../includes/auth_check.php';
requireRole('member');

$pdo           = $GLOBALS['pdo'];
$user          = currentUser();
$reservationId = (int)($_GET['reservation_id'] ?? 0);

if (!$reservationId) {
    header('Location: ' . BASE_PATH . '/pages/my-reservations.php');
    exit;
}

// Only the LENDER (tool owner) can show the QR
$stmt = $pdo->prepare("
    SELECT r.ReservationID, r.Status, r.QR_Token, r.StartDate, r.EndDate,
           r.CheckedInAt, r.CheckedOutAt,
           t.Name AS tool_name, t.MemberID AS lender_id,
           CONCAT(m.Fname,' ',m.Lname) AS borrower_name
    FROM Reservation r
    JOIN Tool   t ON t.ToolID   = r.ToolID
    JOIN Member m ON m.MemberID = r.MemberID
    WHERE r.ReservationID = ?
");
$stmt->execute([$reservationId]);
$res = $stmt->fetch();

if (!$res || $res['lender_id'] != $user['id']) {
    header('Location: ' . BASE_PATH . '/pages/my-reservations.php');
    exit;
}

if ($res['Status'] !== 'approved') {
    header('Location: ' . BASE_PATH . '/pages/my-reservations.php');
    exit;
}

// Auto-generate QR token if missing (for old approved reservations)
if (empty($res['QR_Token'])) {
    $newToken = bin2hex(random_bytes(32));
    $pdo->prepare("UPDATE Reservation SET QR_Token = ? WHERE ReservationID = ?")
        ->execute([$newToken, $reservationId]);
    $res['QR_Token'] = $newToken;
}

require_once '../includes/header.php';
?>

require_once '../includes/header.php';
?>

<style>
.qr-wrapper {
    max-width: 480px;
    margin: 2.5rem auto;
    padding: 0 1rem 4rem;
    text-align: center;
}

.qr-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid #e0e0e0;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.07);
    margin-bottom: 1rem;
}

.qr-card h2 { font-size: 1.2rem; font-weight: 700; color: #333; margin-bottom: 0.3rem; }
.qr-card p  { color: #888; font-size: 0.88rem; margin-bottom: 1.5rem; }

#qr-img {
    width: 240px;
    height: 240px;
    margin: 0 auto 1.5rem;
    border-radius: 12px;
    border: 4px solid #f0f0f0;
    display: block;
}

.tool-info {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1rem;
    font-size: 0.88rem;
    color: #555;
    text-align: left;
    margin-bottom: 1.2rem;
}
.tool-info strong { color: #333; }

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1.2rem;
    border-radius: 20px;
    font-size: 0.88rem;
    font-weight: 600;
    margin-bottom: 1rem;
}
.status-waiting  { background: #fff3cd; color: #856404; }
.status-pickedup { background: #d3f9d8; color: #1a7431; }

.pulse-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #f08c00;
    animation: blink 1.2s ease-in-out infinite;
}
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }

.refresh-note {
    font-size: 0.8rem;
    color: #aaa;
    margin-top: 0.5rem;
}
</style>

<div class="qr-wrapper">
    <div class="qr-card">
        <h2>🔑 Your QR Code</h2>
        <p>Show this to <strong><?= htmlspecialchars($res['borrower_name'], ENT_QUOTES, 'UTF-8') ?></strong> to let them scan and pick up the tool.</p>

        <!-- QR Code image generated via Google Charts API -->
        <img id="qr-img"
             src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=<?= urlencode($res['QR_Token']) ?>"
             alt="QR Code">

        <div class="tool-info">
            <div><strong>🔧 Tool:</strong> <?= htmlspecialchars($res['tool_name'], ENT_QUOTES, 'UTF-8') ?></div>
            <div><strong>👤 Borrower:</strong> <?= htmlspecialchars($res['borrower_name'], ENT_QUOTES, 'UTF-8') ?></div>
            <div><strong>📅 Period:</strong> <?= $res['StartDate'] ?> → <?= $res['EndDate'] ?></div>
        </div>

        <?php if (!$res['CheckedInAt']): ?>
        <div class="status-badge status-waiting">
            <div class="pulse-dot"></div>
            Waiting for borrower to scan…
        </div>
        <p class="refresh-note">This page refreshes automatically every 5 seconds.</p>
        <?php else: ?>
        <div class="status-badge status-pickedup">
            ✅ Tool has been picked up!
        </div>
        <p style="font-size:0.85rem; color:#555">
            Picked up at: <strong><?= $res['CheckedInAt'] ?></strong>
        </p>
        <?php endif; ?>

    </div>

    <a href="my-reservations.php" class="btn btn-outline">← Back to Reservations</a>
</div>

<?php if (!$res['CheckedInAt']): ?>
<script>
// Auto-refresh every 5 seconds to detect when borrower scans
setTimeout(() => location.reload(), 5000);
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>