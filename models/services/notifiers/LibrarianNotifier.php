<?php
require_once __DIR__ . '/../observers/IObserver.php';
require_once __DIR__ . '/../../includes/Mailer.php';

/**
 * Sends emails to the Librarian for events that need admin attention.
 */
class LibrarianNotifier implements IObserver
{
    private Mailer $mailer;
    private string $librarianEmail;

    public function __construct(Mailer $mailer, string $librarianEmail)
    {
        $this->mailer         = $mailer;
        $this->librarianEmail = $librarianEmail;
    }

    public function update(string $event, array $data): void
    {
        match ($event) {
            'reservation.created' => $this->onNewReservation($data),
            'damage.reported'     => $this->onDamage($data),
            'dispute.opened'      => $this->onDispute($data),
            'payment.penalty'     => $this->onPenalty($data),
            default               => null,
        };
    }

    private function onNewReservation(array $d): void
    {
        $this->mailer->send(
            to:      $this->librarianEmail,
            subject: "📋 New Reservation Needs Approval",
            body:    "A new reservation is pending your approval.\n\n"
                   . "Member: {$d['member_name']}\n"
                   . "Tool: {$d['tool_name']}\n"
                   . "From: {$d['start_date']} — To: {$d['end_date']}\n\n"
                   . "Please log in to approve or reject.\n\nTool Library System"
        );
    }

    private function onDamage(array $d): void
    {
        $this->mailer->send(
            to:      $this->librarianEmail,
            subject: "⚠️ Damage Report Filed — Severity: {$d['severity']}",
            body:    "A damage report has been filed.\n\n"
                   . "Tool: " . ($d['tool_name'] ?? 'N/A') . "\n"
                   . "Severity: {$d['severity']}\n"
                   . "Description: {$d['description']}\n\n"
                   . "Please assign a maintenance staff member.\n\nTool Library System"
        );
    }

    private function onDispute(array $d): void
    {
        $this->mailer->send(
            to:      $this->librarianEmail,
            subject: "🚨 New Dispute Opened — Reservation #{$d['reservation_id']}",
            body:    "A dispute has been opened.\n\n"
                   . "Reservation ID: {$d['reservation_id']}\n"
                   . "Details: {$d['description']}\n\n"
                   . "Please review and resolve.\n\nTool Library System"
        );
    }

    private function onPenalty(array $d): void
    {
        $this->mailer->send(
            to:      $this->librarianEmail,
            subject: "💳 Penalty Applied — \${$d['amount']}",
            body:    "A penalty of \${$d['amount']} was applied.\nReason: {$d['reason']}\nReservation: #{$d['reservation_id']}\n\nTool Library System"
        );
    }
}
