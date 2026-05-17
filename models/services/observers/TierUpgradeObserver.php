<?php
require_once __DIR__ . '/IObserver.php';

/**
 * TierUpgradeObserver
 * ────────────────────
 * Listens for 'reservation.returned' events.
 * After each completed reservation, counts the member's total completed
 * reservations and upgrades their MembershipTier accordingly:
 *
 *   basic  →  0–4  completed reservations
 *   silver →  5–14 completed reservations  (+5% discount)
 *   gold   →  15+  completed reservations  (+10% discount)
 *
 * The new tier is stored on the Member row so PricingContext can read it
 * at booking time via MembershipStrategy.
 */
class TierUpgradeObserver implements IObserver
{
    private PDO $pdo;

    // Tier thresholds: how many *completed* reservations unlock each tier
    private const TIERS = [
        'gold'   => 15,
        'silver' => 5,
        'basic'  => 0,
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function update(string $event, array $data): void
    {
        // Only care about completed returns
        if ($event !== 'reservation.returned') return;

        $memberId = $data['MemberID'] ?? null;
        if (!$memberId) return;

        // Count all completed reservations for this member
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM Reservation 
            WHERE MemberID = ? AND Status = 'completed'
        ");
        $stmt->execute([$memberId]);
        $completed = (int) $stmt->fetchColumn();

        // Determine the correct tier
        $newTier = 'basic';
        foreach (self::TIERS as $tier => $threshold) {
            if ($completed >= $threshold) {
                $newTier = $tier;
                break;
            }
        }

        // Update only if the tier actually changed (avoid unnecessary writes)
        $check = $this->pdo->prepare("SELECT MembershipTier FROM Member WHERE MemberID = ?");
        $check->execute([$memberId]);
        $currentTier = $check->fetchColumn();

        if ($currentTier !== $newTier) {
            $this->pdo->prepare("UPDATE Member SET MembershipTier = ? WHERE MemberID = ?")
                      ->execute([$newTier, $memberId]);
        }
    }

    /**
     * Utility: return tier info as array (useful for API responses / UI badges).
     */
    public static function tierInfo(string $tier): array
    {
        return match ($tier) {
            'gold'   => ['tier' => 'gold',   'discount' => 10, 'min_reservations' => 15, 'label' => '🥇 Gold'],
            'silver' => ['tier' => 'silver', 'discount' => 5,  'min_reservations' => 5,  'label' => '🥈 Silver'],
            default  => ['tier' => 'basic',  'discount' => 0,  'min_reservations' => 0,  'label' => '🔵 Basic'],
        };
    }
}
