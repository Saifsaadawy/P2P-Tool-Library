<?php
require_once __DIR__ . '/../observers/IObserver.php';
require_once __DIR__ . '/../../includes/Mailer.php';

/**
 * Sends emails to the Member who owns/lent the tool.
 */
class LenderNotifier implements IObserver
{
    private Mailer $mailer;

    public function __construct(Mailer $mailer) { $this->mailer = $mailer; }

    public function update(string $event, array $data): void
    {
        match ($event) {
            'reservation.created'   => $this->onNewRequest($data),
            'reservation.approved'  => $this->onApproved($data),
            'reservation.cancelled' => $this->onCancelled($data),
            'reservation.returned'  => $this->onReturned($data),
            'reservation.checkin'   => $this->onCheckin($data),
            'reservation.checkout'  => $this->onCheckout($data),
            'damage.reported'       => $this->onDamage($data),
            default                 => null,
        };
    }

    private function onNewRequest(array $d): void
    {
        $this->mailer->send(
            to:      $d['owner_email'],
            subject: "📬 New Reservation Request for \"{$d['tool_name']}\"",
            body:    "Hi {$d['owner_name']},\n\n"
                   . "{$d['member_name']} has requested to borrow your tool \"{$d['tool_name']}\".\n"
                   . "From: {$d['start_date']} — To: {$d['end_date']}\n\n"
                   . "The librarian will review and confirm the request shortly.\n\nThanks,\nTool Library"
        );
    }

    private function onCancelled(array $d): void
    {
        $this->mailer->send(
            to:      $d['owner_email'],
            subject: "❌ Reservation Cancelled — \"{$d['tool_name']}\"",
            body:    "Hi {$d['owner_name']},\n\n"
                   . "The reservation for your tool \"{$d['tool_name']}\" has been cancelled.\n\nThanks,\nTool Library"
        );
    }

    private function onReturned(array $d): void
    {
        $this->mailer->send(
            to:      $d['owner_email'],
            subject: "✅ Your Tool Has Been Returned",
            body:    "Hi {$d['owner_name']},\n\n"
                   . "Your tool \"{$d['tool_name']}\" has been returned by {$d['member_name']}.\n"
                   . "Please check its condition and report any damage if needed.\n\nThanks,\nTool Library"
        );
    }

    private function onCheckin(array $d): void
    {
        $this->mailer->send(
            to:      $d['owner_email'],
            subject: "📦 Your Tool Was Picked Up",
            body:    "Hi {$d['owner_name']},\n\n"
                   . "{$d['member_name']} has picked up your tool \"{$d['tool_name']}\".\n"
                   . "Picked up at: {$d['CheckedInAt']}\n"
                   . "Expected return: {$d['EndDate']}\n\nThanks,\nTool Library"
        );
    }

    private function onCheckout(array $d): void
    {
        $this->mailer->send(
            to:      $d['owner_email'],
            subject: "✅ Your Tool Has Been Returned",
            body:    "Hi {$d['owner_name']},\n\n"
                   . "Your tool \"{$d['tool_name']}\" has been returned by {$d['member_name']}.\n"
                   . "Returned at: {$d['CheckedOutAt']}\n"
                   . "Please check its condition and report any damage if needed.\n\nThanks,\nTool Library"
        );
    }

    private function onDamage(array $d): void
    {
        $this->mailer->send(
            to:      $d['owner_email'] ?? '',
            subject: "⚠️ Damage Reported on Your Tool",
            body:    "A damage report has been filed for your tool.\n"
                   . "Severity: {$d['severity']}\nDetails: {$d['description']}\n\n"
                   . "Our maintenance team will be in touch.\n\nThanks,\nTool Library"
        );
    }

    private function onApproved(array $d): void
    {
        $this->mailer->send(
            to:      $d['owner_email'],
            subject: "✅ Your Tool Has Been Approved for Rental — \"{$d['tool_name']}\"",
            body:    "Hi {$d['owner_name']},\n\n"
                   . "Good news! The reservation for your tool \"{$d['tool_name']}\" has been approved by the librarian.\n"
                   . "Borrower: {$d['member_name']}\n"
                   . "From: {$d['start_date']} — To: {$d['end_date']}\n\n"
                   . "The borrower will pick it up on the scheduled date.\n\nThanks,\nTool Library"
        );
    }

}