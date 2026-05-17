<?php
ob_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

requireRole(['librarian', 'maintenance_staff']);

ob_clean();
$pdo = $GLOBALS['pdo'];
header('Content-Type: application/json');

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT
        dr.ReportID,
        t.Name                          AS tool_name,
        dr.Severity,
        dr.Description,
        du.ImageURL,
        mr.RecordID                     AS maintenance_id,
        mr.Cost                         AS maintenance_cost
    FROM DamageReport        dr
    JOIN Reservation         res ON res.ReservationID = dr.ReservationID
    JOIN Tool                t   ON t.ToolID          = res.ToolID
    LEFT JOIN Damage_URL     du  ON du.DamageID       = dr.ReportID
    LEFT JOIN Damage_Maintenance dm ON dm.DamageID    = dr.ReportID
    LEFT JOIN MaintenanceRecord  mr ON mr.RecordID    = dm.MaintenanceID
    WHERE res.EndDate BETWEEN :from AND :to
    ORDER BY dr.Severity DESC
");
$stmt->execute([':from' => $from, ':to' => $to]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$high   = count(array_filter($rows, fn($r) => $r['Severity'] === 'high'));
$medium = count(array_filter($rows, fn($r) => $r['Severity'] === 'medium'));
$low    = count(array_filter($rows, fn($r) => $r['Severity'] === 'low'));

echo json_encode([
    'success' => true,
    'summary' => ['total' => count($rows), 'high' => $high, 'medium' => $medium, 'low' => $low],
    'data'    => $rows,
]);