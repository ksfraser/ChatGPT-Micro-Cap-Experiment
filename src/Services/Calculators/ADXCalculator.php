<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;

/**
 * Single Responsibility: Calculate ADX (Average Directional Index)
 * Follows SRP by handling only ADX-related calculations
 */
class ADXCalculator
{
    /**
     * Calculate Average Directional Index (ADX)
     * Measures trend strength regardless of direction
     */
    public function calculate(Collection $data, int $period = 14): array
    {
        if ($data->count() < $period + 1) {
            return [
                'adx' => null,
                'plus_di' => null,
                'minus_di' => null,
                'trend_direction' => 'unknown',
                'trend_strength' => 'weak'
            ];
        }

        $trueRanges = [];
        $plusDMs = [];
        $minusDMs = [];
        
        // Calculate True Range, +DM, and -DM
        for ($i = 1; $i < $data->count(); $i++) {
            $current = $data[$i];
            $previous = $data[$i - 1];
            
            $high = $current->high;
            $low = $current->low;
            $close = $current->close;
            $prevHigh = $previous->high;
            $prevLow = $previous->low;
            $prevClose = $previous->close;
            
            // True Range
            $tr1 = $high - $low;
            $tr2 = abs($high - $prevClose);
            $tr3 = abs($low - $prevClose);
            $trueRanges[] = max($tr1, $tr2, $tr3);
            
            // Directional Movement
            $upMove = $high - $prevHigh;
            $downMove = $prevLow - $low;
            
            $plusDMs[] = ($upMove > $downMove && $upMove > 0) ? $upMove : 0;
            $minusDMs[] = ($downMove > $upMove && $downMove > 0) ? $downMove : 0;
        }
        
        // Calculate smoothed values
        $smoothedTR = $this->exponentialMovingAverage($trueRanges, $period);
        $smoothedPlusDM = $this->exponentialMovingAverage($plusDMs, $period);
        $smoothedMinusDM = $this->exponentialMovingAverage($minusDMs, $period);
        
        $plusDI = [];
        $minusDI = [];
        $dx = [];
        
        for ($i = 0; $i < count($smoothedTR); $i++) {
            if ($smoothedTR[$i] > 0) {
                $plusDI[] = ($smoothedPlusDM[$i] / $smoothedTR[$i]) * 100;
                $minusDI[] = ($smoothedMinusDM[$i] / $smoothedTR[$i]) * 100;
                
                $diSum = $plusDI[$i] + $minusDI[$i];
                $dx[] = $diSum > 0 ? (abs($plusDI[$i] - $minusDI[$i]) / $diSum) * 100 : 0;
            } else {
                $plusDI[] = 0;
                $minusDI[] = 0;
                $dx[] = 0;
            }
        }
        
        // Calculate ADX
        $adxValues = $this->exponentialMovingAverage($dx, $period);
        $latestADX = end($adxValues);
        $latestPlusDI = end($plusDI);
        $latestMinusDI = end($minusDI);
        
        return [
            'adx' => $latestADX,
            'plus_di' => $latestPlusDI,
            'minus_di' => $latestMinusDI,
            'trend_direction' => $this->getTrendDirection($latestPlusDI, $latestMinusDI),
            'trend_strength' => $this->getTrendStrength($latestADX),
            'historical_values' => array_slice($adxValues, -min(20, count($adxValues)))
        ];
    }

    /**
     * Calculate exponential moving average
     */
    private function exponentialMovingAverage(array $data, int $period): array
    {
        if (empty($data) || $period <= 0) {
            return [];
        }
        
        $alpha = 2 / ($period + 1);
        $ema = [];
        
        // First value is simple average of first $period values
        if (count($data) >= $period) {
            $ema[0] = array_sum(array_slice($data, 0, $period)) / $period;
            
            // Calculate EMA for remaining values
            for ($i = 1; $i < count($data) - $period + 1; $i++) {
                $ema[$i] = ($data[$i + $period - 1] * $alpha) + ($ema[$i - 1] * (1 - $alpha));
            }
        }
        
        return $ema;
    }

    /**
     * Determine trend direction based on DI values
     */
    private function getTrendDirection(float $plusDI, float $minusDI): string
    {
        if ($plusDI > $minusDI) {
            return 'up';
        } elseif ($minusDI > $plusDI) {
            return 'down';
        } else {
            return 'sideways';
        }
    }

    /**
     * Determine trend strength based on ADX value
     */
    private function getTrendStrength(float $adx): string
    {
        if ($adx >= 50) {
            return 'very_strong';
        } elseif ($adx >= 25) {
            return 'strong';
        } elseif ($adx >= 20) {
            return 'moderate';
        } else {
            return 'weak';
        }
    }

    /**
     * Calculate ADX accuracy based on historical performance
     */
    public function calculateAccuracy(Collection $data, int $period = 14, int $lookback = 50): array
    {
        if ($data->count() < $period + $lookback) {
            return [
                'accuracy_score' => null,
                'confidence_level' => null,
                'sample_size' => 0
            ];
        }

        $correct_predictions = 0;
        $total_predictions = 0;
        
        // Test ADX predictions over historical data
        for ($i = $period + 1; $i < min($data->count() - 1, $period + $lookback); $i++) {
            $subset = $data->slice($i - $period, $period + 1);
            $adxResult = $this->calculate($subset, $period);
            
            if ($adxResult['adx'] !== null) {
                $predicted_direction = $adxResult['trend_direction'];
                
                // Check if prediction was correct by looking at next period's price movement
                $current_price = $data[$i]->close;
                $next_price = $data[$i + 1]->close;
                
                $actual_direction = 'sideways';
                if ($next_price > $current_price * 1.001) { // 0.1% threshold
                    $actual_direction = 'up';
                } elseif ($next_price < $current_price * 0.999) {
                    $actual_direction = 'down';
                }
                
                if ($predicted_direction === $actual_direction || 
                    ($predicted_direction === 'sideways' && $actual_direction === 'sideways')) {
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
        
        // Simple confidence calculation based on sample size
        // More samples = higher confidence in the accuracy measure
        $baseConfidence = min(95, $sampleSize * 2);
        
        // Adjust based on accuracy (extreme values are less reliable)
        if ($accuracy > 80 || $accuracy < 20) {
            $baseConfidence *= 0.8;
        }
        
        return $baseConfidence;
    }
}
