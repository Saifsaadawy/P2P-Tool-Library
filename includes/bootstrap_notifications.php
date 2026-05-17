<?php
/**
 * bootstrap_notifications.php
 * ───────────────────────────
 * يربط كل الـ Services بالـ Notifiers.
 *
 * الاستخدام في أي API file:
 *   $services = require __DIR__ . '/../../includes/bootstrap_notifications.php';
 *   $services['reservation']->approve($id, $librarianId, $qrToken);
 *   $services['payment']->processDeposit($reservationId, $amount);
 *   $services['maintenance']->createDamageReport($input);
 *   $services['dispute']->open($reservationId, $description);
 */

// ── Mailer ───────────────────────────────────────────────────────────────────
require_once __DIR__ . '/Mailer.php';

// ── Observer Interfaces ──────────────────────────────────────────────────────
require_once __DIR__ . '/../services/observers/IObserver.php';
require_once __DIR__ . '/../services/observers/ISubject.php';

// ── Services ─────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../services/observers/ReservationService.php';
require_once __DIR__ . '/../services/observers/PaymentService.php';
require_once __DIR__ . '/../services/observers/MaintenanceService.php';
require_once __DIR__ . '/../services/observers/TierUpgradeObserver.php';
require_once __DIR__ . '/../services/observers/DisputeService.php';

// ── Notifiers ────────────────────────────────────────────────────────────────
require __DIR__ . '/../services/notifiers/BorrowerNotifier.php';
require __DIR__ . '/../services/notifiers/LenderNotifier.php';
require __DIR__ . '/../services/notifiers/LibrarianNotifier.php';
require __DIR__ . '/../services/notifiers/TechnicianNotifier.php';

// ── PDO ──────────────────────────────────────────────────────────────────────
$pdo = $GLOBALS['pdo'];

// ── Mailer instance ──────────────────────────────────────────────────────────
$mailer = new Mailer(
    $_ENV['MAIL_FROM'] ?? 'no-reply@toollibrary.com',
    'Tool Library'
);

// ── Notifier instances ───────────────────────────────────────────────────────
$borrowerNotifier = new BorrowerNotifier($mailer);
$lenderNotifier   = new LenderNotifier($mailer);

$librarianNotifier = new LibrarianNotifier(
    $mailer,
    $_ENV['LIBRARIAN_EMAIL'] ?? 'librarian@toollibrary.com'
);

$technicianNotifier = new TechnicianNotifier(
    $mailer,
    $_ENV['TECHNICIAN_EMAIL'] ?? 'maintenance@toollibrary.com'
);

// ── ReservationService ───────────────────────────────────────────────────────
// Events:
//   reservation.created   → LenderNotifier (new request), LibrarianNotifier (needs approval)
//   reservation.approved  → BorrowerNotifier (approved + QR token)
//   reservation.cancelled → BorrowerNotifier, LenderNotifier
//   reservation.returned  → BorrowerNotifier, LenderNotifier, TierUpgradeObserver
//   reservation.checkin   → BorrowerNotifier, LenderNotifier
//   reservation.checkout  → BorrowerNotifier, LenderNotifier
$reservationService = new ReservationService($pdo);
$reservationService->attach($borrowerNotifier);
$reservationService->attach($lenderNotifier);
$reservationService->attach($librarianNotifier);
$reservationService->attach(new TierUpgradeObserver($pdo)); // auto-upgrade member tier on return

// ── PaymentService ───────────────────────────────────────────────────────────
// Events:
//   payment.completed → BorrowerNotifier (payment receipt)
//   payment.penalty   → BorrowerNotifier (penalty notice), LibrarianNotifier (log)
//   payment.refunded  → BorrowerNotifier (refund confirmation)
$paymentService = new PaymentService($pdo);
$paymentService->attach($borrowerNotifier);
$paymentService->attach($librarianNotifier);

// ── MaintenanceService ───────────────────────────────────────────────────────
// Events:
//   damage.reported      → LenderNotifier (tool damaged), LibrarianNotifier (assign staff), TechnicianNotifier (new job)
//   maintenance.completed → TechnicianNotifier (confirmation)
$maintenanceService = new MaintenanceService($pdo);
$maintenanceService->attach($lenderNotifier);
$maintenanceService->attach($librarianNotifier);
$maintenanceService->attach($technicianNotifier);

// ── DisputeService ───────────────────────────────────────────────────────────
// Events:
//   dispute.opened   → LibrarianNotifier (review request)
//   dispute.resolved → BorrowerNotifier (resolution notice)
$disputeService = new DisputeService($pdo);
$disputeService->attach($librarianNotifier);
$disputeService->attach($borrowerNotifier);

// ── Return all wired services ────────────────────────────────────────────────
return [
    'reservation'  => $reservationService,
    'payment'      => $paymentService,
    'maintenance'  => $maintenanceService,
    'dispute'      => $disputeService,
    'mailer'       => $mailer,
];