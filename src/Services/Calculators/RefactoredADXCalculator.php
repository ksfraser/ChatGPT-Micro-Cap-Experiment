<?php

namespace App\Services\Calculators;

use LupeCode\phpTraderNative\Trader;

/**
 * Refactored ADX Calculator using TA-Lib
 * Single Responsibility: Calculate ADX indicators using TA-Lib backend
 * 
 * This demonstrates how to refactor existing calculators to use TA-Lib
 * while maintaining the same interface and adding our custom enhancements
 */
class RefactoredADXCalculator extends TALibCalculatorBase
{
    /**
     * Calculate ADX using TA-Lib (replaces our custom implementation)
     */
    public function calculate(array $priceData, int $period = 14): array
    {
        $this->validatePriceData($priceData, ['high', 'low', 'close']);

        if (count($priceData) < $period * 2) {
            return [
                'adx_values' => [],
                'adx_signal' => 'insufficient_data',
                'current_adx' => null,
                'trend_strength' => 'unknown',
                'calculation_engine' => 'TA-Lib'
            ];
        }

        // Extract price arrays for TA-Lib
        $arrays = $this->extractArrays($priceData, ['high', 'low', 'close']);
        
        // Calculate ADX using TA-Lib
        $adxResults = Trader::adx($arrays['high'], $arrays['low'], $arrays['close'], $period);
        
        // Calculate supporting indicators
        $plusDI = Trader::plus_di($arrays['high'], $arrays['low'], $arrays['close'], $period);
        $minusDI = Trader::minus_di($arrays['high'], $arrays['low'], $arrays['close'], $period);

        // Combine results with our enhancements
        return $this->enhanceADXResults($adxResults, $plusDI, $minusDI, $priceData, $period);
    }

    /**
     * Enhance TA-Lib ADX results with our custom analysis
     */
    private function enhanceADXResults(
        array $adxResults, 
        array $plusDI, 
        array $minusDI, 
        array $originalData, 
        int $period
    ): array {
        $lookback = $period * 2 - 1; // ADX lookback period
        $combined = [];

        for ($i = 0; $i < count($adxResults); $i++) {
            $dataIndex = $lookback + $i;
            $adxValue = $adxResults[$i];
            
            $combined[] = [
                'date' => $originalData[$dataIndex]['date'] ?? $dataIndex,
                'adx' => $adxValue,
                'plus_di' => $plusDI[$i] ?? null,
                'minus_di' => $minusDI[$i] ?? null,
                'trend_strength' => $this->getTrendStrength($adxValue),
                'trend_direction' => $this->getTrendDirection($plusDI[$i] ?? 0, $minusDI[$i] ?? 0),
                'directional_movement' => $this->getDirectionalMovement($plusDI[$i] ?? 0, $minusDI[$i] ?? 0)
            ];
        }

        $currentADX = !empty($adxResults) ? end($adxResults) : null;
        $signal = $this->generateADXSignal($combined);
        $accuracy = $this->calculateAccuracy($combined);

        return [
            'adx_values' => $combined,
            'current_adx' => $currentADX,
            'adx_signal' => $signal,
            'trend_strength' => $currentADX ? $this->getTrendStrength($currentADX) : 'unknown',
            'accuracy_analysis' => $accuracy,
            'period' => $period,
            'calculation_engine' => 'TA-Lib',
            'data_points' => count($originalData),
            'result_points' => count($combined)
        ];
    }

    /**
     * Get trend strength from ADX value
     */
    private function getTrendStrength(float $adx): string
    {
        if ($adx > 50) {
            return 'very_strong';
        } elseif ($adx > 40) {
            return 'strong';
        } elseif ($adx > 25) {
            return 'moderate';
        } elseif ($adx > 20) {
            return 'weak';
        } else {
            return 'very_weak';
        }
    }

    /**
     * Get trend direction from DI values
     */
    private function getTrendDirection(float $plusDI, float $minusDI): string
    {
        $diff = abs($plusDI - $minusDI);
        
        if ($diff < 2) {
            return 'sideways';
        } elseif ($plusDI > $minusDI) {
            return $diff > 10 ? 'strong_uptrend' : 'uptrend';
        } else {
            return $diff > 10 ? 'strong_downtrend' : 'downtrend';
        }
    }

    /**
     * Get directional movement analysis
     */
    private function getDirectionalMovement(float $plusDI, float $minusDI): array
    {
        return [
            'plus_di' => $plusDI,
            'minus_di' => $minusDI,
            'di_difference' => $plusDI - $minusDI,
            'di_sum' => $plusDI + $minusDI,
            'directional_ratio' => $minusDI > 0 ? $plusDI / $minusDI : 0
        ];
    }

    /**
     * Generate ADX signal based on comprehensive analysis
     */
    private function generateADXSignal(array $adxData): string
    {
        if (count($adxData) < 3) {
            return 'neutral';
        }

        $current = end($adxData);
        $previous = $adxData[count($adxData) - 2];
        $beforePrevious = $adxData[count($adxData) - 3];

        $adx = $current['adx'];
        $prevADX = $previous['adx'];
        $plusDI = $current['plus_di'];
        $minusDI = $current['minus_di'];

        // Strong trend signals
        if ($adx > 40 && $adx > $prevADX) {
            if ($plusDI > $minusDI + 5) {
                return 'strong_bullish_trend';
            } elseif ($minusDI > $plusDI + 5) {
                return 'strong_bearish_trend';
            }
        }

        // Trend initiation signals
        if ($adx > 25 && $prevADX <= 25 && $beforePrevious['adx'] <= 25) {
            if ($plusDI > $minusDI) {
                return 'bullish_trend_starting';
            } else {
                return 'bearish_trend_starting';
            }
        }

        // Trend weakening signals
        if ($adx < 20 && $prevADX >= 20) {
            return 'trend_weakening';
        }

        // DI crossover signals
        if ($plusDI > $minusDI && $previous['plus_di'] <= $previous['minus_di'] && $adx > 20) {
            return 'bullish_directional_change';
        }

        if ($minusDI > $plusDI && $previous['minus_di'] <= $previous['plus_di'] && $adx > 20) {
            return 'bearish_directional_change';
        }

        // Consolidation signals
        if ($adx < 20 && abs($plusDI - $minusDI) < 5) {
            return 'consolidation';
        }

        return 'neutral';
    }

    /**
     * Calculate accuracy metrics for ADX signals
     * This preserves our custom accuracy tracking while using TA-Lib calculations
     */
    public function calculateAccuracy(array $adxData): array
    {
        if (count($adxData) < 10) {
            return [
                'trend_accuracy' => null,
                'signal_reliability' => null,
                'sample_size' => count($adxData)
            ];
        }

        $trendChanges = 0;
        $correctPredictions = 0;
        $totalSignals = 0;

        for ($i = 2; $i < count($adxData) - 1; $i++) {
            $current = $adxData[$i];
            $next = $adxData[$i + 1];
            $previous = $adxData[$i - 1];

            // Check if ADX predicted trend correctly
            if ($current['adx'] > 25) {
                $totalSignals++;
                
                // If ADX indicated strong trend, check if it continued
                if ($current['trend_direction'] !== 'sideways') {
                    $nextDirection = $next['trend_direction'];
                    $currentDirection = $current['trend_direction'];
                    
                    if ($this->isSameDirection($currentDirection, $nextDirection)) {
                        $correctPredictions++;
                    }
                }
            }

            // Count trend changes
            if ($current['trend_direction'] !== $previous['trend_direction']) {
                $trendChanges++;
            }
        }

        $accuracy = $totalSignals > 0 ? ($correctPredictions / $totalSignals) * 100 : 0;
        
        return [
            'trend_accuracy' => round($accuracy, 2),
            'correct_predictions' => $correctPredictions,
            'total_signals' => $totalSignals,
            'trend_changes' => $trendChanges,
            'signal_reliability' => $this->getReliabilityLevel($accuracy),
            'sample_size' => count($adxData)
        ];
    }

    /**
     * Check if two trend directions are the same category
     */
    private function isSameDirection(string $dir1, string $dir2): bool
    {
        $bullish = ['uptrend', 'strong_uptrend'];
        $bearish = ['downtrend', 'strong_downtrend'];
        
        return (in_array($dir1, $bullish) && in_array($dir2, $bullish)) ||
               (in_array($dir1, $bearish) && in_array($dir2, $bearish)) ||
               ($dir1 === 'sideways' && $dir2 === 'sideways');
    }

    /**
     * Get reliability level from accuracy percentage
     */
    private function getReliabilityLevel(float $accuracy): string
    {
        if ($accuracy >= 80) {
            return 'very_high';
        } elseif ($accuracy >= 70) {
            return 'high';
        } elseif ($accuracy >= 60) {
            return 'moderate';
        } elseif ($accuracy >= 50) {
            return 'low';
        } else {
            return 'very_low';
        }
    }

    /**
     * Get historical ADX performance analysis
     */
    public function getPerformanceAnalysis(array $priceData, int $period = 14): array
    {
        $adxResult = $this->calculate($priceData, $period);
        
        if (empty($adxResult['adx_values'])) {
            return ['analysis' => 'insufficient_data'];
        }

        $adxValues = $adxResult['adx_values'];
        $performance = [
            'average_adx' => $this->calculateAverageADX($adxValues),
            'trend_periods' => $this->analyzeTrendPeriods($adxValues),
            'volatility_correlation' => $this->analyzeVolatilityCorrelation($adxValues, $priceData),
            'signal_distribution' => $this->analyzeSignalDistribution($adxValues)
        ];

        return array_merge($adxResult, ['performance_analysis' => $performance]);
    }

    /**
     * Calculate average ADX over the period
     */
    private function calculateAverageADX(array $adxValues): float
    {
        $sum = array_sum(array_column($adxValues, 'adx'));
        return $sum / count($adxValues);
    }

    /**
     * Analyze different trend periods
     */
    private function analyzeTrendPeriods(array $adxValues): array
    {
        $strongTrend = 0;
        $moderateTrend = 0;
        $weakTrend = 0;
        $consolidation = 0;

        foreach ($adxValues as $data) {
            $adx = $data['adx'];
            if ($adx > 40) {
                $strongTrend++;
            } elseif ($adx > 25) {
                $moderateTrend++;
            } elseif ($adx > 20) {
                $weakTrend++;
            } else {
                $consolidation++;
            }
        }

        $total = count($adxValues);
        return [
            'strong_trend_pct' => round(($strongTrend / $total) * 100, 1),
            'moderate_trend_pct' => round(($moderateTrend / $total) * 100, 1),
            'weak_trend_pct' => round(($weakTrend / $total) * 100, 1),
            'consolidation_pct' => round(($consolidation / $total) * 100, 1)
        ];
    }

    /**
     * Analyze correlation between ADX and price volatility
     */
    private function analyzeVolatilityCorrelation(array $adxValues, array $priceData): array
    {
        // Simple volatility calculation (could be enhanced)
        $volatilities = [];
        $adxOnly = [];

        for ($i = 1; $i < count($priceData); $i++) {
            $volatility = abs($priceData[$i]['close'] - $priceData[$i-1]['close']) / $priceData[$i-1]['close'];
            $volatilities[] = $volatility;
        }

        // Align with ADX data
        $alignedVolatilities = array_slice($volatilities, -count($adxValues));
        $adxOnly = array_column($adxValues, 'adx');

        if (count($alignedVolatilities) !== count($adxOnly)) {
            return ['correlation' => 'calculation_error'];
        }

        $correlation = $this->calculateCorrelation($adxOnly, $alignedVolatilities);

        return [
            'correlation_coefficient' => round($correlation, 3),
            'relationship' => $this->interpretCorrelation($correlation)
        ];
    }

    /**
     * Calculate Pearson correlation coefficient
     */
    private function calculateCorrelation(array $x, array $y): float
    {
        $n = count($x);
        if ($n !== count($y) || $n === 0) {
            return 0;
        }

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $y[$i] * $y[$i];
        }

        $denominator = sqrt(($n * $sumX2 - $sumX * $sumX) * ($n * $sumY2 - $sumY * $sumY));
        
        if ($denominator == 0) {
            return 0;
        }

        return ($n * $sumXY - $sumX * $sumY) / $denominator;
    }

    /**
     * Interpret correlation coefficient
     */
    private function interpretCorrelation(float $correlation): string
    {
        $abs = abs($correlation);
        
        if ($abs >= 0.8) {
            return $correlation > 0 ? 'strong_positive' : 'strong_negative';
        } elseif ($abs >= 0.6) {
            return $correlation > 0 ? 'moderate_positive' : 'moderate_negative';
        } elseif ($abs >= 0.3) {
            return $correlation > 0 ? 'weak_positive' : 'weak_negative';
        } else {
            return 'negligible';
        }
    }

    /**
     * Analyze distribution of different signal types
     */
    private function analyzeSignalDistribution(array $adxValues): array
    {
        $signals = [];
        
        foreach ($adxValues as $data) {
            $direction = $data['trend_direction'];
            $strength = $data['trend_strength'];
            $key = $direction . '_' . $strength;
            
            if (!isset($signals[$key])) {
                $signals[$key] = 0;
            }
            $signals[$key]++;
        }

        // Convert to percentages
        $total = count($adxValues);
        foreach ($signals as $key => $count) {
            $signals[$key] = round(($count / $total) * 100, 1);
        }

        return $signals;
    }
}
