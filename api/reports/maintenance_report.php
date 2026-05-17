<?php
ob_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

// Librarian or Maintenance Staff
requireRole(['librarian', 'maintenance_staff']);

ob_clean();
$pdo = $GLOBALS['pdo'];
header('Content-Type: application/json');

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT
        mr.RecordID,
        t.Name                              AS tool_name,
        CONCAT(ms.Fname, ' ', ms.Lname)     AS staff_name,
        mr.Date,
        mr.Description,
        mr.Cost,
        dr.Severity                         AS damage_severity,
        dr.Description                      AS damage_description
    FROM MaintenanceRecord mr
    JOIN Tool              t  ON t.ToolID   = mr.ToolID
    JOIN MaintenanceStaff  ms ON ms.StaffID = mr.StaffID
    LEFT JOIN Damage_Maintenance dm ON dm.MaintenanceID = mr.RecordID
    LEFT JOIN DamageReport       dr ON dr.ReportID      = dm.DamageID
    WHERE mr.Date BETWEEN :from AND :to
    ORDER BY mr.Date DESC
");
$stmt->execute([':from' => $from, ':to' => $to]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_cost = array_sum(array_column($rows, 'Cost'));
$total      = count($rows);

echo json_encode([
    'success'    => true,
    'summary'    => compact('total', 'total_cost'),
    'data'       => $rows,
]);