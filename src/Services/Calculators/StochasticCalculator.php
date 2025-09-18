<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;

/**
 * Single Responsibility: Calculate Stochastic Oscillator
 * Follows SRP by handling only Stochastic calculations
 */
class StochasticCalculator
{
    /**
     * Calculate Stochastic Oscillator
     */
    public function calculate(Collection $data, int $kPeriod = 14, int $dPeriod = 3): array
    {
        if ($data->count() < $kPeriod) {
            return [
                'k_percent' => null,
                'd_percent' => null,
                'signal' => 'NEUTRAL',
                'overbought' => false,
                'oversold' => false
            ];
        }

        $kValues = [];
        
        // Calculate %K values
        for ($i = $kPeriod - 1; $i < $data->count(); $i++) {
            $subset = $data->slice($i - $kPeriod + 1, $kPeriod);
            
            $highest = $subset->max('high');
            $lowest = $subset->min('low');
            $currentClose = $data[$i]->close;
            
            if ($highest == $lowest) {
                $kValues[] = 50; // Avoid division by zero
            } else {
                $kPercent = (($currentClose - $lowest) / ($highest - $lowest)) * 100;
                $kValues[] = $kPercent;
            }
        }

        // Calculate %D (SMA of %K)
        $dValues = [];
        if (count($kValues) >= $dPeriod) {
            for ($i = $dPeriod - 1; $i < count($kValues); $i++) {
                $subset = array_slice($kValues, $i - $dPeriod + 1, $dPeriod);
                $dValues[] = array_sum($subset) / $dPeriod;
            }
        }

        $currentK = end($kValues);
        $currentD = end($dValues);

        return [
            'k_percent' => round($currentK, 2),
            'd_percent' => round($currentD, 2),
            'signal' => $this->getStochasticSignal($currentK, $currentD),
            'overbought' => $currentK > 80,
            'oversold' => $currentK < 20,
            'divergence' => $this->detectDivergence($data, $kValues),
            'historical_k' => array_slice($kValues, -min(20, count($kValues))),
            'historical_d' => array_slice($dValues, -min(20, count($dValues)))
        ];
    }

    /**
     * Calculate Fast Stochastic (no smoothing of %K)
     */
    public function calculateFast(Collection $data, int $kPeriod = 14): array
    {
        return $this->calculate($data, $kPeriod, 1);
    }

    /**
     * Calculate Slow Stochastic (smoothed %K)
     */
    public function calculateSlow(Collection $data, int $kPeriod = 14, int $kSmooth = 3, int $dPeriod = 3): array
    {
        if ($data->count() < $kPeriod + $kSmooth) {
            return [
                'k_percent' => null,
                'd_percent' => null,
                'signal' => 'NEUTRAL'
            ];
        }

        // Calculate raw %K
        $rawK = [];
        for ($i = $kPeriod - 1; $i < $data->count(); $i++) {
            $subset = $data->slice($i - $kPeriod + 1, $kPeriod);
            
            $highest = $subset->max('high');
            $lowest = $subset->min('low');
            $currentClose = $data[$i]->close;
            
            if ($highest == $lowest) {
                $rawK[] = 50;
            } else {
                $kPercent = (($currentClose - $lowest) / ($highest - $lowest)) * 100;
                $rawK[] = $kPercent;
            }
        }

        // Smooth %K
        $smoothedK = [];
        if (count($rawK) >= $kSmooth) {
            for ($i = $kSmooth - 1; $i < count($rawK); $i++) {
                $subset = array_slice($rawK, $i - $kSmooth + 1, $kSmooth);
                $smoothedK[] = array_sum($subset) / $kSmooth;
            }
        }

        // Calculate %D from smoothed %K
        $dValues = [];
        if (count($smoothedK) >= $dPeriod) {
            for ($i = $dPeriod - 1; $i < count($smoothedK); $i++) {
                $subset = array_slice($smoothedK, $i - $dPeriod + 1, $dPeriod);
                $dValues[] = array_sum($subset) / $dPeriod;
            }
        }

        $currentK = end($smoothedK);
        $currentD = end($dValues);

        return [
            'k_percent' => round($currentK, 2),
            'd_percent' => round($currentD, 2),
            'signal' => $this->getStochasticSignal($currentK, $currentD),
            'type' => 'slow'
        ];
    }

    /**
     * Generate trading signal based on Stochastic values
     */
    private function getStochasticSignal(float $kPercent, ?float $dPercent): string
    {
        // Oversold bounce
        if ($kPercent < 20 && $dPercent !== null && $kPercent > $dPercent) {
            return 'BUY';
        }
        
        // Overbought reversal
        if ($kPercent > 80 && $dPercent !== null && $kPercent < $dPercent) {
            return 'SELL';
        }
        
        // Strong oversold
        if ($kPercent < 10) {
            return 'STRONG_BUY';
        }
        
        // Strong overbought
        if ($kPercent > 90) {
            return 'STRONG_SELL';
        }
        
        return 'NEUTRAL';
    }

    /**
     * Detect bullish/bearish divergence
     */
    private function detectDivergence(Collection $data, array $kValues): string
    {
        if (count($kValues) < 10 || $data->count() < 10) {
            return 'none';
        }

        $recentPrices = $data->slice(-10)->map(function($quote) {
            return $quote->close;
        })->toArray();
        
        $recentK = array_slice($kValues, -10);

        // Simple divergence detection
        $priceSlope = $this->calculateSlope($recentPrices);
        $kSlope = $this->calculateSlope($recentK);

        // Bullish divergence: price falling, stochastic rising
        if ($priceSlope < -0.1 && $kSlope > 0.1) {
            return 'bullish';
        }
        
        // Bearish divergence: price rising, stochastic falling
        if ($priceSlope > 0.1 && $kSlope < -0.1) {
            return 'bearish';
        }

        return 'none';
    }

    /**
     * Calculate slope of a data series
     */
    private function calculateSlope(array $data): float
    {
        $n = count($data);
        if ($n < 2) return 0;

        $x = range(0, $n - 1);
        $sumX = array_sum($x);
        $sumY = array_sum($data);
        $sumXY = 0;
        $sumXX = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $data[$i];
            $sumXX += $x[$i] * $x[$i];
        }

        $denominator = ($n * $sumXX) - ($sumX * $sumX);
        
        if ($denominator == 0) return 0;
        
        return (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
    }

    /**
     * Calculate Stochastic accuracy
     */
    public function calculateAccuracy(Collection $data, int $kPeriod = 14, int $dPeriod = 3, int $lookback = 50): array
    {
        if ($data->count() < $kPeriod + $lookback) {
            return [
                'accuracy_score' => null,
                'confidence_level' => null,
                'sample_size' => 0
            ];
        }

        $correct_predictions = 0;
        $total_predictions = 0;
        
        for ($i = $kPeriod; $i < min($data->count() - 1, $kPeriod + $lookback); $i++) {
            $subset = $data->slice($i - $kPeriod, $kPeriod);
            $stochResult = $this->calculate($subset, $kPeriod, $dPeriod);
            
            if ($stochResult['k_percent'] !== null) {
                $signal = $stochResult['signal'];
                
                $entry_price = $data[$i]->close;
                $exit_price = $data[$i + 1]->close;
                
                $profitable = false;
                
                if (strpos($signal, 'BUY') !== false && $exit_price > $entry_price) {
                    $profitable = true;
                } elseif (strpos($signal, 'SELL') !== false && $exit_price < $entry_price) {
                    $profitable = true;
                } elseif ($signal === 'NEUTRAL') {
                    $change = abs($exit_price - $entry_price) / $entry_price;
                    if ($change < 0.015) { // Less than 1.5% move
                        $profitable = true;
                    }
                }
                
                if ($profitable) {
                    $correct_predictions++;
                }
                $total_predictions++;
            }
        }
        
        $accuracy = $total_predictions > 0 ? ($correct_predictions / $total_predictions) * 100 : 0;
        $confidence = $this->calculateConfidenceLevel($total_predictions, $accuracy);
        
        return [
            'accuracy_score' => round($accuracy, 2),
            'confidence_level' => round($confidence, 2),
            'sample_size' => $total_predictions,
            'correct_predictions' => $correct_predictions
        ];
    }

    /**
     * Calculate statistical confidence level
     */
    private function calculateConfidenceLevel(int $sampleSize, float $accuracy): float
    {
        if ($sampleSize < 10) {
            return 0;
        }
        
        $baseConfidence = min(95, $sampleSize * 1.8);
        
        if ($accuracy > 70 || $accuracy < 30) {
            $baseConfidence *= 0.9;
        }
        
        return $baseConfidence;
    }
}
