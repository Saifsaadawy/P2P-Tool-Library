<?php
require_once '../config.php';
require_once '../includes/auth_check.php';
requireRole('librarian');

$pdo = $GLOBALS['pdo'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['member_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id && $action === 'verify') {
        $pdo->prepare("UPDATE Member SET Verified=1 WHERE MemberID=?")->execute([$id]);
    }
    if ($id && $action === 'reject') {
        $pdo->prepare("UPDATE Member SET Verified=0, Status='rejected' WHERE MemberID=?")->execute([$id]);
    }
    if ($id && $action === 'suspend') {
        $pdo->prepare("UPDATE Member SET Status='suspended' WHERE MemberID=?")->execute([$id]);
    }
    if ($id && $action === 'activate') {
        $pdo->prepare("UPDATE Member SET Status='active' WHERE MemberID=?")->execute([$id]);
    }

    header('Location: manage-members.php');
    exit;
}

$filter = $_GET['filter'] ?? 'all';
$where = match($filter) {
    'unverified' => "WHERE Verified = 0 AND Status != 'rejected'",
    'suspended'  => "WHERE Status = 'suspended'",
    'active'     => "WHERE Status = 'active'",
    'rejected'   => "WHERE Status = 'rejected'",
    default      => "WHERE Status != 'rejected'",
};

$members = $pdo->query("
    SELECT *, 
        (SELECT COUNT(*) FROM Reservation WHERE MemberID = m.MemberID) AS totalRes
    FROM Member m
    $where
    ORDER BY CreatedAt DESC
")->fetchAll();

require_once '../includes/header.php';
?>

<style>
.section-card { background:#fff; border-radius:12px; border:1px solid #e0e0e0; padding:1.5rem; box-shadow:0 2px 8px rgba(0,0,0,0.04); }
.data-table { width:100%; border-collapse:collapse; font-size:0.88rem; }
.data-table th { text-align:left; padding:0.6rem 0.8rem; background:#f8f9fa; color:#666; font-weight:600; border-bottom:1px solid #e0e0e0; }
.data-table td { padding:0.7rem 0.8rem; border-bottom:1px solid #f0f0f0; color:#444; vertical-align:middle; }
.data-table tr:last-child td { border-bottom:none; }
.data-table tr:hover td { background:#f8f9fa; }
.filter-tabs { display:flex; gap:0.5rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.filter-tab { padding:0.4rem 1rem; border-radius:20px; font-size:0.85rem; border:1px solid #e0e0e0; background:#fff; color:#555; text-decoration:none; }
.filter-tab.active { background:#3b5bdb; color:#fff; border-color:#3b5bdb; }
.action-form { display:inline; }
</style>

<div class="container" style="margin-top:2rem; margin-bottom:3rem">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem">
        <h2>👥 Manage Members</h2>
        <a href="dashboard.php" class="btn btn-outline">← Back</a>
    </div>

    <div class="filter-tabs">
        <?php foreach (['all' => 'All', 'unverified' => 'Unverified', 'active' => 'Active', 'suspended' => 'Suspended', 'rejected' => 'Rejected'] as $k => $v): ?>
        <a href="?filter=<?= $k ?>" class="filter-tab <?= $filter === $k ? 'active' : '' ?>"><?= $v ?></a>
        <?php endforeach; ?>
    </div>

    <div class="section-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Tier</th>
                    <th>Trust</th>
                    <th>Reservations</th>
                    <th>Verified</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($members): ?>
                <?php foreach ($members as $m): ?>
                <tr>
                    <td>#<?= $m['MemberID'] ?></td>
                    <td><?= htmlspecialchars($m['Fname'] . ' ' . $m['Lname'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size:0.82rem"><?= htmlspecialchars($m['Email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge badge-<?= $m['MembershipTier'] ?>"><?= ucfirst($m['MembershipTier']) ?></span></td>
                    <td><?= (int)$m['TrustScore'] ?></td>
                    <td><?= (int)$m['totalRes'] ?></td>
                    <td>
                        <?php if ($m['Verified']): ?>
                            <span class="badge badge-approved">✅ Verified</span>
                        <?php else: ?>
                            <span class="badge badge-pending">⏳ Pending</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-<?= $m['Status'] ?>"><?= ucfirst($m['Status']) ?></span></td>
                    <td style="display:flex; gap:0.4rem; flex-wrap:wrap">
                        <?php if (!$m['Verified']): ?>
                            <form class="action-form" method="POST">
                                <input type="hidden" name="member_id" value="<?= $m['MemberID'] ?>">
                                <input type="hidden" name="action" value="verify">
                                <button class="btn btn-success" style="padding:0.25rem 0.7rem;font-size:0.8rem">✅ Verify</button>
                            </form>
                            <form class="action-form" method="POST">
                                <input type="hidden" name="member_id" value="<?= $m['MemberID'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <button class="btn btn-danger" style="padding:0.25rem 0.7rem;font-size:0.8rem">❌ Reject</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($m['Status'] === 'active'): ?>
                            <form class="action-form" method="POST">
                                <input type="hidden" name="member_id" value="<?= $m['MemberID'] ?>">
                                <input type="hidden" name="action" value="suspend">
                                <button class="btn btn-danger" style="padding:0.25rem 0.7rem;font-size:0.8rem">🚫 Suspend</button>
                            </form>
                        <?php else: ?>
                            <form class="action-form" method="POST">
                                <input type="hidden" name="member_id" value="<?= $m['MemberID'] ?>">
                                <input type="hidden" name="action" value="activate">
                                <button class="btn btn-success" style="padding:0.25rem 0.7rem;font-size:0.8rem">✅ Activate</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9" style="text-align:center;color:#aaa;padding:2rem">No members found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>