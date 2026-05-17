<?php
require_once '../config.php';
require_once '../includes/auth_check.php';
requireRole('librarian');

$pdo = $GLOBALS['pdo'];

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['tool_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($id && in_array($status, ['available','reserved','maintenance','pending'])) {
        $pdo->prepare("UPDATE Tool SET CurrentStatus=? WHERE ToolID=?")->execute([$status, $id]);
    }
    header('Location: manage-tools.php');
    exit;
}

$filter = $_GET['status'] ?? 'all';
$where  = $filter !== 'all' ? "WHERE t.CurrentStatus = " . $pdo->quote($filter) : '';

$tools = $pdo->query("
    SELECT t.*, m.Fname, m.Lname
    FROM Tool t
    JOIN Member m ON m.MemberID = t.MemberID
    $where
    ORDER BY t.ToolID DESC
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
</style>

<div class="container" style="margin-top:2rem; margin-bottom:3rem">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem">
        <h2>🔧 Manage Tools</h2>
        <a href="dashboard.php" class="btn btn-outline">← Back</a>
    </div>

    <div class="filter-tabs">
        <?php foreach (['all','available','reserved','maintenance','pending'] as $s): ?>
        <a href="?status=<?= $s ?>" class="filter-tab <?= $filter === $s ? 'active' : '' ?>">
            <?= ucfirst($s) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="section-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tool</th>
                    <th>Owner</th>
                    <th>Category</th>
                    <th>Rate/day</th>
                    <th>Condition</th>
                    <th>Safety Expiry</th>
                    <th>Status</th>
                    <th>Change Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($tools): ?>
                <?php foreach ($tools as $t): ?>
                <?php
                    $expirySoon = $t['SafetyExpiry'] && strtotime($t['SafetyExpiry']) <= strtotime('+30 days');
                ?>
                <tr>
                    <td>#<?= $t['ToolID'] ?></td>
                    <td><?= htmlspecialchars($t['Name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($t['Fname'] . ' ' . $t['Lname'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($t['Category'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>$<?= number_format($t['DailyRate'], 2) ?></td>
                    <td><?= ucfirst($t['ToolCondition'] ?? $t['Condition'] ?? '—') ?></td>
                    <td style="<?= $expirySoon ? 'color:#f08c00;font-weight:600' : '' ?>">
                        <?= $t['SafetyExpiry'] ?? '—' ?>
                        <?= $expirySoon ? ' ⚠️' : '' ?>
                    </td>
                    <td><span class="badge badge-<?= $t['CurrentStatus'] ?>"><?= ucfirst($t['CurrentStatus']) ?></span></td>
                    <td>
                        <form method="POST" style="display:flex; gap:0.4rem; align-items:center">
                            <input type="hidden" name="tool_id" value="<?= $t['ToolID'] ?>">
                            <select name="status" class="form-control" style="padding:0.3rem;font-size:0.82rem;width:130px">
                                <?php foreach (['available','reserved','maintenance','pending'] as $s): ?>
                                <option value="<?= $s ?>" <?= $t['CurrentStatus'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary" style="padding:0.3rem 0.7rem;font-size:0.8rem">Save</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9" style="text-align:center;color:#aaa;padding:2rem">No tools found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>