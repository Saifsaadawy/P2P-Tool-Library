<?php
class PaymentProxy {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function canPay(int $memberId, float $amount): array {
        $s = $this->pdo->prepare("SELECT Balance FROM Member WHERE MemberID=?");
        $s->execute([$memberId]);
        $m = $s->fetch();
        if (!$m)                      return [false, 'Member not found.'];
        if ($m['Balance'] < $amount)  return [false, 'Insufficient balance.'];
        return [true, 'OK'];
    }

    public function deduct(int $memberId, float $amount): void {
        $this->pdo->prepare("UPDATE Member SET Balance = GREATEST(0, Balance - ?) WHERE MemberID=?")->execute([$amount, $memberId]);
    }
}