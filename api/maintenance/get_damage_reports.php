<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/helpers.php';

requireRole('maintenance_staff');

$pdo    = $GLOBALS['pdo'];

$toolId   = (int)($_GET['tool_id']   ?? 0);
$recordId = (int)($_GET['record_id'] ?? 0);

if ($toolId) {
    // Get all damage reports tied to reservations of this tool
    $stmt = $pdo->prepare("
        SELECT dr.ReportID, dr.ReservationID, dr.Description, dr.Severity,
               t.Name AS ToolName
        FROM DamageReport dr
        JOIN Reservation r ON r.ReservationID = dr.ReservationID
        JOIN Tool t ON t.ToolID = r.ToolID
        WHERE r.ToolID = ?
        ORDER BY dr.ReportID DESC
    ");
    $stmt->execute([$toolId]);
} elseif ($recordId) {
    // Get damage reports linked to this maintenance record's tool
    $stmt = $pdo->prepare("
        SELECT dr.ReportID, dr.ReservationID, dr.Description, dr.Severity,
               t.Name AS ToolName
        FROM DamageReport dr
        JOIN Reservation r ON r.ReservationID = dr.ReservationID
        JOIN MaintenanceRecord mr ON mr.ToolID = r.ToolID
        JOIN Tool t ON t.ToolID = r.ToolID
        WHERE mr.RecordID = ?
        ORDER BY dr.ReportID DESC
    ");
    $stmt->execute([$recordId]);
} else {
    // No filter — return ALL damage reports (used by nav button)
    $stmt = $pdo->prepare("
        SELECT dr.ReportID, dr.ReservationID, dr.Description, dr.Severity,
               t.Name AS ToolName
        FROM DamageReport dr
        JOIN Reservation r ON r.ReservationID = dr.ReservationID
        JOIN Tool t ON t.ToolID = r.ToolID
        ORDER BY dr.ReportID DESC
    ");
    $stmt->execute();
}

$reports = $stmt->fetchAll();

// Attach images for each report
foreach ($reports as &$report) {
    $imgs = $pdo->prepare("SELECT ImageURL FROM Damage_URL WHERE DamageID=?");
    $imgs->execute([$report['ReportID']]);
    $report['images'] = array_column($imgs->fetchAll(), 'ImageURL');
}

jsonResponse(true, $reports, 'OK');