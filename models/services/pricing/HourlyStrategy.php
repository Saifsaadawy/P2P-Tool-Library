<?php
require_once __DIR__ . '/IPricingStrategy.php';
class HourlyStrategy implements IPricingStrategy {
    public function calculate(float $dailyRate, int $days, int $trustScore = 50): float {
        $hourlyRate = $dailyRate / 8;
        return round($hourlyRate * $days, 2); // $days used as hours here
    }
}
