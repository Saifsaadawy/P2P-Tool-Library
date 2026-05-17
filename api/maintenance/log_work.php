<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/helpers.php';

requireRole('maintenance_staff');

$pdo     = $GLOBALS['pdo'];
$staffId = (int)$_SESSION['user_id'];

$recordId  = (int)($_POST['record_id']   ?? 0);
$desc      = trim($_POST['description']  ?? '');
$cost      = (float)($_POST['cost']      ?? 0);
$date      = $_POST['date']              ?? date('Y-m-d');
$damageId  = (int)($_POST['damage_id']   ?? 0);

if (!$recordId || !$desc) {
    jsonResponse(false, null, 'record_id and description are required.', 422);
}

// Verify the record belongs to this staff member
$chk = $pdo->prepare("SELECT RecordID, ToolID FROM MaintenanceRecord WHERE RecordID = ? AND StaffID = ?");
$chk->execute([$recordId, $staffId]);
$record = $chk->fetch();

if (!$record) {
    jsonResponse(false, null, 'Record not found or access denied.', 403);
}

// Append new work log entry to description instead of overwriting
$pdo->prepare("
    UPDATE MaintenanceRecord
    SET Description = CONCAT(
            COALESCE(Description, ''),
            CASE WHEN COALESCE(Description,'') = '' THEN '' ELSE '\n\n---\n' END,
            '[', ?, '] ', ?
        ),
        Cost = Cost + ?,
        Date = ?
    WHERE RecordID = ? AND StaffID = ?
")->execute([$date, $desc, $cost, $date, $recordId, $staffId]);

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

// Link damage report if provided
if ($damageId > 0) {
    // Check it's not already linked
    $exists = $pdo->prepare("SELECT 1 FROM Damage_Maintenance WHERE DamageID=? AND MaintenanceID=?");
    $exists->execute([$damageId, $recordId]);
    if (!$exists->fetch()) {
        $pdo->prepare("INSERT INTO Damage_Maintenance (DamageID, MaintenanceID) VALUES (?,?)")
            ->execute([$damageId, $recordId]);
    }
}

jsonResponse(true, ['record_id' => $recordId], 'Work logged successfully.');