<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/helpers.php';

requireRole('librarian');
ob_clean();
header('Content-Type: application/json');

$input  = json_decode(file_get_contents('php://input'), true);
$toolId = (int)($input['tool_id'] ?? 0);
if (!$toolId) jsonResponse(false, null, 'tool_id required.', 422);

$pdo->prepare("UPDATE Tool SET CurrentStatus='available', Availability=1 WHERE ToolID=?")
    ->execute([$toolId]);

// Notify tool owner that their listing is approved
$ownerStmt = $pdo->prepare("
    SELECT t.Name AS tool_name, m.Email AS owner_email, CONCAT(m.Fname,' ',m.Lname) AS owner_name
    FROM Tool t JOIN Member m ON m.MemberID = t.MemberID
    WHERE t.ToolID = ?
");
$ownerStmt->execute([$toolId]);
$toolData = $ownerStmt->fetch();

if ($toolData) {
    $services = require __DIR__ . '/../../includes/bootstrap_notifications.php';
    $services['mailer']->send(
        to:      $toolData['owner_email'],
        subject: "✅ Your Tool Listing Has Been Approved — \"{$toolData['tool_name']}\"",
        body:    "Hi {$toolData['owner_name']},\n\n"
               . "Great news! Your tool \"{$toolData['tool_name']}\" has been reviewed and approved by the librarian.\n"
               . "It is now listed and available for members to reserve.\n\n"
               . "Thanks,\nTool Library"
    );
}

jsonResponse(true, null, 'Tool listing approved.');