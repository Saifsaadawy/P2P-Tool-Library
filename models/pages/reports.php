<?php
require_once '../config.php';
require_once '../includes/auth_check.php';
requireRole('librarian');

$pdo = $GLOBALS['pdo'];

// ── Stats ────────────────────────────────────────────
$totalRevenue    = $pdo->query("SELECT COALESCE(SUM(Amount),0) FROM Payment WHERE Status='completed'")->fetchColumn();
$totalRes        = $pdo->query("SELECT COUNT(*) FROM Reservation")->fetchColumn();
$completedRes    = $pdo->query("SELECT COUNT(*) FROM Reservation WHERE Status='completed'")->fetchColumn();
$cancelledRes    = $pdo->query("SELECT COUNT(*) FROM Reservation WHERE Status='cancelled'")->fetchColumn();
$totalMembers    = $pdo->query("SELECT COUNT(*) FROM Member WHERE Status != 'rejected'")->fetchColumn();
$verifiedMembers = $pdo->query("SELECT COUNT(*) FROM Member WHERE Verified=1")->fetchColumn();
$rejectedMembers = $pdo->query("SELECT COUNT(*) FROM Member WHERE Status='rejected'")->fetchColumn();
$totalTools      = $pdo->query("SELECT COUNT(*) FROM Tool")->fetchColumn();
$totalMaint      = $pdo->query("SELECT COUNT(*) FROM MaintenanceRecord")->fetchColumn();
$maintCost       = $pdo->query("SELECT COALESCE(SUM(Cost),0) FROM MaintenanceRecord")->fetchColumn();

// ── Top 5 most reserved tools ────────────────────────
$topTools = $pdo->query("
    SELECT t.Name, COUNT(r.ReservationID) AS total
    FROM Reservation r
    JOIN Tool t ON t.ToolID = r.ToolID
    GROUP BY r.ToolID
    ORDER BY total DESC
    LIMIT 5
")->fetchAll();

// ── Revenue per month (last 6 months) ───────────────
$monthlyRevenue = $pdo->query("
    SELECT DATE_FORMAT(CreatedAt, '%Y-%m') AS month,
        COALESCE(SUM(Amount), 0)        AS revenue
    FROM Payment
    WHERE Status='completed'
    AND CreatedAt >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
")->fetchAll();

// ── Reservations per month (last 6 months) ──────────
$monthlyRes = $pdo->query("
    SELECT DATE_FORMAT(r.StartDate, '%Y-%m') AS month,
        COUNT(*)                           AS total
    FROM Reservation r
    WHERE r.StartDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
")->fetchAll();

// ── Top members by reservations ──────────────────────
$topMembers = $pdo->query("
    SELECT m.Fname, m.Lname, m.MembershipTier,
        COUNT(r.ReservationID) AS total,
        COALESCE(SUM(p.Amount),0) AS spent
    FROM Member m
    LEFT JOIN Reservation r ON r.MemberID = m.MemberID
    LEFT JOIN Payment p     ON p.ReservationID = r.ReservationID AND p.Status='completed'
    GROUP BY m.MemberID
    ORDER BY total DESC
    LIMIT 5
")->fetchAll();

// ── Damage reports summary ───────────────────────────
$damageStats = $pdo->query("
    SELECT Severity, COUNT(*) AS total
    FROM DamageReport
    GROUP BY Severity
")->fetchAll();

require_once '../includes/header.php';
?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
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
.stat-value { font-size: 1.7rem; font-weight: 700; color: #3b5bdb; }
.stat-value.green  { color: #2f9e44; }
.stat-value.orange { color: #f08c00; }
.stat-value.red    { color: #e03131; }

.section-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.section-card h3 { font-size: 1rem; font-weight: 600; margin-bottom: 1rem; color: #333; }

.data-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
.data-table th { text-align:left; padding:0.6rem 0.8rem; background:#f8f9fa; color:#666; font-weight:600; border-bottom:1px solid #e0e0e0; }
.data-table td { padding:0.7rem 0.8rem; border-bottom:1px solid #f0f0f0; color:#444; }
.data-table tr:last-child td { border-bottom:none; }
.data-table tr:hover td { background:#f8f9fa; }

/* Bar chart */
.bar-chart { display:flex; flex-direction:column; gap:0.6rem; }
.bar-row { display:flex; align-items:center; gap:0.8rem; font-size:0.85rem; }
.bar-label { width: 120px; color:#555; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; flex-shrink:0; }
.bar-track { flex:1; background:#f0f0f0; border-radius:20px; height:10px; overflow:hidden; }
.bar-fill  { height:100%; border-radius:20px; background:#3b5bdb; transition:width 0.4s; }
.bar-fill.green  { background:#2f9e44; }
.bar-fill.orange { background:#f08c00; }
.bar-count { width:36px; text-align:right; color:#888; font-size:0.82rem; }

.two-col { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; }
@media(max-width:700px){ .two-col { grid-template-columns:1fr; } }
</style>

<div class="container" style="margin-top:2rem; margin-bottom:3rem">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem">
        <h2>📊 Reports</h2>
        <a href="dashboard.php" class="btn btn-outline">← Back</a>
    </div>

    <!-- Overview Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value green" style="font-size:1.4rem">$<?= number_format($totalRevenue, 2) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Reservations</div>
            <div class="stat-value"><?= (int)$totalRes ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Completed</div>
            <div class="stat-value green"><?= (int)$completedRes ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Cancelled</div>
            <div class="stat-value red"><?= (int)$cancelledRes ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Members</div>
            <div class="stat-value"><?= (int)$totalMembers ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Verified Members</div>
            <div class="stat-value green"><?= (int)$verifiedMembers ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Rejected Members</div>
            <div class="stat-value red"><?= (int)$rejectedMembers ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Tools</div>
            <div class="stat-value"><?= (int)$totalTools ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Maintenance Cost</div>
            <div class="stat-value orange" style="font-size:1.4rem">$<?= number_format($maintCost, 2) ?></div>
        </div>
    </div>

    <div class="two-col">

        <!-- Top Tools -->
        <div class="section-card">
            <h3>🔧 Most Reserved Tools</h3>
            <?php if ($topTools):
                $max = max(array_column($topTools, 'total')) ?: 1; ?>
            <div class="bar-chart">
                <?php foreach ($topTools as $t): ?>
                <div class="bar-row">
                    <div class="bar-label" title="<?= htmlspecialchars($t['Name'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($t['Name'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width:<?= round(($t['total']/$max)*100) ?>%"></div>
                    </div>
                    <div class="bar-count"><?= $t['total'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <p style="color:#aaa; text-align:center; padding:1rem">No data yet.</p>
            <?php endif; ?>
        </div>

        <!-- Top Members -->
        <div class="section-card">
            <h3>👥 Top Members by Reservations</h3>
            <?php if ($topMembers):
                $maxM = max(array_column($topMembers, 'total')) ?: 1; ?>
            <div class="bar-chart">
                <?php foreach ($topMembers as $m): ?>
                <div class="bar-row">
                    <div class="bar-label" title="<?= htmlspecialchars($m['Fname'].' '.$m['Lname'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($m['Fname'].' '.$m['Lname'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill green" style="width:<?= round(($m['total']/$maxM)*100) ?>%"></div>
                    </div>
                    <div class="bar-count"><?= $m['total'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <p style="color:#aaa; text-align:center; padding:1rem">No data yet.</p>
            <?php endif; ?>
        </div>

    </div>

    <div class="two-col">

        <!-- Monthly Revenue -->
        <div class="section-card">
            <h3>💰 Monthly Revenue (Last 6 Months)</h3>
            <table class="data-table">
                <thead><tr><th>Month</th><th>Revenue</th></tr></thead>
                <tbody>
                <?php if ($monthlyRevenue): ?>
                    <?php foreach ($monthlyRevenue as $row): ?>
                    <tr>
                        <td><?= $row['month'] ?></td>
                        <td style="color:#2f9e44; font-weight:600">$<?= number_format($row['revenue'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="2" style="text-align:center;color:#aaa;padding:1rem">No data yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Monthly Reservations -->
        <div class="section-card">
            <h3>📋 Monthly Reservations (Last 6 Months)</h3>
            <table class="data-table">
                <thead><tr><th>Month</th><th>Reservations</th></tr></thead>
                <tbody>
                <?php if ($monthlyRes): ?>
                    <?php foreach ($monthlyRes as $row): ?>
                    <tr>
                        <td><?= $row['month'] ?></td>
                        <td style="font-weight:600"><?= (int)$row['total'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="2" style="text-align:center;color:#aaa;padding:1rem">No data yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Damage Reports Summary -->
    <div class="section-card">
        <h3>⚠️ Damage Reports by Severity</h3>
        <?php if ($damageStats):
            $maxD = max(array_column($damageStats, 'total')) ?: 1;
            $colors = ['low' => '', 'medium' => 'orange', 'high' => 'red']; ?>
        <div class="bar-chart">
            <?php foreach ($damageStats as $d): ?>
            <div class="bar-row">
                <div class="bar-label"><?= ucfirst($d['Severity']) ?></div>
                <div class="bar-track">
                    <div class="bar-fill <?= $colors[$d['Severity']] ?? '' ?>"
                        style="width:<?= round(($d['total']/$maxD)*100) ?>%"></div>
                </div>
                <div class="bar-count"><?= $d['total'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p style="color:#aaa; text-align:center; padding:1rem">No damage reports yet.</p>
        <?php endif; ?>
    </div>

    <!-- Top Members Table -->
    <div class="section-card">
        <h3>🏆 Top Members Detail</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Tier</th>
                    <th>Reservations</th>
                    <th>Total Spent</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($topMembers): ?>
                <?php foreach ($topMembers as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['Fname'].' '.$m['Lname'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge badge-<?= $m['MembershipTier'] ?>"><?= ucfirst($m['MembershipTier']) ?></span></td>
                    <td><?= (int)$m['total'] ?></td>
                    <td style="color:#2f9e44; font-weight:600">$<?= number_format($m['spent'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center;color:#aaa;padding:1rem">No data yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>