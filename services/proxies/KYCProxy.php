<?php
class KYCProxy {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function isVerified(int $memberId): bool {
        $s = $this->pdo->prepare("SELECT Verified FROM Member WHERE MemberID=?");
        $s->execute([$memberId]);
        return (bool)($s->fetchColumn());
    }

    public function verify(int $memberId): void {
        $this->pdo->prepare("UPDATE Member SET Verified=1 WHERE MemberID=?")->execute([$memberId]);
    }
}
