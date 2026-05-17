<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
$pdo = $GLOBALS['pdo'];

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Not authenticated.']);
    exit;
}

$user  = ['id' => $_SESSION['user_id']];
$input = json_decode(file_get_contents('php://input'), true);
$amount = (float)($input['amount'] ?? 0);

if ($amount <= 0) {
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Please enter a valid amount greater than 0.']);
    exit;
}

if ($amount > 10000) {
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Maximum top-up amount is $10,000.']);
    exit;
}

$stmt = $pdo->prepare("UPDATE Member SET Balance = Balance + ? WHERE MemberID = ?");
$stmt->execute([$amount, $user['id']]);

$stmt = $pdo->prepare("SELECT Balance FROM Member WHERE MemberID = ?");
$stmt->execute([$user['id']]);
$row = $stmt->fetch();

echo json_encode(['success' => true, 'data' => ['new_balance' => (float)$row['Balance']], 'message' => 'Balance added successfully.']);