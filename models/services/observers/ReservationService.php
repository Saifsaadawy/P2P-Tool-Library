<?php
require_once __DIR__ . '/ISubject.php';
require_once __DIR__ . '/IObserver.php';

class ReservationService implements ISubject
{
    private array $observers = [];
    private \PDO  $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function attach(IObserver $observer): void   { $this->observers[] = $observer; }
    public function detach(IObserver $observer): void   { $this->observers = array_filter($this->observers, fn($o) => $o !== $observer); }
    public function notify(string $event, array $data): void { foreach ($this->observers as $o) $o->update($event, $data); }

    /** Member requests to borrow a tool */
    public function create(array $input): array
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO Reservation (MemberID, ToolID, StartDate, EndDate, PickupDate, TotalCost, Status)
            VALUES (:member, :tool, :start, :end, :pickup, :cost, 'pending')
        ");
        $stmt->execute([
            ':member' => $input['member_id'],
            ':tool'   => $input['tool_id'],
            ':start'  => $input['start_date'],
            ':end'    => $input['end_date'],
            ':pickup' => $input['pickup_date'],
            ':cost'   => $input['total_cost'],
        ]);
        $id = $this->pdo->lastInsertId();
        $data = array_merge($input, ['reservation_id' => $id]);

        $this->notify('reservation.created', $data);
        return ['success' => true, 'reservation_id' => $id];
    }

    /** Librarian approves a reservation */
    public function approve(int $reservationId, int $librarianId, string $qrToken = ''): array
    {
        $this->pdo->prepare("UPDATE Reservation SET Status='approved' WHERE ReservationID=?")
                  ->execute([$reservationId]);

        $data = $this->fetchReservationData($reservationId);
        $data['librarian_id'] = $librarianId;
        $data['qr_token']     = $qrToken;

        $this->notify('reservation.approved', $data);
        return ['success' => true];
    }

    /** Member or Librarian cancels */
    public function cancel(int $reservationId, string $reason = ''): array
    {
        $this->pdo->prepare("UPDATE Reservation SET Status='cancelled' WHERE ReservationID=?")
                  ->execute([$reservationId]);

        $data = $this->fetchReservationData($reservationId);
        $data['reason'] = $reason;

        $this->notify('reservation.cancelled', $data);
        return ['success' => true];
    }

    /** Tool returned — updates status and tool availability */
    public function markReturned(int $reservationId): array
    {
        $this->pdo->prepare("UPDATE Reservation SET Status='completed', ReturnDate=CURDATE() WHERE ReservationID=?")
                  ->execute([$reservationId]);

        $data = $this->fetchReservationData($reservationId);
        $this->notify('reservation.returned', $data);
        return ['success' => true];
    }

    private function fetchReservationData(int $id): array
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, m.Email AS member_email, CONCAT(m.Fname,' ',m.Lname) AS member_name,
                   t.Name AS tool_name, t.MemberID AS owner_id,
                   mo.Email AS owner_email, CONCAT(mo.Fname,' ',mo.Lname) AS owner_name
            FROM Reservation r
            JOIN Member m  ON m.MemberID = r.MemberID
            JOIN Tool   t  ON t.ToolID   = r.ToolID
            JOIN Member mo ON mo.MemberID = t.MemberID
            WHERE r.ReservationID = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
    }
}
