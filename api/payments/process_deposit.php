<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/helpers.php';

requireRole('librarian');
ob_clean();
header('Content-Type: application/json');

$input         = json_decode(file_get_contents('php://input'), true);
$reservationId = (int)($input['reservation_id'] ?? 0);
$amount        = (float)($input['amount']        ?? 0);

if (!$reservationId || $amount <= 0)
    jsonResponse(false, null, 'reservation_id and amount required.', 422);

$services = require __DIR__ . '/../../includes/bootstrap_notifications.php';
$result   = $services['payment']->processDeposit($reservationId, $amount);

jsonResponse(true, null, 'Deposit recorded.');