<?php
require_once __DIR__ . '/IPricingStrategy.php';
require_once __DIR__ . '/DailyStrategy.php';
require_once __DIR__ . '/WeeklyStrategy.php';
require_once __DIR__ . '/TrustBasedStrategy.php';
require_once __DIR__ . '/MembershipStrategy.php';

class PricingContext {
    private IPricingStrategy $strategy;

    public function __construct(IPricingStrategy $strategy) {
        $this->strategy = $strategy;
    }

    public static function forDays(int $days): self {
        return $days >= 7 ? new self(new WeeklyStrategy()) : new self(new DailyStrategy());
    }

    public static function withTrust(): self {
        return new self(new TrustBasedStrategy());
    }

    /**
     * Price based on MembershipTier (basic / silver / gold).
     * Pass the tier string fetched from Member.MembershipTier.
     *
     * Example:
     *   $price = PricingContext::withTier($member['MembershipTier'])
     *                          ->calculate($tool['DailyRate'], $days);
     */
    public static function withTier(string $tier): self {
        return new self(new MembershipStrategy($tier));
    }

    public function calculate(float $dailyRate, int $days, int $trustScore = 50): float {
        return $this->strategy->calculate($dailyRate, $days, $trustScore);
    }
}
