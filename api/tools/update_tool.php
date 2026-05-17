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

$input  = json_decode(file_get_contents('php://input'), true);
$user   = currentUser();
$toolId = (int)($input['tool_id'] ?? 0);

$stmt = $pdo->prepare("SELECT MemberID FROM Tool WHERE ToolID=?");
$stmt->execute([$toolId]);
$tool = $stmt->fetch();

if (!$tool) jsonResponse(false, null, 'Tool not found.', 404);
if ($tool['MemberID'] != $user['id']) jsonResponse(false, null, 'Not your tool.', 403);

$pdo->prepare("UPDATE Tool SET Name=?,Description=?,Category=?,DailyRate=?,`Condition`=?,SecurityDeposit=?,SafetyExpiry=? WHERE ToolID=?")
    ->execute([
        sanitize($input['name']             ?? ''),
        sanitize($input['description']      ?? ''),
        sanitize($input['category']         ?? ''),
        (float)($input['daily_rate']        ?? 0),
        $input['condition']                 ?? 'good',
        (float)($input['security_deposit']  ?? 0),
        $input['safety_expiry']             ?? null,
        $toolId
    ]);

jsonResponse(true, null, 'Tool updated successfully.');