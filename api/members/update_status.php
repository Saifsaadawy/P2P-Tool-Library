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

$input    = json_decode(file_get_contents('php://input'), true);
$memberId = (int)($input['member_id'] ?? 0);
$status   = $input['status'] ?? '';

if (!in_array($status, ['active', 'suspended'])) {
    jsonResponse(false, null, 'Invalid status.', 422);
}

$pdo->prepare("UPDATE Member SET Status = ? WHERE MemberID = ?")
    ->execute([$status, $memberId]);

// Notify member of status change
$memberStmt = $pdo->prepare("SELECT Email, CONCAT(Fname,' ',Lname) AS name FROM Member WHERE MemberID = ?");
$memberStmt->execute([$memberId]);
$member = $memberStmt->fetch();

if ($member) {
    $services = require __DIR__ . '/../../includes/bootstrap_notifications.php';
    if ($status === 'suspended') {
        $services['mailer']->send(
            to:      $member['Email'],
            subject: "⚠️ Your Account Has Been Suspended",
            body:    "Hi {$member['name']},\n\n"
                   . "Your account has been suspended by the library administrator.\n"
                   . "If you believe this is a mistake, please contact the library.\n\n"
                   . "Thanks,\nTool Library"
        );
    } else {
        $services['mailer']->send(
            to:      $member['Email'],
            subject: "✅ Your Account Has Been Reactivated",
            body:    "Hi {$member['name']},\n\n"
                   . "Your account has been reactivated. You can now log in and make reservations.\n\n"
                   . "Thanks,\nTool Library"
        );
    }
}

jsonResponse(true, null, "Member status updated to $status.");