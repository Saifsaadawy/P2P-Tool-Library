<?php
class DamageReport {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function create(int $reservationId, string $description, string $severity): int {
        $s = $this->pdo->prepare("INSERT INTO DamageReport (ReservationID,Description,Severity) VALUES (?,?,?)");
        $s->execute([$reservationId,$description,$severity]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findById(int $id): array|false {
        $s = $this->pdo->prepare("SELECT * FROM DamageReport WHERE ReportID=?");
        $s->execute([$id]); return $s->fetch();
    }
}
