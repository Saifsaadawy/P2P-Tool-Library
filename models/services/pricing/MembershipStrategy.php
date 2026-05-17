<?php
require_once __DIR__ . '/IPricingStrategy.php';

/**
 * MembershipStrategy
 * ───────────────────
 * Applies a discount based on the member's MembershipTier:
 *
 *   basic  → 0%  discount
 *   silver → 5%  discount
 *   gold   → 10% discount
 *
 * Usage:
 *   $price = PricingContext::withTier('gold')->calculate($dailyRate, $days);
 */
class MembershipStrategy implements IPricingStrategy
{
    private string $tier;

    private const DISCOUNTS = [
        'gold'   => 0.10,   // 10%
        'silver' => 0.05,   //  5%
        'basic'  => 0.00,   //  0%
    ];

    public function __construct(string $tier = 'basic')
    {
        $this->tier = $tier;
    }

    public function calculate(float $dailyRate, int $days, int $trustScore = 50): float
    {
        $discount = self::DISCOUNTS[$this->tier] ?? 0.00;
        return round($dailyRate * $days * (1 - $discount), 2);
    }

    public function getDiscount(): float
    {
        return self::DISCOUNTS[$this->tier] ?? 0.00;
    }

    public function getTier(): string
    {
        return $this->tier;
    }
}
