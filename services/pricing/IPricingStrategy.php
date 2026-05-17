<?php
interface IPricingStrategy {
    public function calculate(float $dailyRate, int $days, int $trustScore = 50): float;
}
