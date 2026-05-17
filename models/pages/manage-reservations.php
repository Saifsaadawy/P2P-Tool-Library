<?php
require_once '../config.php';
require_once '../includes/auth_check.php';
requireRole('librarian');

$pdo = $GLOBALS['pdo'];

// Handle approve / cancel action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['reservation_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id && in_array($action, ['approved', 'cancelled'])) {
        $stmt = $pdo->prepare("UPDATE Reservation SET Status=?, LibrarianID=? WHERE ReservationID=?");
        $stmt->execute([$action, $_SESSION['user_id'], $id]);

        // If approved → mark tool as reserved
        if ($action === 'approved') {
            $toolStmt = $pdo->prepare("SELECT ToolID FROM Reservation WHERE ReservationID=?");
            $toolStmt->execute([$id]);
            $toolID = $toolStmt->fetchColumn();
            if ($toolID) {
                $pdo->prepare("UPDATE Tool SET CurrentStatus='reserved' WHERE ToolID=?")->execute([$toolID]);
            }
        }
        // If cancelled → mark tool back to available
        if ($action === 'cancelled') {
            $toolStmt = $pdo->prepare("SELECT ToolID FROM Reservation WHERE ReservationID=?");
            $toolStmt->execute([$id]);
            $toolID = $toolStmt->fetchColumn();
            if ($toolID) {
                $pdo->prepare("UPDATE Tool SET CurrentStatus='available' WHERE ToolID=?")->execute([$toolID]);
            }
        }
    }

    // Handle pickup / return
    if ($id && $action === 'pickup') {
        $pdo->prepare("UPDATE Reservation SET PickupDate=CURDATE() WHERE ReservationID=?")->execute([$id]);
    }
    if ($id && $action === 'returned') {
        $pdo->prepare("UPDATE Reservation SET ReturnDate=CURDATE(), Status='completed' WHERE ReservationID=?")->execute([$id]);
        $toolStmt = $pdo->prepare("SELECT ToolID FROM Reservation WHERE ReservationID=?");
        $toolStmt->execute([$id]);
        $toolID = $toolStmt->fetchColumn();
        if ($toolID) {
            $pdo->prepare("UPDATE Tool SET CurrentStatus='available' WHERE ToolID=?")->execute([$toolID]);
        }
    }

    header('Location: manage-reservations.php');
    exit;
}

// Filters
$status = $_GET['status'] ?? 'all';
$where  = $status !== 'all' ? "WHERE r.Status = " . $pdo->quote($status) : '';

$reservations = $pdo->query("
    SELECT r.*, m.Fname, m.Lname, m.Email, t.Name AS ToolName
    FROM Reservation r
    JOIN Member m ON m.MemberID = r.MemberID
    JOIN Tool t   ON t.ToolID   = r.ToolID
    $where
    ORDER BY r.ReservationID DESC
")->fetchAll();

require_once '../includes/header.php';
?>

<style>
.section-card { background:#fff; border-radius:12px; border:1px solid #e0e0e0; padding:1.5rem; box-shadow:0 2px 8px rgba(0,0,0,0.04); }
.data-table { width:100%; border-collapse:collapse; font-size:0.88rem; }
.data-table th { text-align:left; padding:0.6rem 0.8rem; background:#f8f9fa; color:#666; font-weight:600; border-bottom:1px solid #e0e0e0; }
.data-table td { padding:0.7rem 0.8rem; border-bottom:1px solid #f0f0f0; color:#444; }
.data-table tr:last-child td { border-bottom:none; }
.data-table tr:hover td { background:#f8f9fa; }
.filter-tabs { display:flex; gap:0.5rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.filter-tab { padding:0.4rem 1rem; border-radius:20px; font-size:0.85rem; border:1px solid #e0e0e0; background:#fff; color:#555; cursor:pointer; text-decoration:none; }
.filter-tab.active { background:#3b5bdb; color:#fff; border-color:#3b5bdb; }
.action-form { display:inline; }
</style>

<div class="container" style="margin-top:2rem; margin-bottom:3rem">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem">
        <h2>📋 Manage Reservations</h2>
        <a href="dashboard.php" class="btn btn-outline">← Back</a>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <?php foreach (['all','pending','approved','completed','cancelled'] as $s): ?>
        <a href="?status=<?= $s ?>" class="filter-tab <?= $status === $s ? 'active' : '' ?>">
            <?= ucfirst($s) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="section-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Member</th>
                    <th>Tool</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Cost</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($reservations): ?>
                <?php foreach ($reservations as $r): ?>
                <tr>
                    <td>#<?= $r['ReservationID'] ?></td>
                    <td>
                        <?= htmlspecialchars($r['Fname'] . ' ' . $r['Lname'], ENT_QUOTES, 'UTF-8') ?>
                        <div style="font-size:0.78rem;color:#aaa"><?= htmlspecialchars($r['Email'], ENT_QUOTES, 'UTF-8') ?></div>
                    </td>
                    <td><?= htmlspecialchars($r['ToolName'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $r['StartDate'] ?></td>
                    <td><?= $r['EndDate'] ?></td>
                    <td>$<?= number_format($r['TotalCost'], 2) ?></td>
                    <td><span class="badge badge-<?= $r['Status'] ?>"><?= ucfirst($r['Status']) ?></span></td>
                    <td style="display:flex; gap:0.4rem; flex-wrap:wrap">
                        <?php if ($r['Status'] === 'pending'): ?>
                            <form class="action-form" method="POST">
                                <input type="hidden" name="reservation_id" value="<?= $r['ReservationID'] ?>">
                                <input type="hidden" name="action" value="approved">
                                <button class="btn btn-success" style="padding:0.25rem 0.7rem;font-size:0.8rem">✅ Approve</button>
                            </form>
                            <form class="action-form" method="POST">
                                <input type="hidden" name="reservation_id" value="<?= $r['ReservationID'] ?>">
                                <input type="hidden" name="action" value="cancelled">
                                <button class="btn btn-danger" style="padding:0.25rem 0.7rem;font-size:0.8rem">❌ Cancel</button>
                            </form>
                        <?php elseif ($r['Status'] === 'approved' && !$r['PickupDate']): ?>
                            <form class="action-form" method="POST">
                                <input type="hidden" name="reservation_id" value="<?= $r['ReservationID'] ?>">
                                <input type="hidden" name="action" value="pickup">
                                <button class="btn btn-primary" style="padding:0.25rem 0.7rem;font-size:0.8rem">📦 Pickup</button>
                            </form>
                        <?php elseif ($r['Status'] === 'approved' && $r['PickupDate'] && !$r['ReturnDate']): ?>
                            <form class="action-form" method="POST">
                                <input type="hidden" name="reservation_id" value="<?= $r['ReservationID'] ?>">
                                <input type="hidden" name="action" value="returned">
                                <button class="btn btn-success" style="padding:0.25rem 0.7rem;font-size:0.8rem">↩️ Return</button>
                            </form>
                        <?php else: ?>
                            <span style="color:#aaa; font-size:0.8rem">—</span>
                        <?php endif; ?>
                        <!-- Librarian view chat -->
                        <?php if (in_array($r['Status'], ['approved','completed'])): ?>
                        <a href="chat.php?reservation_id=<?= $r['ReservationID'] ?>"
                           class="btn btn-outline"
                           style="padding:0.25rem 0.7rem;font-size:0.8rem;color:#3b5bdb;border-color:#3b5bdb">
                           👁️ Chat
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" style="text-align:center;color:#aaa;padding:2rem">No reservations found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>