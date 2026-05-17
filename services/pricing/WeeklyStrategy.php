<?php
require_once __DIR__ . '/IPricingStrategy.php';
class WeeklyStrategy implements IPricingStrategy {
    public function calculate(float $dailyRate, int $days, int $trustScore = 50): float {
        $weeks    = ceil($days / 7);
        $weekly   = $dailyRate * 7 * 0.85; // 15% weekly discount
        return round($weekly * $weeks, 2);
    }
}
