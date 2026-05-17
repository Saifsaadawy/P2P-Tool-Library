<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');

$pdo = $GLOBALS['pdo'];

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Not authenticated.']);
    exit;
}

$input     = json_decode(file_get_contents('php://input'), true);
$memberId  = (int)$_SESSION['user_id'];
$memberName = $_SESSION['name'] ?? '';

$toolId    = (int)($input['tool_id']    ?? 0);
$startDate = $input['start_date']       ?? '';
$endDate   = $input['end_date']         ?? '';
$pickup    = $input['pickup_date']      ?? $startDate;

if (!$toolId || !$startDate || !$endDate) {
    echo json_encode(['success' => false, 'data' => null, 'message' => 'tool_id, start_date and end_date are required.']);
    exit;
}

if ($startDate >= $endDate) {
    echo json_encode(['success' => false, 'data' => null, 'message' => 'End date must be after start date.']);
    exit;
}

// Proxy checks
require_once '../../services/proxies/ReservationProxy.php';
$proxy = new ReservationProxy($pdo);
[$ok, $msg] = $proxy->canReserve($memberId, $toolId);
if (!$ok) {
    echo json_encode(['success' => false, 'data' => null, 'message' => $msg]);
    exit;
}

// Check date overlap
$overlap = $pdo->prepare("SELECT COUNT(*) FROM Reservation WHERE ToolID=? AND Status IN ('approved','pending') AND NOT (EndDate < ? OR StartDate > ?)");
$overlap->execute([$toolId, $startDate, $endDate]);
if ($overlap->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Tool is not available for the selected dates.']);
    exit;
}

// Fetch tool
$toolStmt = $pdo->prepare("SELECT * FROM Tool WHERE ToolID=?");
$toolStmt->execute([$toolId]);
$tool = $toolStmt->fetch();

if (!$tool) {
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Tool not found.']);
    exit;
}

// Fetch member
$memStmt = $pdo->prepare("SELECT MembershipTier, TrustScore FROM Member WHERE MemberID=?");
$memStmt->execute([$memberId]);
$member = $memStmt->fetch();

// Pricing
require_once '../../services/pricing/PricingContext.php';
$days = (int)((strtotime($endDate) - strtotime($startDate)) / 86400);
$cost = PricingContext::withTier($member['MembershipTier'])->calculate($tool['DailyRate'], $days);

// Payment proxy
require_once '../../services/proxies/PaymentProxy.php';
$payProxy = new PaymentProxy($pdo);
$total    = $cost + $tool['SecurityDeposit'];
[$canPay, $payMsg] = $payProxy->canPay($memberId, $total);
if (!$canPay) {
    echo json_encode(['success' => false, 'data' => null, 'message' => $payMsg]);
    exit;
}

// Fetch tool owner info for notifications
$ownerStmt = $pdo->prepare("SELECT m.Email AS owner_email, CONCAT(m.Fname,' ',m.Lname) AS owner_name FROM Tool t JOIN Member m ON m.MemberID = t.MemberID WHERE t.ToolID = ?");
$ownerStmt->execute([$toolId]);
$ownerInfo = $ownerStmt->fetch() ?: ['owner_email' => '', 'owner_name' => ''];

// Create reservation via observer service
$services = require __DIR__ . '/../../includes/bootstrap_notifications.php';
$result   = $services['reservation']->create([
    'member_id'   => $memberId,
    'tool_id'     => $toolId,
    'start_date'  => $startDate,
    'end_date'    => $endDate,
    'pickup_date' => $pickup,
    'total_cost'  => $cost,
    'member_name' => $memberName,
    'tool_name'   => $tool['Name'],
    'owner_email' => $ownerInfo['owner_email'],
    'owner_name'  => $ownerInfo['owner_name'],
]);

// Deduct balance & record payment
$payProxy->deduct($memberId, $total);
$pdo->prepare("INSERT INTO Payment (ReservationID, Amount, Status) VALUES (?,?,'completed')")
    ->execute([$result['reservation_id'], $total]);

echo json_encode(['success' => true, 'data' => ['reservation_id' => $result['reservation_id']], 'message' => 'Reservation created successfully.']);