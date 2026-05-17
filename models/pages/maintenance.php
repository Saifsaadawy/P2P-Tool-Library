<?php
require_once '../config.php';
require_once '../includes/auth_check.php';
requireRole('librarian');

$pdo = $GLOBALS['pdo'];

// Handle new maintenance record
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toolID      = (int)($_POST['tool_id']     ?? 0);
    $staffID     = (int)($_POST['staff_id']    ?? 0);
    $date        = $_POST['date']              ?? date('Y-m-d');
    $description = trim($_POST['description'] ?? '');
    $cost        = (float)($_POST['cost']      ?? 0);

    if ($toolID && $staffID) {
        $stmt = $pdo->prepare("
            INSERT INTO MaintenanceRecord (ToolID, StaffID, LibrarianID, Date, Description, Cost)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$toolID, $staffID, $_SESSION['user_id'], $date, $description, $cost]);

        // Set tool status to maintenance
        $pdo->prepare("UPDATE Tool SET CurrentStatus='maintenance' WHERE ToolID=?")->execute([$toolID]);
    }

    header('Location: maintenance.php');
    exit;
}

$records = $pdo->query("
    SELECT mr.*, t.Name AS ToolName,
        s.Fname AS SFname, s.Lname AS SLname
    FROM MaintenanceRecord mr
    JOIN Tool t              ON t.ToolID   = mr.ToolID
    JOIN MaintenanceStaff s  ON s.StaffID  = mr.StaffID
    ORDER BY mr.Date DESC
")->fetchAll();

$tools = $pdo->query("SELECT ToolID, Name FROM Tool ORDER BY Name")->fetchAll();
$staff = $pdo->query("SELECT StaffID, Fname, Lname FROM MaintenanceStaff WHERE Status='active'")->fetchAll();

require_once '../includes/header.php';
?>

<style>
.section-card { background:#fff; border-radius:12px; border:1px solid #e0e0e0; padding:1.5rem; box-shadow:0 2px 8px rgba(0,0,0,0.04); margin-bottom:1.5rem; }
.data-table { width:100%; border-collapse:collapse; font-size:0.88rem; }
.data-table th { text-align:left; padding:0.6rem 0.8rem; background:#f8f9fa; color:#666; font-weight:600; border-bottom:1px solid #e0e0e0; }
.data-table td { padding:0.7rem 0.8rem; border-bottom:1px solid #f0f0f0; color:#444; }
.data-table tr:last-child td { border-bottom:none; }
.data-table tr:hover td { background:#f8f9fa; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
</style>

<div class="container" style="margin-top:2rem; margin-bottom:3rem">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem">
        <h2>🔩 Maintenance</h2>
        <a href="dashboard.php" class="btn btn-outline">← Back</a>
    </div>

    <!-- New Maintenance Form -->
    <div class="section-card">
        <h3 style="margin-bottom:1rem; font-size:1rem; font-weight:600">➕ Assign New Maintenance</h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Tool</label>
                    <select name="tool_id" class="form-control" required>
                        <option value="">— Select Tool —</option>
                        <?php foreach ($tools as $t): ?>
                        <option value="<?= $t['ToolID'] ?>"><?= htmlspecialchars($t['Name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Maintenance Staff</label>
                    <select name="staff_id" class="form-control" required>
                        <option value="">— Select Staff —</option>
                        <?php foreach ($staff as $s): ?>
                        <option value="<?= $s['StaffID'] ?>"><?= htmlspecialchars($s['Fname'] . ' ' . $s['Lname'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Cost ($)</label>
                    <input type="number" name="cost" class="form-control" min="0" step="0.01" value="0">
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Describe the maintenance work..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Assign Maintenance</button>
        </form>
    </div>

    <!-- Records Table -->
    <div class="section-card">
        <h3 style="margin-bottom:1rem; font-size:1rem; font-weight:600">📋 Maintenance Records</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tool</th>
                    <th>Staff</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Cost</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($records): ?>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td>#<?= $r['RecordID'] ?></td>
                    <td><?= htmlspecialchars($r['ToolName'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($r['SFname'] . ' ' . $r['SLname'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $r['Date'] ?></td>
                    <td><?= htmlspecialchars($r['Description'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>$<?= number_format($r['Cost'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;color:#aaa;padding:2rem">No maintenance records yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>