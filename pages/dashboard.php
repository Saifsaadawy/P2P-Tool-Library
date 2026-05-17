<?php
require_once '../config.php';
require_once '../includes/auth_check.php';
requireRole('librarian');

$pdo = $GLOBALS['pdo'];

// Stats
$stats = [];

$stats['pending_reservations'] = $pdo->query("SELECT COUNT(*) FROM Reservation WHERE Status='pending'")->fetchColumn();
$stats['active_tools']         = $pdo->query("SELECT COUNT(*) FROM Tool WHERE CurrentStatus='available'")->fetchColumn();
$stats['total_members']        = $pdo->query("SELECT COUNT(*) FROM Member")->fetchColumn();
$stats['unverified_members']   = $pdo->query("SELECT COUNT(*) FROM Member WHERE Verified=0")->fetchColumn();
$stats['maintenance_tools']    = $pdo->query("SELECT COUNT(*) FROM Tool WHERE CurrentStatus='maintenance'")->fetchColumn();
$stats['total_revenue']        = $pdo->query("SELECT COALESCE(SUM(Amount),0) FROM Payment WHERE Status='completed'")->fetchColumn();

// Latest pending reservations
$pendingRes = $pdo->query("
    SELECT r.ReservationID, r.StartDate, r.EndDate, r.TotalCost,
           m.Fname, m.Lname, t.Name AS ToolName
    FROM Reservation r
    JOIN Member m ON m.MemberID = r.MemberID
    JOIN Tool t   ON t.ToolID   = r.ToolID
    WHERE r.Status = 'pending'
    ORDER BY r.ReservationID DESC
    LIMIT 5
")->fetchAll();

// Tools expiring safety soon (within 30 days)
$expiringTools = $pdo->query("
    SELECT ToolID, Name, SafetyExpiry
    FROM Tool
    WHERE SafetyExpiry IS NOT NULL
      AND SafetyExpiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
      AND SafetyExpiry >= CURDATE()
    ORDER BY SafetyExpiry ASC
    LIMIT 5
")->fetchAll();

// Unverified members
$unverifiedMembers = $pdo->query("
    SELECT MemberID, Fname, Lname, Email, CreatedAt
    FROM Member
    WHERE Verified = 0
    ORDER BY CreatedAt DESC
    LIMIT 5
")->fetchAll();

require_once '../includes/header.php';
?>

<style>
.dash-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}
.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 1.2rem 1.5rem;
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.stat-label { font-size: 0.82rem; color: #888; margin-bottom: 0.4rem; }
.stat-value { font-size: 1.8rem; font-weight: 700; color: #3b5bdb; }
.stat-value.red   { color: #e03131; }
.stat-value.green { color: #2f9e44; }
.stat-value.orange{ color: #f08c00; }

.section-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.section-card h3 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #333;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.data-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
.data-table th {
    text-align: left;
    padding: 0.6rem 0.8rem;
    background: #f8f9fa;
    color: #666;
    font-weight: 600;
    border-bottom: 1px solid #e0e0e0;
}
.data-table td {
    padding: 0.7rem 0.8rem;
    border-bottom: 1px solid #f0f0f0;
    color: #444;
}
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #f8f9fa; }

.quick-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 2rem;
}
.empty-row { text-align: center; color: #aaa; padding: 1.5rem !important; }
</style>

<div class="container" style="margin-top:2rem; margin-bottom:3rem">
    <h2 style="margin-bottom:0.3rem">Librarian Dashboard 📚</h2>
    <p style="color:#888; margin-bottom:1.5rem">Welcome back, <?= htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') ?></p>

    <!-- Stats -->
    <div class="dash-grid">
        <div class="stat-card">
            <div class="stat-label">Pending Reservations</div>
            <div class="stat-value <?= $stats['pending_reservations'] > 0 ? 'orange' : 'green' ?>">
                <?= (int)$stats['pending_reservations'] ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Available Tools</div>
            <div class="stat-value green"><?= (int)$stats['active_tools'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">In Maintenance</div>
            <div class="stat-value orange"><?= (int)$stats['maintenance_tools'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Members</div>
            <div class="stat-value"><?= (int)$stats['total_members'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Unverified Members</div>
            <div class="stat-value <?= $stats['unverified_members'] > 0 ? 'red' : 'green' ?>">
                <?= (int)$stats['unverified_members'] ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value green" style="font-size:1.4rem">
                $<?= number_format($stats['total_revenue'], 2) ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="manage-reservations.php" class="btn btn-primary">📋 Manage Reservations</a>
        <a href="manage-tools.php"        class="btn btn-outline">🔧 Manage Tools</a>
        <a href="manage-members.php"      class="btn btn-outline">👥 Manage Members</a>
        <a href="maintenance.php"         class="btn btn-outline">🔩 Maintenance</a>
        <a href="reports.php"             class="btn btn-outline">📊 Reports</a>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; flex-wrap:wrap">

        <!-- Pending Reservations -->
        <div class="section-card">
            <h3>⏳ Pending Reservations</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Member</th>
                        <th>Tool</th>
                        <th>Dates</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($pendingRes): ?>
                    <?php foreach ($pendingRes as $r): ?>
                    <tr>
                        <td>#<?= $r['ReservationID'] ?></td>
                        <td><?= htmlspecialchars($r['Fname'] . ' ' . $r['Lname'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($r['ToolName'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="font-size:0.8rem"><?= $r['StartDate'] ?> → <?= $r['EndDate'] ?></td>
                        <td>
                            <a href="manage-reservations.php?id=<?= $r['ReservationID'] ?>" class="btn btn-primary" style="padding:0.25rem 0.7rem;font-size:0.8rem">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="empty-row">No pending reservations 🎉</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Unverified Members -->
        <div class="section-card">
            <h3>🔍 Unverified Members</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Joined</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($unverifiedMembers): ?>
                    <?php foreach ($unverifiedMembers as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['Fname'] . ' ' . $m['Lname'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="font-size:0.8rem"><?= htmlspecialchars($m['Email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="font-size:0.8rem"><?= date('M d', strtotime($m['CreatedAt'])) ?></td>
                        <td>
                            <a href="manage-members.php?id=<?= $m['MemberID'] ?>" class="btn btn-outline" style="padding:0.25rem 0.7rem;font-size:0.8rem">Review</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="empty-row">All members verified ✅</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Expiring Safety Tools -->
        <div class="section-card" style="grid-column: 1 / -1">
            <h3>⚠️ Tools with Expiring Safety Certificate (Next 30 Days)</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tool Name</th>
                        <th>Safety Expiry</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($expiringTools): ?>
                    <?php foreach ($expiringTools as $t): ?>
                    <tr>
                        <td>#<?= $t['ToolID'] ?></td>
                        <td><?= htmlspecialchars($t['Name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="color:#f08c00; font-weight:600"><?= $t['SafetyExpiry'] ?></td>
                        <td>
                            <a href="manage-tools.php?id=<?= $t['ToolID'] ?>" class="btn btn-outline" style="padding:0.25rem 0.7rem;font-size:0.8rem">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="empty-row">No expiring certificates ✅</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>