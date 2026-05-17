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
$toolId = (int)($input['tool_id'] ?? 0);
$user   = currentUser();

// Make sure the tool belongs to this member
$stmt = $pdo->prepare("SELECT MemberID, CurrentStatus FROM Tool WHERE ToolID = ?");
$stmt->execute([$toolId]);
$tool = $stmt->fetch();

if (!$tool) jsonResponse(false, null, 'Tool not found.', 404);
if ($tool['MemberID'] != $user['id']) jsonResponse(false, null, 'Not your tool.', 403);
if ($tool['CurrentStatus'] === 'reserved') jsonResponse(false, null, 'Cannot delete a reserved tool.', 409);

$pdo->prepare("DELETE FROM Tool WHERE ToolID = ?")->execute([$toolId]);
jsonResponse(true, null, 'Tool deleted successfully.');