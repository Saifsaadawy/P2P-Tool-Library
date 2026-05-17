<?php
require_once __DIR__ . '/../observers/IObserver.php';
require_once __DIR__ . '/../../includes/Mailer.php';

/**
 * Sends emails to the Member who borrowed a tool.
 */
class BorrowerNotifier implements IObserver
{
    private Mailer $mailer;

    public function __construct(Mailer $mailer) { $this->mailer = $mailer; }

    public function update(string $event, array $data): void
    {
        match ($event) {
            'reservation.approved'  => $this->onApproved($data),
            'reservation.cancelled' => $this->onCancelled($data),
            'reservation.returned'  => $this->onReturned($data),
            'reservation.checkin'   => $this->onCheckin($data),
            'reservation.checkout'  => $this->onCheckout($data),
            'payment.completed'     => $this->onPayment($data),
            'payment.penalty'       => $this->onPenalty($data),
            'payment.refunded'      => $this->onRefund($data),
            'dispute.resolved'      => $this->onDisputeResolved($data),
            default                 => null,
        };
    }

    private function onApproved(array $d): void
    {
        $qrLine = '';
        if (!empty($d['qr_token'])) {
            $baseUrl = $_ENV['APP_URL'] ?? (defined('BASE_URL') ? BASE_URL : '');
            $qrLine  = "\n🔑 Your QR Code Token: {$d['qr_token']}"
                     . "\nPresent this to the librarian on pickup & return.\n";
        }

        $this->mailer->send(
            to:      $d['member_email'],
            subject: "✅ Reservation #{$d['ReservationID']} Approved",
            body:    "Hi {$d['member_name']},\n\n"
                   . "Your reservation for \"{$d['tool_name']}\" has been approved.\n"
                   . "Pickup date: {$d['PickupDate']}\n"
                   . "Return date: {$d['EndDate']}\n"
                   . $qrLine
                   . "\nPlease arrive on time to collect the tool.\n\nThanks,\nTool Library"
        );
    }

    private function onCancelled(array $d): void
    {
        $reason = $d['reason'] ? "\nReason: {$d['reason']}" : '';
        $this->mailer->send(
            to:      $d['member_email'],
            subject: "❌ Reservation #{$d['ReservationID']} Cancelled",
            body:    "Hi {$d['member_name']},\n\n"
                   . "Your reservation for \"{$d['tool_name']}\" has been cancelled.{$reason}\n\n"
                   . "If you have questions, please contact the library.\n\nThanks,\nTool Library"
        );
    }

    private function onReturned(array $d): void
    {
        $this->mailer->send(
            to:      $d['member_email'],
            subject: "🔄 Tool Returned — Thank You!",
            body:    "Hi {$d['member_name']},\n\n"
                   . "We've confirmed the return of \"{$d['tool_name']}\".\n"
                   . "Thank you for using Tool Library!\n\nThanks,\nTool Library"
        );
    }

    private function onCheckin(array $d): void
    {
        $this->mailer->send(
            to:      $d['member_email'],
            subject: "📦 Tool Picked Up — \"{$d['tool_name']}\"",
            body:    "Hi {$d['member_name']},\n\n"
                   . "You've successfully checked in \"{$d['tool_name']}\".\n"
                   . "Checked in at: {$d['CheckedInAt']}\n"
                   . "Please return it by: {$d['EndDate']}\n\n"
                   . "Thanks,\nTool Library"
        );
    }

    private function onCheckout(array $d): void
    {
        $this->mailer->send(
            to:      $d['member_email'],
            subject: "🔄 Tool Returned — Thank You!",
            body:    "Hi {$d['member_name']},\n\n"
                   . "You've successfully returned \"{$d['tool_name']}\".\n"
                   . "Returned at: {$d['CheckedOutAt']}\n\n"
                   . "Thank you for using Tool Library!\n\nThanks,\nTool Library"
        );
    }

    private function onPayment(array $d): void
    {
        $to = $d['member_email'] ?? '';
        if (!$to) return;
        $this->mailer->send(
            to:      $to,
            subject: "💳 Payment Confirmed — \${$d['amount']}",
            body:    "Your payment of \${$d['amount']} for reservation #{$d['reservation_id']} has been received.\n\nThanks,\nTool Library"
        );
    }

    private function onPenalty(array $d): void
    {
        $this->mailer->send(
            to:      $d['member_email'] ?? '',
            subject: "⚠️ Penalty Applied — \${$d['amount']}",
            body:    "A penalty of \${$d['amount']} has been applied to your account.\nReason: {$d['reason']}\n\nPlease contact us if you have questions.\n\nThanks,\nTool Library"
        );
    }

    private function onRefund(array $d): void
    {
        $this->mailer->send(
            to:      $d['member_email'] ?? '',
            subject: "💰 Refund Issued — \${$d['amount']}",
            body:    "A refund of \${$d['amount']} for reservation #{$d['reservation_id']} has been issued.\n\nThanks,\nTool Library"
        );
    }

    private function onDisputeResolved(array $d): void
    {
        $this->mailer->send(
            to:      $d['member_email'] ?? '',
            subject: "✅ Dispute Resolved",
            body:    "Your dispute for reservation #{$d['reservation_id']} has been resolved.\nResolution: {$d['resolution']}\n\nThanks,\nTool Library"
        );
    }
}
