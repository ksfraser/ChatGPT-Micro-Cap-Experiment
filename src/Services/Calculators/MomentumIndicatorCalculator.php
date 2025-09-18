<?php

namespace App\Services\Calculators;

/**
 * Single Responsibility: Calculate Momentum-Based Technical Indicators
 * Follows SRP by handling only momentum and oscillator calculations
 */
class MomentumIndicatorCalculator
{
    /**
     * Calculate Williams %R
     */
    public function calculateWilliamsR(array $priceData, int $period = 14): array
    {
        if (empty($priceData) || count($priceData) < $period) {
            return [
                'williams_r_values' => [],
                'williams_r_signal' => 'neutral',
                'current_williams_r' => null
            ];
        }

        $williamsRValues = [];

        for ($i = $period - 1; $i < count($priceData); $i++) {
            $periodData = array_slice($priceData, $i - $period + 1, $period);
            
            $highestHigh = max(array_column($periodData, 'high'));
            $lowestLow = min(array_column($periodData, 'low'));
            $currentClose = $priceData[$i]['close'];
            
            $williamsR = 0;
            if ($highestHigh - $lowestLow != 0) {
                $williamsR = (($highestHigh - $currentClose) / ($highestHigh - $lowestLow)) * -100;
            }
            
            $williamsRValues[] = [
                'date' => $priceData[$i]['date'] ?? $i,
                'williams_r' => $williamsR,
                'highest_high' => $highestHigh,
                'lowest_low' => $lowestLow,
                'close' => $currentClose
            ];
        }

        $signal = $this->analyzeWilliamsRSignal($williamsRValues);

        return [
            'williams_r_values' => $williamsRValues,
            'current_williams_r' => end($williamsRValues)['williams_r'],
            'williams_r_signal' => $signal,
            'period' => $period,
            'overbought_level' => -20,
            'oversold_level' => -80
        ];
    }

    /**
     * Calculate Commodity Channel Index (CCI)
     */
    public function calculateCCI(array $priceData, int $period = 20): array
    {
        if (empty($priceData) || count($priceData) < $period) {
            return [
                'cci_values' => [],
                'cci_signal' => 'neutral',
                'current_cci' => null
            ];
        }

        $cciValues = [];

        for ($i = $period - 1; $i < count($priceData); $i++) {
            $periodData = array_slice($priceData, $i - $period + 1, $period);
            
            // Calculate typical prices for the period
            $typicalPrices = [];
            foreach ($periodData as $data) {
                $typicalPrices[] = ($data['high'] + $data['low'] + $data['close']) / 3;
            }
            
            $currentTypicalPrice = end($typicalPrices);
            $smaTypicalPrice = array_sum($typicalPrices) / count($typicalPrices);
            
            // Calculate Mean Deviation
            $meanDeviation = 0;
            foreach ($typicalPrices as $tp) {
                $meanDeviation += abs($tp - $smaTypicalPrice);
            }
            $meanDeviation /= count($typicalPrices);
            
            $cci = 0;
            if ($meanDeviation != 0) {
                $cci = ($currentTypicalPrice - $smaTypicalPrice) / (0.015 * $meanDeviation);
            }
            
            $cciValues[] = [
                'date' => $priceData[$i]['date'] ?? $i,
                'cci' => $cci,
                'typical_price' => $currentTypicalPrice,
                'sma_typical_price' => $smaTypicalPrice,
                'mean_deviation' => $meanDeviation
            ];
        }

        $signal = $this->analyzeCCISignal($cciValues);

        return [
            'cci_values' => $cciValues,
            'current_cci' => end($cciValues)['cci'],
            'cci_signal' => $signal,
            'period' => $period,
            'overbought_level' => 100,
            'oversold_level' => -100
        ];
    }

    /**
     * Calculate Rate of Change (ROC)
     */
    public function calculateROC(array $priceData, int $period = 12): array
    {
        if (empty($priceData) || count($priceData) < $period + 1) {
            return [
                'roc_values' => [],
                'roc_signal' => 'neutral',
                'current_roc' => null
            ];
        }

        $rocValues = [];

        for ($i = $period; $i < count($priceData); $i++) {
            $currentClose = $priceData[$i]['close'];
            $previousClose = $priceData[$i - $period]['close'];
            
            $roc = 0;
            if ($previousClose != 0) {
                $roc = (($currentClose - $previousClose) / $previousClose) * 100;
            }
            
            $rocValues[] = [
                'date' => $priceData[$i]['date'] ?? $i,
                'roc' => $roc,
                'current_close' => $currentClose,
                'previous_close' => $previousClose,
                'price_change' => $currentClose - $previousClose
            ];
        }

        $signal = $this->analyzeROCSignal($rocValues);

        return [
            'roc_values' => $rocValues,
            'current_roc' => end($rocValues)['roc'],
            'roc_signal' => $signal,
            'period' => $period,
            'momentum_analysis' => $this->analyzeMomentum($rocValues)
        ];
    }

    /**
     * Calculate Aroon Oscillator
     */
    public function calculateAroon(array $priceData, int $period = 25): array
    {
        if (empty($priceData) || count($priceData) < $period) {
            return [
                'aroon_values' => [],
                'aroon_signal' => 'neutral',
                'current_aroon' => null
            ];
        }

        $aroonValues = [];

        for ($i = $period - 1; $i < count($priceData); $i++) {
            $periodData = array_slice($priceData, $i - $period + 1, $period);
            
            // Find periods since highest high and lowest low
            $highestHigh = -1;
            $lowestLow = PHP_FLOAT_MAX;
            $daysSinceHigh = 0;
            $daysSinceLow = 0;
            
            for ($j = 0; $j < count($periodData); $j++) {
                if ($periodData[$j]['high'] > $highestHigh) {
                    $highestHigh = $periodData[$j]['high'];
                    $daysSinceHigh = count($periodData) - 1 - $j;
                }
                if ($periodData[$j]['low'] < $lowestLow) {
                    $lowestLow = $periodData[$j]['low'];
                    $daysSinceLow = count($periodData) - 1 - $j;
                }
            }
            
            $aroonUp = (($period - $daysSinceHigh) / $period) * 100;
            $aroonDown = (($period - $daysSinceLow) / $period) * 100;
            $aroonOscillator = $aroonUp - $aroonDown;
            
            $aroonValues[] = [
                'date' => $priceData[$i]['date'] ?? $i,
                'aroon_up' => $aroonUp,
                'aroon_down' => $aroonDown,
                'aroon_oscillator' => $aroonOscillator,
                'days_since_high' => $daysSinceHigh,
                'days_since_low' => $daysSinceLow
            ];
        }

        $signal = $this->analyzeAroonSignal($aroonValues);

        return [
            'aroon_values' => $aroonValues,
            'current_aroon' => end($aroonValues),
            'aroon_signal' => $signal,
            'period' => $period,
            'trend_analysis' => $this->analyzeAroonTrend($aroonValues)
        ];
    }

    /**
     * Calculate Average True Range (ATR)
     */
    public function calculateATR(array $priceData, int $period = 14): array
    {
        if (empty($priceData) || count($priceData) < $period + 1) {
            return [
                'atr_values' => [],
                'atr_signal' => 'neutral',
                'current_atr' => null
            ];
        }

        $trueRanges = [];
        $atrValues = [];

        // Calculate True Range for each period
        for ($i = 1; $i < count($priceData); $i++) {
            $current = $priceData[$i];
            $previous = $priceData[$i - 1];
            
            $tr1 = $current['high'] - $current['low'];
            $tr2 = abs($current['high'] - $previous['close']);
            $tr3 = abs($current['low'] - $previous['close']);
            
            $trueRange = max($tr1, $tr2, $tr3);
            $trueRanges[] = $trueRange;
        }

        // Calculate ATR using smoothed average
        for ($i = $period - 1; $i < count($trueRanges); $i++) {
            if ($i == $period - 1) {
                // First ATR is simple average
                $atr = array_sum(array_slice($trueRanges, 0, $period)) / $period;
            } else {
                // Subsequent ATRs are smoothed
                $previousATR = end($atrValues)['atr'];
                $atr = (($previousATR * ($period - 1)) + $trueRanges[$i]) / $period;
            }
            
            $atrValues[] = [
                'date' => $priceData[$i + 1]['date'] ?? ($i + 1),
                'atr' => $atr,
                'true_range' => $trueRanges[$i],
                'volatility_percentile' => $this->calculateVolatilityPercentile($atr, array_slice($atrValues, -50))
            ];
        }

        $signal = $this->analyzeATRSignal($atrValues);

        return [
            'atr_values' => $atrValues,
            'current_atr' => end($atrValues)['atr'],
            'atr_signal' => $signal,
            'period' => $period,
            'volatility_analysis' => $this->analyzeVolatility($atrValues)
        ];
    }

    /**
     * Calculate Parabolic SAR
     */
    public function calculateParabolicSAR(array $priceData, float $accelerationFactor = 0.02, float $maxAcceleration = 0.20): array
    {
        if (empty($priceData) || count($priceData) < 2) {
            return [
                'sar_values' => [],
                'sar_signal' => 'neutral',
                'current_sar' => null
            ];
        }

        $sarValues = [];
        $isUptrend = true;
        $af = $accelerationFactor;
        $extremePoint = $priceData[0]['high'];
        $sar = $priceData[0]['low'];

        foreach ($priceData as $index => $data) {
            if ($index == 0) {
                $sarValues[] = [
                    'date' => $data['date'] ?? $index,
                    'sar' => $sar,
                    'trend' => $isUptrend ? 'bullish' : 'bearish',
                    'acceleration_factor' => $af,
                    'extreme_point' => $extremePoint
                ];
                continue;
            }

            $prevSar = $sar;
            
            if ($isUptrend) {
                $sar = $prevSar + $af * ($extremePoint - $prevSar);
                
                // SAR cannot be above the previous two lows
                if ($index > 1) {
                    $sar = min($sar, $priceData[$index - 1]['low'], $priceData[$index - 2]['low']);
                }
                
                if ($data['low'] <= $sar) {
                    // Trend reversal to downtrend
                    $isUptrend = false;
                    $sar = $extremePoint;
                    $extremePoint = $data['low'];
                    $af = $accelerationFactor;
                } else {
                    if ($data['high'] > $extremePoint) {
                        $extremePoint = $data['high'];
                        $af = min($af + $accelerationFactor, $maxAcceleration);
                    }
                }
            } else {
                $sar = $prevSar + $af * ($extremePoint - $prevSar);
                
                // SAR cannot be below the previous two highs
                if ($index > 1) {
                    $sar = max($sar, $priceData[$index - 1]['high'], $priceData[$index - 2]['high']);
                }
                
                if ($data['high'] >= $sar) {
                    // Trend reversal to uptrend
                    $isUptrend = true;
                    $sar = $extremePoint;
                    $extremePoint = $data['high'];
                    $af = $accelerationFactor;
                } else {
                    if ($data['low'] < $extremePoint) {
                        $extremePoint = $data['low'];
                        $af = min($af + $accelerationFactor, $maxAcceleration);
                    }
                }
            }
            
            $sarValues[] = [
                'date' => $data['date'] ?? $index,
                'sar' => $sar,
                'close' => $data['close'],
                'trend' => $isUptrend ? 'bullish' : 'bearish',
                'acceleration_factor' => $af,
                'extreme_point' => $extremePoint,
                'reversal_signal' => $this->detectSARReversal($sarValues, $index)
            ];
        }

        $signal = $this->analyzeSARSignal($sarValues);

        return [
            'sar_values' => $sarValues,
            'current_sar' => end($sarValues),
            'sar_signal' => $signal,
            'trend_changes' => $this->countTrendChanges($sarValues),
            'parameters' => [
                'acceleration_factor' => $accelerationFactor,
                'max_acceleration' => $maxAcceleration
            ]
        ];
    }

    /**
     * Analyze Williams %R signal
     */
    private function analyzeWilliamsRSignal(array $williamsRValues): string
    {
        if (empty($williamsRValues)) {
            return 'neutral';
        }

        $current = end($williamsRValues)['williams_r'];
        
        if ($current > -20) {
            return 'overbought';
        } elseif ($current < -80) {
            return 'oversold';
        } elseif ($current > -50) {
            return 'bullish';
        } elseif ($current < -50) {
            return 'bearish';
        }
        
        return 'neutral';
    }

    /**
     * Analyze CCI signal
     */
    private function analyzeCCISignal(array $cciValues): string
    {
        if (empty($cciValues)) {
            return 'neutral';
        }

        $current = end($cciValues)['cci'];
        
        if ($current > 100) {
            return 'overbought';
        } elseif ($current < -100) {
            return 'oversold';
        } elseif ($current > 0) {
            return 'bullish';
        } elseif ($current < 0) {
            return 'bearish';
        }
        
        return 'neutral';
    }

    /**
     * Analyze ROC signal
     */
    private function analyzeROCSignal(array $rocValues): string
    {
        if (empty($rocValues)) {
            return 'neutral';
        }

        $current = end($rocValues)['roc'];
        
        if ($current > 10) {
            return 'strong_bullish';
        } elseif ($current > 2) {
            return 'bullish';
        } elseif ($current < -10) {
            return 'strong_bearish';
        } elseif ($current < -2) {
            return 'bearish';
        }
        
        return 'neutral';
    }

    /**
     * Analyze momentum from ROC values
     */
    private function analyzeMomentum(array $rocValues): array
    {
        if (count($rocValues) < 5) {
            return ['momentum' => 'insufficient_data'];
        }

        $recent = array_slice($rocValues, -10);
        $rocValues_only = array_column($recent, 'roc');
        
        $increasing = 0;
        $decreasing = 0;
        
        for ($i = 1; $i < count($rocValues_only); $i++) {
            if ($rocValues_only[$i] > $rocValues_only[$i - 1]) {
                $increasing++;
            } elseif ($rocValues_only[$i] < $rocValues_only[$i - 1]) {
                $decreasing++;
            }
        }
        
        $avgROC = array_sum($rocValues_only) / count($rocValues_only);
        
        return [
            'average_roc' => round($avgROC, 2),
            'increasing_periods' => $increasing,
            'decreasing_periods' => $decreasing,
            'momentum_direction' => $increasing > $decreasing ? 'accelerating' : 'decelerating',
            'momentum_strength' => abs($avgROC)
        ];
    }

    /**
     * Analyze Aroon signal
     */
    private function analyzeAroonSignal(array $aroonValues): string
    {
        if (empty($aroonValues)) {
            return 'neutral';
        }

        $current = end($aroonValues);
        $aroonUp = $current['aroon_up'];
        $aroonDown = $current['aroon_down'];
        $oscillator = $current['aroon_oscillator'];
        
        if ($aroonUp > 70 && $aroonDown < 30) {
            return 'strong_uptrend';
        } elseif ($aroonUp < 30 && $aroonDown > 70) {
            return 'strong_downtrend';
        } elseif ($oscillator > 50) {
            return 'uptrend';
        } elseif ($oscillator < -50) {
            return 'downtrend';
        }
        
        return 'consolidation';
    }

    /**
     * Analyze Aroon trend
     */
    private function analyzeAroonTrend(array $aroonValues): array
    {
        if (count($aroonValues) < 5) {
            return ['trend' => 'insufficient_data'];
        }

        $recent = array_slice($aroonValues, -10);
        $oscillators = array_column($recent, 'aroon_oscillator');
        
        $trendStrength = end($oscillators);
        $trendConsistency = 0;
        
        $positiveCount = 0;
        foreach ($oscillators as $osc) {
            if ($osc > 0) $positiveCount++;
        }
        
        $trendConsistency = $positiveCount / count($oscillators);
        
        return [
            'trend_strength' => round($trendStrength, 2),
            'trend_consistency' => round($trendConsistency, 2),
            'trend_direction' => $trendStrength > 0 ? 'bullish' : 'bearish',
            'trend_stability' => $trendConsistency > 0.7 || $trendConsistency < 0.3 ? 'stable' : 'unstable'
        ];
    }

    /**
     * Calculate volatility percentile for ATR
     */
    private function calculateVolatilityPercentile(float $currentATR, array $historicalATR): float
    {
        if (empty($historicalATR)) {
            return 50; // Neutral percentile
        }

        $atrValues = array_column($historicalATR, 'atr');
        $atrValues[] = $currentATR;
        sort($atrValues);
        
        $position = array_search($currentATR, $atrValues);
        return ($position / (count($atrValues) - 1)) * 100;
    }

    /**
     * Analyze ATR signal
     */
    private function analyzeATRSignal(array $atrValues): string
    {
        if (count($atrValues) < 10) {
            return 'neutral';
        }

        $current = end($atrValues);
        $percentile = $current['volatility_percentile'];
        
        if ($percentile > 80) {
            return 'high_volatility';
        } elseif ($percentile < 20) {
            return 'low_volatility';
        } elseif ($percentile > 60) {
            return 'above_average_volatility';
        } elseif ($percentile < 40) {
            return 'below_average_volatility';
        }
        
        return 'average_volatility';
    }

    /**
     * Analyze volatility trends
     */
    private function analyzeVolatility(array $atrValues): array
    {
        if (count($atrValues) < 10) {
            return ['volatility_trend' => 'insufficient_data'];
        }

        $recent = array_slice($atrValues, -20);
        $atrOnly = array_column($recent, 'atr');
        
        $firstHalf = array_slice($atrOnly, 0, 10);
        $secondHalf = array_slice($atrOnly, -10);
        
        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);
        
        $volatilityChange = (($secondAvg - $firstAvg) / $firstAvg) * 100;
        
        return [
            'volatility_change_pct' => round($volatilityChange, 2),
            'current_avg_atr' => round($secondAvg, 4),
            'previous_avg_atr' => round($firstAvg, 4),
            'volatility_trend' => abs($volatilityChange) < 5 ? 'stable' : 
                                ($volatilityChange > 0 ? 'increasing' : 'decreasing')
        ];
    }

    /**
     * Detect SAR reversal signals
     */
    private function detectSARReversal(array $sarValues, int $currentIndex): bool
    {
        if (count($sarValues) < 2) {
            return false;
        }

        $current = end($sarValues);
        $previous = $sarValues[count($sarValues) - 2];
        
        return $current['trend'] !== $previous['trend'];
    }

    /**
     * Analyze SAR signal
     */
    private function analyzeSARSignal(array $sarValues): string
    {
        if (empty($sarValues)) {
            return 'neutral';
        }

        $current = end($sarValues);
        $trend = $current['trend'];
        $reversal = $current['reversal_signal'];
        
        if ($reversal) {
            return $trend === 'bullish' ? 'bullish_reversal' : 'bearish_reversal';
        }
        
        return $trend === 'bullish' ? 'uptrend_continue' : 'downtrend_continue';
    }

    /**
     * Count trend changes in SAR
     */
    private function countTrendChanges(array $sarValues): array
    {
        $changes = 0;
        $bullishPeriods = 0;
        $bearishPeriods = 0;
        
        for ($i = 1; $i < count($sarValues); $i++) {
            if ($sarValues[$i]['trend'] !== $sarValues[$i - 1]['trend']) {
                $changes++;
            }
            
            if ($sarValues[$i]['trend'] === 'bullish') {
                $bullishPeriods++;
            } else {
                $bearishPeriods++;
            }
        }
        
        return [
            'total_changes' => $changes,
            'bullish_periods' => $bullishPeriods,
            'bearish_periods' => $bearishPeriods,
            'trend_stability' => count($sarValues) > 0 ? 1 - ($changes / count($sarValues)) : 0
        ];
    }
}
