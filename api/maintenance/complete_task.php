<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/helpers.php';

requireRole('maintenance_staff');

$pdo     = $GLOBALS['pdo'];
$staffId = (int)$_SESSION['user_id'];

$recordId = (int)($_POST['record_id'] ?? 0);
$toolId   = (int)($_POST['tool_id']   ?? 0);
$notes    = trim($_POST['notes']      ?? '');

if (!$recordId || !$toolId) {
    jsonResponse(false, null, 'record_id and tool_id are required.', 422);
}

// Verify ownership
$chk = $pdo->prepare("SELECT RecordID FROM MaintenanceRecord WHERE RecordID=? AND StaffID=?");
$chk->execute([$recordId, $staffId]);
if (!$chk->fetch()) {
    jsonResponse(false, null, 'Record not found or access denied.', 403);
}

// Append notes to description if provided
if ($notes) {
    $pdo->prepare("
        UPDATE MaintenanceRecord
        SET Description = CONCAT(COALESCE(Description,''), '\n\n[Completion Notes]: ', ?)
        WHERE RecordID = ?
    ")->execute([$notes, $recordId]);
}

// Handle image uploads
if (!empty($_FILES['images']['name'][0])) {
    $files = $_FILES['images'];
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        $file = [
            'name'     => $files['name'][$i],
            'type'     => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i],
        ];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $url = uploadFile($file, 'maintenance');
            if ($url) {
                $pdo->prepare("INSERT INTO Maintenance_URL (MaintenanceID, ImageURL) VALUES (?,?)")
                    ->execute([$recordId, $url]);
            }
        }
    }
}

// Mark tool as available
$pdo->prepare("UPDATE Tool SET CurrentStatus='available' WHERE ToolID=?")
    ->execute([$toolId]);

// Fire maintenance.completed notification via observer
$services = require __DIR__ . '/../../includes/bootstrap_notifications.php';
$services['maintenance']->completeMaintenanceRecord($recordId);

jsonResponse(true, ['tool_id' => $toolId], 'Maintenance completed. Tool is now available.');