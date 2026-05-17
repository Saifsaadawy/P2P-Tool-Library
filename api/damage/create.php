<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/helpers.php';

requireRole('member');
ob_clean();
$pdo = $GLOBALS['pdo'];
header('Content-Type: application/json');

$user          = currentUser();
$reservationId = (int)($_POST['reservation_id'] ?? 0);
$description   = sanitize($_POST['description']  ?? '');
$severity      = $_POST['severity']              ?? 'low';

if (!$reservationId || !$description)
    jsonResponse(false, null, 'reservation_id and description are required.', 422);

// Verify reservation belongs to member
$stmt = $pdo->prepare("SELECT r.*, t.MemberID AS owner_id, t.Name AS tool_name FROM Reservation r JOIN Tool t ON t.ToolID=r.ToolID WHERE r.ReservationID=? AND r.MemberID=?");
$stmt->execute([$reservationId, $user['id']]);
$res = $stmt->fetch();
if (!$res) jsonResponse(false, null, 'Reservation not found.', 404);

$services = require __DIR__ . '/../../includes/bootstrap_notifications.php';

// Fetch owner email for notifier
$ownerStmt = $pdo->prepare("SELECT Email, CONCAT(Fname,' ',Lname) AS name FROM Member WHERE MemberID=?");
$ownerStmt->execute([$res['owner_id']]);
$owner = $ownerStmt->fetch();

$result = $services['maintenance']->createDamageReport([
    'reservation_id' => $reservationId,
    'description'    => $description,
    'severity'       => $severity,
    'tool_name'      => $res['tool_name'],
    'owner_email'    => $owner['Email'] ?? '',
]);

// Save images
if (!empty($_FILES['images'])) {
    $files = $_FILES['images'];
    $count = is_array($files['name']) ? count($files['name']) : 1;
    for ($i = 0; $i < $count; $i++) {
        $file = [
            'name'     => is_array($files['name'])     ? $files['name'][$i]     : $files['name'],
            'type'     => is_array($files['type'])     ? $files['type'][$i]     : $files['type'],
            'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
            'error'    => is_array($files['error'])    ? $files['error'][$i]    : $files['error'],
        ];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $path = uploadFile($file, 'damage');
            if ($path) {
                $pdo->prepare("INSERT INTO Damage_URL (DamageID, ImageURL) VALUES (?,?)")
                    ->execute([$result['report_id'], $path]);
            }
        }
    }
}

// Lower trust score for damage
$pdo->prepare("UPDATE Member SET TrustScore = GREATEST(0, TrustScore - 5) WHERE MemberID=?")
    ->execute([$user['id']]);

jsonResponse(true, ['report_id' => $result['report_id']], 'Damage report submitted.');