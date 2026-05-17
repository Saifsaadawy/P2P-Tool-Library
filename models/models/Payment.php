<?php
class Payment {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function create(int $reservationId, float $amount, string $status = 'completed'): int {
        $s = $this->pdo->prepare("INSERT INTO Payment (ReservationID,Amount,Status) VALUES (?,?,?)");
        $s->execute([$reservationId,$amount,$status]);
        return (int)$this->pdo->lastInsertId();
    }

    public function getByReservation(int $reservationId): array {
        $s = $this->pdo->prepare("SELECT * FROM Payment WHERE ReservationID=?");
        $s->execute([$reservationId]); return $s->fetchAll();
    }
}
