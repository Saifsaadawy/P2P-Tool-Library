<?php
class ReservationProxy {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function canReserve(int $memberId, int $toolId): array {
        $s = $this->pdo->prepare("SELECT TrustScore, Verified, Status FROM Member WHERE MemberID=?");
        $s->execute([$memberId]);
        $m = $s->fetch();

        if (!$m)                        return [false, 'Member not found.'];
        if ($m['Status'] !== 'active')  return [false, 'Your account is suspended.'];
        if (!$m['Verified'])            return [false, 'Please complete KYC verification first.'];
        if ($m['TrustScore'] < 30)      return [false, 'Your trust score is too low to make reservations.'];

        // Check tool is available
        $s2 = $this->pdo->prepare("SELECT CurrentStatus FROM Tool WHERE ToolID=?");
        $s2->execute([$toolId]);
        $t = $s2->fetch();
        if (!$t || $t['CurrentStatus'] !== 'available') return [false, 'Tool is not available.'];

        return [true, 'OK'];
    }
}
