<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
$pdo = $GLOBALS['pdo'];

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Tool ID required.']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT t.*, CONCAT(m.Fname,' ',m.Lname) AS owner_name
    FROM Tool t
    JOIN Member m ON m.MemberID = t.MemberID
    WHERE t.ToolID = ?
");
$stmt->execute([$id]);
$tool = $stmt->fetch();

if (!$tool) {
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Tool not found.']);
    exit;
}

$imgs = $pdo->prepare("SELECT MediaURL FROM Tool_URL WHERE ToolID = ?");
$imgs->execute([$id]);
$tool['images'] = $imgs->fetchAll(\PDO::FETCH_COLUMN);

echo json_encode(['success' => true, 'data' => ['tool' => $tool], 'message' => '']);