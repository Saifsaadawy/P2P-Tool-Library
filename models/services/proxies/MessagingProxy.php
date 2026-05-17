<?php
class MessagingProxy {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function canMessage(int $senderId, int $receiverId): array {
        // Both must be active verified members
        $s = $this->pdo->prepare("SELECT Status, Verified FROM Member WHERE MemberID=?");
        $s->execute([$senderId]);
        $sender = $s->fetch();
        if (!$sender || $sender['Status'] !== 'active') return [false, 'Sender account is not active.'];
        if (!$sender['Verified'])                       return [false, 'Sender must be verified to send messages.'];
        return [true, 'OK'];
    }
}
