<?php
class MaintenanceRecord {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function create(array $data): int {
        $s = $this->pdo->prepare("INSERT INTO MaintenanceRecord (ToolID,StaffID,LibrarianID,Date,Description,Cost) VALUES (?,?,?,?,?,?)");
        $s->execute([$data['tool_id'],$data['staff_id'],$data['librarian_id']??null,$data['date'],$data['description'],$data['cost']??0]);
        return (int)$this->pdo->lastInsertId();
    }

    public function complete(int $id): void {
        $this->pdo->prepare("UPDATE Tool t JOIN MaintenanceRecord mr ON mr.ToolID=t.ToolID SET t.CurrentStatus='available' WHERE mr.RecordID=?")->execute([$id]);
    }
}
