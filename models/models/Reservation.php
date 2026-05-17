<?php
class Reservation {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function findById(int $id): array|false {
        $s = $this->pdo->prepare("SELECT r.*,CONCAT(m.Fname,' ',m.Lname) AS member_name,t.Name AS tool_name FROM Reservation r JOIN Member m ON m.MemberID=r.MemberID JOIN Tool t ON t.ToolID=r.ToolID WHERE r.ReservationID=?");
        $s->execute([$id]); return $s->fetch();
    }

    public function getByMember(int $memberId): array {
        $s = $this->pdo->prepare("SELECT r.*,t.Name AS tool_name FROM Reservation r JOIN Tool t ON t.ToolID=r.ToolID WHERE r.MemberID=? ORDER BY r.ReservationID DESC");
        $s->execute([$memberId]); return $s->fetchAll();
    }

    public function updateStatus(int $id, string $status): void {
        $this->pdo->prepare("UPDATE Reservation SET Status=? WHERE ReservationID=?")->execute([$status,$id]);
    }

    public function isToolAvailable(int $toolId, string $start, string $end): bool {
        $s = $this->pdo->prepare("SELECT COUNT(*) FROM Reservation WHERE ToolID=? AND Status IN ('approved','pending') AND NOT (EndDate < ? OR StartDate > ?)");
        $s->execute([$toolId,$start,$end]); return $s->fetchColumn() == 0;
    }
}
