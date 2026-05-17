<?php
class Member {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function findById(int $id): array|false {
        $s = $this->pdo->prepare("SELECT * FROM Member WHERE MemberID = ?");
        $s->execute([$id]); return $s->fetch();
    }

    public function findByEmail(string $email): array|false {
        $s = $this->pdo->prepare("SELECT * FROM Member WHERE Email = ?");
        $s->execute([$email]); return $s->fetch();
    }

    public function create(array $data): int {
        $s = $this->pdo->prepare("INSERT INTO Member (Fname,Lname,Email,Password,Phone,City,Street) VALUES (?,?,?,?,?,?,?)");
        $s->execute([$data['fname'],$data['lname'],$data['email'],password_hash($data['password'],PASSWORD_BCRYPT),$data['phone']??null,$data['city']??null,$data['street']??null]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $this->pdo->prepare("UPDATE Member SET Fname=?,Lname=?,Phone=?,City=?,Street=? WHERE MemberID=?")
            ->execute([$data['fname'],$data['lname'],$data['phone'],$data['city'],$data['street'],$id]);
    }

    public function updateTrustScore(int $id, int $delta): void {
        $this->pdo->prepare("UPDATE Member SET TrustScore = LEAST(100, GREATEST(0, TrustScore + ?)) WHERE MemberID = ?")
            ->execute([$delta, $id]);
    }

    /**
     * Update the membership tier directly.
     * Normally called by TierUpgradeObserver after a reservation is completed.
     * Valid values: 'basic', 'silver', 'gold'
     */
    public function updateTier(int $id, string $tier): void {
        $allowed = ['basic', 'silver', 'gold'];
        if (!in_array($tier, $allowed)) return;
        $this->pdo->prepare("UPDATE Member SET MembershipTier = ? WHERE MemberID = ?")
            ->execute([$tier, $id]);
    }

    /**
     * Count completed reservations for a member.
     * Useful for showing progress toward next tier in the UI.
     */
    public function countCompletedReservations(int $id): int {
        $s = $this->pdo->prepare("SELECT COUNT(*) FROM Reservation WHERE MemberID = ? AND Status = 'completed'");
        $s->execute([$id]);
        return (int) $s->fetchColumn();
    }
}
