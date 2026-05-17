<?php
require_once __DIR__ . '/IPricingStrategy.php';
class DailyStrategy implements IPricingStrategy {
    public function calculate(float $dailyRate, int $days, int $trustScore = 50): float {
        return round($dailyRate * $days, 2);
    }
}
