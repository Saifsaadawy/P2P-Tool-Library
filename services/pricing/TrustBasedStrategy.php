<?php
require_once __DIR__ . '/IPricingStrategy.php';
class TrustBasedStrategy implements IPricingStrategy {
    public function calculate(float $dailyRate, int $days, int $trustScore = 50): float {
        // TrustScore 80+ → 10% discount, 60-79 → 5% discount, below 60 → no discount
        $discount = $trustScore >= 80 ? 0.10 : ($trustScore >= 60 ? 0.05 : 0);
        return round($dailyRate * $days * (1 - $discount), 2);
    }
}
