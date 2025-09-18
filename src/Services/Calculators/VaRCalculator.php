<?php

namespace App\Services\Calculators;

/**
 * Single Responsibility: Calculate Value at Risk (VaR)
 * Follows SRP by handling only VaR calculations
 */
class VaRCalculator
{
    /**
     * Calculate Value at Risk using Historical Simulation method
     */
    public function calculateHistoricalVaR(array $returns, float $confidence = 0.95): array
    {
        if (empty($returns)) {
            return [
                'var' => null,
                'expected_shortfall' => null,
                'confidence_level' => $confidence
            ];
        }

        // Sort returns in ascending order
        $sortedReturns = $returns;
        sort($sortedReturns);
        
        // Calculate VaR at specified confidence level
        $percentile = (1 - $confidence) * 100;
        $index = intval(count($sortedReturns) * (1 - $confidence));
        $var = $sortedReturns[$index] ?? 0;
        
        // Calculate Expected Shortfall (Conditional VaR)
        $expectedShortfall = $this->calculateExpectedShortfall($sortedReturns, $index);
        
        return [
            'var' => $var,
            'expected_shortfall' => $expectedShortfall,
            'confidence_level' => $confidence,
            'percentile' => $percentile,
            'sample_size' => count($returns),
            'method' => 'historical_simulation'
        ];
    }

    /**
     * Calculate Value at Risk using Parametric method (assumes normal distribution)
     */
    public function calculateParametricVaR(array $returns, float $confidence = 0.95): array
    {
        if (empty($returns)) {
            return [
                'var' => null,
                'expected_shortfall' => null,
                'confidence_level' => $confidence
            ];
        }

        $mean = array_sum($returns) / count($returns);
        $variance = $this->calculateVariance($returns, $mean);
        $stdDev = sqrt($variance);
        
        // Z-score for given confidence level
        $zScore = $this->getZScore($confidence);
        
        // VaR calculation
        $var = $mean - ($zScore * $stdDev);
        
        // Expected Shortfall for normal distribution
        $expectedShortfall = $mean - ($stdDev * $this->getNormalExpectedShortfallMultiplier($confidence));
        
        return [
            'var' => $var,
            'expected_shortfall' => $expectedShortfall,
            'confidence_level' => $confidence,
            'mean_return' => $mean,
            'volatility' => $stdDev,
            'z_score' => $zScore,
            'method' => 'parametric_normal'
        ];
    }

    /**
     * Calculate Value at Risk using Monte Carlo simulation
     */
    public function calculateMonteCarloVaR(array $returns, float $confidence = 0.95, int $simulations = 10000): array
    {
        if (empty($returns)) {
            return [
                'var' => null,
                'expected_shortfall' => null,
                'confidence_level' => $confidence
            ];
        }

        $mean = array_sum($returns) / count($returns);
        $stdDev = sqrt($this->calculateVariance($returns, $mean));
        
        // Generate random returns based on historical distribution
        $simulatedReturns = [];
        
        for ($i = 0; $i < $simulations; $i++) {
            // Box-Muller transformation for normal random numbers
            $u1 = mt_rand() / mt_getrandmax();
            $u2 = mt_rand() / mt_getrandmax();
            
            $z0 = sqrt(-2 * log($u1)) * cos(2 * pi() * $u2);
            $simulatedReturn = $mean + ($stdDev * $z0);
            $simulatedReturns[] = $simulatedReturn;
        }
        
        // Calculate VaR from simulated returns
        return $this->calculateHistoricalVaR($simulatedReturns, $confidence);
    }

    /**
     * Calculate Expected Shortfall (Conditional VaR)
     */
    private function calculateExpectedShortfall(array $sortedReturns, int $varIndex): float
    {
        if ($varIndex <= 0) {
            return $sortedReturns[0] ?? 0;
        }
        
        $tailReturns = array_slice($sortedReturns, 0, $varIndex + 1);
        return array_sum($tailReturns) / count($tailReturns);
    }

    /**
     * Calculate variance
     */
    private function calculateVariance(array $returns, float $mean = null): float
    {
        if (empty($returns)) {
            return 0;
        }
        
        if ($mean === null) {
            $mean = array_sum($returns) / count($returns);
        }
        
        $variance = 0;
        foreach ($returns as $return) {
            $variance += pow($return - $mean, 2);
        }
        
        return $variance / (count($returns) - 1);
    }

    /**
     * Get Z-score for confidence level
     */
    private function getZScore(float $confidence): float
    {
        $zScores = [
            0.90 => 1.282,
            0.95 => 1.645,
            0.975 => 1.960,
            0.99 => 2.326,
            0.995 => 2.576
        ];
        
        return $zScores[$confidence] ?? 1.645;
    }

    /**
     * Get Expected Shortfall multiplier for normal distribution
     */
    private function getNormalExpectedShortfallMultiplier(float $confidence): float
    {
        $multipliers = [
            0.90 => 1.755,
            0.95 => 2.063,
            0.975 => 2.338,
            0.99 => 2.665,
            0.995 => 2.892
        ];
        
        return $multipliers[$confidence] ?? 2.063;
    }

    /**
     * Calculate VaR at multiple confidence levels
     */
    public function calculateMultiLevelVaR(array $returns, array $confidenceLevels = [0.90, 0.95, 0.99]): array
    {
        $results = [];
        
        foreach ($confidenceLevels as $confidence) {
            $results["var_{$confidence}"] = $this->calculateHistoricalVaR($returns, $confidence);
        }
        
        return $results;
    }

    /**
     * Calculate VaR for different time horizons
     */
    public function calculateTimeHorizonVaR(array $dailyReturns, float $confidence = 0.95, array $horizons = [1, 5, 10, 22]): array
    {
        $results = [];
        
        foreach ($horizons as $horizon) {
            // Scale VaR by square root of time for normal distribution
            $baseVaR = $this->calculateParametricVaR($dailyReturns, $confidence);
            
            if ($baseVaR['var'] !== null) {
                $scaledVaR = $baseVaR['var'] * sqrt($horizon);
                $scaledES = $baseVaR['expected_shortfall'] * sqrt($horizon);
                
                $results["horizon_{$horizon}_days"] = [
                    'var' => $scaledVaR,
                    'expected_shortfall' => $scaledES,
                    'confidence_level' => $confidence,
                    'time_horizon_days' => $horizon,
                    'scaling_factor' => sqrt($horizon)
                ];
            }
        }
        
        return $results;
    }

    /**
     * Backtest VaR model accuracy
     */
    public function backtestVaR(array $returns, float $confidence = 0.95, int $windowSize = 250): array
    {
        if (count($returns) < $windowSize + 50) {
            return [
                'violations' => null,
                'violation_rate' => null,
                'expected_violations' => null,
                'accuracy' => null
            ];
        }

        $violations = 0;
        $totalTests = 0;
        $expectedViolationRate = 1 - $confidence;
        
        // Rolling window VaR calculation and testing
        for ($i = $windowSize; $i < count($returns) - 1; $i++) {
            $window = array_slice($returns, $i - $windowSize, $windowSize);
            $varResult = $this->calculateHistoricalVaR($window, $confidence);
            
            if ($varResult['var'] !== null) {
                $actualReturn = $returns[$i + 1];
                
                // Check if actual return violates VaR
                if ($actualReturn < $varResult['var']) {
                    $violations++;
                }
                $totalTests++;
            }
        }
        
        $actualViolationRate = $totalTests > 0 ? $violations / $totalTests : 0;
        $expectedViolations = $totalTests * $expectedViolationRate;
        
        // Calculate accuracy (how close actual violation rate is to expected)
        $accuracy = 100 - (abs($actualViolationRate - $expectedViolationRate) * 100);
        
        return [
            'violations' => $violations,
            'total_tests' => $totalTests,
            'violation_rate' => round($actualViolationRate * 100, 2),
            'expected_violation_rate' => round($expectedViolationRate * 100, 2),
            'expected_violations' => round($expectedViolations, 1),
            'accuracy' => round($accuracy, 2),
            'confidence_level' => $confidence,
            'window_size' => $windowSize
        ];
    }
}
