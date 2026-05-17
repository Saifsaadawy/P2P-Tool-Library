<?php
require_once __DIR__ . '/../observers/IObserver.php';
require_once __DIR__ . '/../../includes/Mailer.php';

/**
 * Sends emails to Maintenance Staff.
 */
class TechnicianNotifier implements IObserver
{
    private Mailer $mailer;
    private string $techEmail;

    public function __construct(Mailer $mailer, string $techEmail)
    {
        $this->mailer    = $mailer;
        $this->techEmail = $techEmail;
    }

    public function update(string $event, array $data): void
    {
        match ($event) {
            'damage.reported'        => $this->onDamage($data),
            'maintenance.completed'  => $this->onCompleted($data),
            default                  => null,
        };
    }

    private function onDamage(array $d): void
    {
        $toolName = $d['tool_name'] ?? 'N/A';
        $this->mailer->send(
            to:      $this->techEmail,
            subject: "🔧 New Damage Report — Action Required",
            body:    "A damage report requires your attention.\n\n"
                   . "Tool: {$toolName}\n"
                   . "Severity: {$d['severity']}\n"
                   . "Description: {$d['description']}\n\n"
                   . "Please log in to schedule maintenance.\n\nTool Library System"
        );
    }

    private function onCompleted(array $d): void
    {
        $this->mailer->send(
            to:      $d['staff_email'] ?? $this->techEmail,
            subject: "✅ Maintenance Record #{$d['RecordID']} Completed",
            body:    "Maintenance for \"{$d['tool_name']}\" has been marked as completed.\n\nTool Library System"
        );
    }
}
