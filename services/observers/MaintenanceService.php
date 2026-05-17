<?php
require_once __DIR__ . '/ISubject.php';
require_once __DIR__ . '/IObserver.php';

class MaintenanceService implements ISubject
{
    private array $observers = [];
    private \PDO  $pdo;

    public function __construct(\PDO $pdo) { $this->pdo = $pdo; }

    public function attach(IObserver $o): void  { $this->observers[] = $o; }
    public function detach(IObserver $o): void  { $this->observers = array_filter($this->observers, fn($obs) => $obs !== $o); }
    public function notify(string $event, array $data): void { foreach ($this->observers as $o) $o->update($event, $data); }

    public function createDamageReport(array $input): array
    {
        $stmt = $this->pdo->prepare("INSERT INTO DamageReport (ReservationID, Description, Severity) VALUES (?, ?, ?)");
        $stmt->execute([$input['reservation_id'], $input['description'], $input['severity']]);
        $data = array_merge($input, ['report_id' => $this->pdo->lastInsertId()]);
        $this->notify('damage.reported', $data);
        return ['success' => true, 'report_id' => $data['report_id']];
    }

    public function completeMaintenanceRecord(int $recordId): array
    {
        $stmt = $this->pdo->prepare("SELECT mr.*, t.Name AS tool_name, ms.Email AS staff_email FROM MaintenanceRecord mr JOIN Tool t ON t.ToolID=mr.ToolID JOIN MaintenanceStaff ms ON ms.StaffID=mr.StaffID WHERE mr.RecordID=?");
        $stmt->execute([$recordId]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->notify('maintenance.completed', $data);
        return ['success' => true];
    }
}
