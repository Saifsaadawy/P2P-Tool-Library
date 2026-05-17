<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/helpers.php';

requireRole('librarian');
ob_clean();
$pdo = $GLOBALS['pdo'];
header('Content-Type: application/json');

$input   = json_decode(file_get_contents('php://input'), true);
$toolId  = (int)($input['tool_id']  ?? 0);
$staffId = (int)($input['staff_id'] ?? 0);
$date    = $input['date']        ?? date('Y-m-d');
$desc    = sanitize($input['description'] ?? '');
$cost    = (float)($input['cost'] ?? 0);
$user    = currentUser();

if (!$toolId || !$staffId || !$desc)
    jsonResponse(false, null, 'tool_id, staff_id and description are required.', 422);

// Set tool to maintenance
$pdo->prepare("UPDATE Tool SET CurrentStatus='maintenance' WHERE ToolID=?")->execute([$toolId]);

$services = require __DIR__ . '/../../includes/bootstrap_notifications.php';

$stmt = $pdo->prepare("INSERT INTO MaintenanceRecord (ToolID,StaffID,LibrarianID,Date,Description,Cost) VALUES (?,?,?,?,?,?)");
$stmt->execute([$toolId, $staffId, $user['id'], $date, $desc, $cost]);
$recordId = (int)$pdo->lastInsertId();

$services['maintenance']->completeMaintenanceRecord($recordId);

jsonResponse(true, ['record_id' => $recordId], 'Maintenance record created.');