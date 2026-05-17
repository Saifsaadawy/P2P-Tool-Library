<?php
require_once __DIR__ . '/ISubject.php';
require_once __DIR__ . '/IObserver.php';

class DisputeService implements ISubject
{
    private array $observers = [];
    private \PDO  $pdo;

    public function __construct(\PDO $pdo) { $this->pdo = $pdo; }

    public function attach(IObserver $o): void  { $this->observers[] = $o; }
    public function detach(IObserver $o): void  { $this->observers = array_filter($this->observers, fn($obs) => $obs !== $o); }
    public function notify(string $event, array $data): void { foreach ($this->observers as $o) $o->update($event, $data); }

    public function open(int $reservationId, string $description): array
    {
        $data = ['reservation_id' => $reservationId, 'description' => $description];
        $this->notify('dispute.opened', $data);
        return ['success' => true];
    }

    public function resolve(int $reservationId, string $resolution): array
    {
        $stmt = $this->pdo->prepare("
            SELECT m.Email AS member_email, CONCAT(m.Fname,' ',m.Lname) AS member_name
            FROM Reservation r
            JOIN Member m ON m.MemberID = r.MemberID
            WHERE r.ReservationID = ?
        ");
        $stmt->execute([$reservationId]);
        $member = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

        $data = array_merge($member, ['reservation_id' => $reservationId, 'resolution' => $resolution]);
        $this->notify('dispute.resolved', $data);
        return ['success' => true];
    }
}