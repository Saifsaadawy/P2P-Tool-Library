<?php
require_once __DIR__ . '/ISubject.php';
require_once __DIR__ . '/IObserver.php';

class PaymentService implements ISubject
{
    private array $observers = [];
    private \PDO  $pdo;

    public function __construct(\PDO $pdo) { $this->pdo = $pdo; }

    public function attach(IObserver $o): void  { $this->observers[] = $o; }
    public function detach(IObserver $o): void  { $this->observers = array_filter($this->observers, fn($obs) => $obs !== $o); }
    public function notify(string $event, array $data): void { foreach ($this->observers as $o) $o->update($event, $data); }

    public function processDeposit(int $reservationId, float $amount): array
    {
        $stmt = $this->pdo->prepare("INSERT INTO Payment (ReservationID, Amount, Status) VALUES (?, ?, 'completed')");
        $stmt->execute([$reservationId, $amount]);
        $data = array_merge(
            $this->fetchMemberData($reservationId),
            ['reservation_id' => $reservationId, 'amount' => $amount, 'payment_id' => $this->pdo->lastInsertId()]
        );
        $this->notify('payment.completed', $data);
        return ['success' => true];
    }

    public function applyPenalty(int $reservationId, float $amount, string $reason): array
    {
        $stmt = $this->pdo->prepare("INSERT INTO Payment (ReservationID, Amount, Status) VALUES (?, ?, 'penalty')");
        $stmt->execute([$reservationId, $amount]);
        $data = array_merge(
            $this->fetchMemberData($reservationId),
            ['reservation_id' => $reservationId, 'amount' => $amount, 'reason' => $reason]
        );
        $this->notify('payment.penalty', $data);
        return ['success' => true];
    }

    public function refund(int $reservationId, float $amount): array
    {
        $data = array_merge(
            $this->fetchMemberData($reservationId),
            ['reservation_id' => $reservationId, 'amount' => $amount]
        );
        $this->notify('payment.refunded', $data);
        return ['success' => true];
    }

    private function fetchMemberData(int $reservationId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT m.Email AS member_email, CONCAT(m.Fname,' ',m.Lname) AS member_name
            FROM Reservation r
            JOIN Member m ON m.MemberID = r.MemberID
            WHERE r.ReservationID = ?
        ");
        $stmt->execute([$reservationId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
    }
}
