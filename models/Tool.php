<?php
class Tool {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function findById(int $id): array|false {
        $s = $this->pdo->prepare("SELECT t.*, CONCAT(m.Fname,' ',m.Lname) AS owner_name FROM Tool t JOIN Member m ON m.MemberID=t.MemberID WHERE t.ToolID=?");
        $s->execute([$id]); return $s->fetch();
    }

    public function getAll(string $status = ''): array {
        $sql = "SELECT t.*, CONCAT(m.Fname,' ',m.Lname) AS owner_name FROM Tool t JOIN Member m ON m.MemberID=t.MemberID";
        $params = [];
        if ($status) { $sql .= " WHERE t.CurrentStatus=?"; $params[] = $status; }
        $s = $this->pdo->prepare($sql); $s->execute($params); return $s->fetchAll();
    }

    public function create(array $data): int {
        $s = $this->pdo->prepare("INSERT INTO Tool (MemberID,Name,Description,Category,DailyRate,Condition,SecurityDeposit,SafetyExpiry) VALUES (?,?,?,?,?,?,?,?)");
        $s->execute([$data['member_id'],$data['name'],$data['description'],$data['category'],$data['daily_rate'],$data['condition']??'good',$data['security_deposit']??0,$data['safety_expiry']??null]);
        return (int)$this->pdo->lastInsertId();
    }

    public function setStatus(int $id, string $status): void {
        $this->pdo->prepare("UPDATE Tool SET CurrentStatus=? WHERE ToolID=?")->execute([$status,$id]);
    }
}
