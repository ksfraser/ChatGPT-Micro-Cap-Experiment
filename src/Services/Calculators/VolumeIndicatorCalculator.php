<?php

namespace App\Services\Calculators;

/**
 * Single Responsibility: Calculate Volume-Based Technical Indicators
 * Follows SRP by handling only volume analysis calculations
 */
class VolumeIndicatorCalculator
{
    /**
     * Calculate On Balance Volume (OBV)
     */
    public function calculateOBV(array $priceData): array
    {
        if (empty($priceData)) {
            return [
                'obv_values' => [],
                'obv_signal' => 'neutral',
                'trend_strength' => 0
            ];
        }

        $obvValues = [];
        $obv = 0;
        $previousClose = null;

        foreach ($priceData as $index => $data) {
            $close = $data['close'];
            $volume = $data['volume'] ?? 0;

            if ($previousClose !== null) {
                if ($close > $previousClose) {
                    $obv += $volume; // Accumulation
                } elseif ($close < $previousClose) {
                    $obv -= $volume; // Distribution
                }
                // If close == previousClose, OBV stays the same
            }

            $obvValues[] = [
                'date' => $data['date'] ?? $index,
                'obv' => $obv,
                'volume' => $volume,
                'price_change' => $previousClose ? $close - $previousClose : 0
            ];

            $previousClose = $close;
        }

        // Analyze OBV trend
        $signal = $this->analyzeOBVTrend($obvValues);
        $trendStrength = $this->calculateOBVTrendStrength($obvValues);

        return [
            'obv_values' => $obvValues,
            'current_obv' => end($obvValues)['obv'],
            'obv_signal' => $signal,
            'trend_strength' => $trendStrength,
            'divergence_detected' => $this->detectOBVDivergence($priceData, $obvValues)
        ];
    }

    /**
     * Calculate Accumulation/Distribution Line (A/D Line)
     */
    public function calculateAccumulationDistribution(array $priceData): array
    {
        if (empty($priceData)) {
            return [
                'ad_values' => [],
                'ad_signal' => 'neutral',
                'money_flow_multiplier' => []
            ];
        }

        $adValues = [];
        $adLine = 0;

        foreach ($priceData as $index => $data) {
            $high = $data['high'];
            $low = $data['low'];
            $close = $data['close'];
            $volume = $data['volume'] ?? 0;

            // Calculate Money Flow Multiplier
            $hlRange = $high - $low;
            $moneyFlowMultiplier = 0;
            
            if ($hlRange > 0) {
                $moneyFlowMultiplier = (($close - $low) - ($high - $close)) / $hlRange;
            }

            // Calculate Money Flow Volume
            $moneyFlowVolume = $moneyFlowMultiplier * $volume;
            $adLine += $moneyFlowVolume;

            $adValues[] = [
                'date' => $data['date'] ?? $index,
                'ad_line' => $adLine,
                'money_flow_multiplier' => $moneyFlowMultiplier,
                'money_flow_volume' => $moneyFlowVolume,
                'volume' => $volume
            ];
        }

        $signal = $this->analyzeADTrend($adValues);

        return [
            'ad_values' => $adValues,
            'current_ad' => end($adValues)['ad_line'],
            'ad_signal' => $signal,
            'money_flow_analysis' => $this->analyzeMoneyFlow($adValues)
        ];
    }

    /**
     * Calculate Chaikin Money Flow (CMF)
     */
    public function calculateChaikinMoneyFlow(array $priceData, int $period = 21): array
    {
        if (empty($priceData) || count($priceData) < $period) {
            return [
                'cmf_values' => [],
                'cmf_signal' => 'neutral',
                'current_cmf' => null
            ];
        }

        $cmfValues = [];
        
        for ($i = $period - 1; $i < count($priceData); $i++) {
            $periodData = array_slice($priceData, $i - $period + 1, $period);
            
            $sumMoneyFlowVolume = 0;
            $sumVolume = 0;
            
            foreach ($periodData as $data) {
                $high = $data['high'];
                $low = $data['low'];
                $close = $data['close'];
                $volume = $data['volume'] ?? 0;
                
                $hlRange = $high - $low;
                $moneyFlowMultiplier = 0;
                
                if ($hlRange > 0) {
                    $moneyFlowMultiplier = (($close - $low) - ($high - $close)) / $hlRange;
                }
                
                $moneyFlowVolume = $moneyFlowMultiplier * $volume;
                $sumMoneyFlowVolume += $moneyFlowVolume;
                $sumVolume += $volume;
            }
            
            $cmf = $sumVolume > 0 ? $sumMoneyFlowVolume / $sumVolume : 0;
            
            $cmfValues[] = [
                'date' => $priceData[$i]['date'] ?? $i,
                'cmf' => $cmf,
                'period_volume' => $sumVolume,
                'money_flow_volume' => $sumMoneyFlowVolume
            ];
        }

        $signal = $this->analyzeCMFSignal($cmfValues);

        return [
            'cmf_values' => $cmfValues,
            'current_cmf' => end($cmfValues)['cmf'],
            'cmf_signal' => $signal,
            'period' => $period,
            'overbought_level' => 0.25,
            'oversold_level' => -0.25
        ];
    }

    /**
     * Calculate Volume Weighted Average Price (VWAP)
     */
    public function calculateVWAP(array $priceData): array
    {
        if (empty($priceData)) {
            return [
                'vwap_values' => [],
                'vwap_signal' => 'neutral',
                'current_vwap' => null
            ];
        }

        $vwapValues = [];
        $cumulativePriceVolume = 0;
        $cumulativeVolume = 0;

        foreach ($priceData as $index => $data) {
            $high = $data['high'];
            $low = $data['low'];
            $close = $data['close'];
            $volume = $data['volume'] ?? 0;

            // Typical Price
            $typicalPrice = ($high + $low + $close) / 3;
            $priceVolume = $typicalPrice * $volume;

            $cumulativePriceVolume += $priceVolume;
            $cumulativeVolume += $volume;

            $vwap = $cumulativeVolume > 0 ? $cumulativePriceVolume / $cumulativeVolume : $typicalPrice;

            $vwapValues[] = [
                'date' => $data['date'] ?? $index,
                'vwap' => $vwap,
                'typical_price' => $typicalPrice,
                'close' => $close,
                'volume' => $volume,
                'price_vs_vwap' => $close - $vwap,
                'price_vs_vwap_pct' => $vwap > 0 ? (($close - $vwap) / $vwap) * 100 : 0
            ];
        }

        $signal = $this->analyzeVWAPSignal($vwapValues);

        return [
            'vwap_values' => $vwapValues,
            'current_vwap' => end($vwapValues)['vwap'],
            'vwap_signal' => $signal,
            'volume_profile' => $this->analyzeVolumeProfile($priceData)
        ];
    }

    /**
     * Calculate Money Flow Index (MFI)
     */
    public function calculateMoneyFlowIndex(array $priceData, int $period = 14): array
    {
        if (empty($priceData) || count($priceData) < $period + 1) {
            return [
                'mfi_values' => [],
                'mfi_signal' => 'neutral',
                'current_mfi' => null
            ];
        }

        $mfiValues = [];
        
        for ($i = $period; $i < count($priceData); $i++) {
            $periodData = array_slice($priceData, $i - $period, $period + 1);
            
            $positiveMoneyFlow = 0;
            $negativeMoneyFlow = 0;
            
            for ($j = 1; $j < count($periodData); $j++) {
                $current = $periodData[$j];
                $previous = $periodData[$j - 1];
                
                $currentTypicalPrice = ($current['high'] + $current['low'] + $current['close']) / 3;
                $previousTypicalPrice = ($previous['high'] + $previous['low'] + $previous['close']) / 3;
                
                $rawMoneyFlow = $currentTypicalPrice * ($current['volume'] ?? 0);
                
                if ($currentTypicalPrice > $previousTypicalPrice) {
                    $positiveMoneyFlow += $rawMoneyFlow;
                } elseif ($currentTypicalPrice < $previousTypicalPrice) {
                    $negativeMoneyFlow += $rawMoneyFlow;
                }
            }
            
            $moneyFlowRatio = $negativeMoneyFlow > 0 ? $positiveMoneyFlow / $negativeMoneyFlow : 100;
            $mfi = 100 - (100 / (1 + $moneyFlowRatio));
            
            $mfiValues[] = [
                'date' => $priceData[$i]['date'] ?? $i,
                'mfi' => $mfi,
                'positive_money_flow' => $positiveMoneyFlow,
                'negative_money_flow' => $negativeMoneyFlow,
                'money_flow_ratio' => $moneyFlowRatio
            ];
        }

        $signal = $this->analyzeMFISignal($mfiValues);

        return [
            'mfi_values' => $mfiValues,
            'current_mfi' => end($mfiValues)['mfi'],
            'mfi_signal' => $signal,
            'period' => $period,
            'overbought_level' => 80,
            'oversold_level' => 20
        ];
    }

    /**
     * Analyze OBV trend
     */
    private function analyzeOBVTrend(array $obvValues): string
    {
        if (count($obvValues) < 10) {
            return 'neutral';
        }

        $recent = array_slice($obvValues, -10);
        $firstOBV = $recent[0]['obv'];
        $lastOBV = end($recent)['obv'];
        
        $change = $lastOBV - $firstOBV;
        $changePercent = $firstOBV != 0 ? abs($change / $firstOBV) : 0;
        
        if ($changePercent < 0.05) {
            return 'neutral';
        }
        
        return $change > 0 ? 'bullish' : 'bearish';
    }

    /**
     * Calculate OBV trend strength
     */
    private function calculateOBVTrendStrength(array $obvValues): float
    {
        if (count($obvValues) < 5) {
            return 0;
        }

        $recent = array_slice($obvValues, -20);
        $values = array_column($recent, 'obv');
        
        // Calculate correlation with time (trend strength)
        $n = count($values);
        $x = range(1, $n);
        
        $sumX = array_sum($x);
        $sumY = array_sum($values);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $values[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $values[$i] * $values[$i];
        }
        
        $correlation = 0;
        $denominator = sqrt(($n * $sumX2 - $sumX * $sumX) * ($n * $sumY2 - $sumY * $sumY));
        
        if ($denominator != 0) {
            $correlation = ($n * $sumXY - $sumX * $sumY) / $denominator;
        }
        
        return abs($correlation);
    }

    /**
     * Detect OBV price divergence
     */
    private function detectOBVDivergence(array $priceData, array $obvValues): array
    {
        if (count($priceData) < 20 || count($obvValues) < 20) {
            return ['detected' => false, 'type' => null];
        }

        $recentPrices = array_slice($priceData, -20);
        $recentOBV = array_slice($obvValues, -20);
        
        $priceChange = end($recentPrices)['close'] - $recentPrices[0]['close'];
        $obvChange = end($recentOBV)['obv'] - $recentOBV[0]['obv'];
        
        // Bullish divergence: price down, OBV up
        if ($priceChange < 0 && $obvChange > 0) {
            return ['detected' => true, 'type' => 'bullish'];
        }
        
        // Bearish divergence: price up, OBV down
        if ($priceChange > 0 && $obvChange < 0) {
            return ['detected' => true, 'type' => 'bearish'];
        }
        
        return ['detected' => false, 'type' => null];
    }

    /**
     * Analyze A/D Line trend
     */
    private function analyzeADTrend(array $adValues): string
    {
        if (count($adValues) < 10) {
            return 'neutral';
        }

        $recent = array_slice($adValues, -10);
        $firstAD = $recent[0]['ad_line'];
        $lastAD = end($recent)['ad_line'];
        
        $change = $lastAD - $firstAD;
        
        if (abs($change) < 1000) { // Threshold for significance
            return 'neutral';
        }
        
        return $change > 0 ? 'accumulation' : 'distribution';
    }

    /**
     * Analyze money flow patterns
     */
    private function analyzeMoneyFlow(array $adValues): array
    {
        if (count($adValues) < 5) {
            return ['pattern' => 'insufficient_data'];
        }

        $recent = array_slice($adValues, -10);
        $positiveFlows = 0;
        $negativeFlows = 0;
        
        foreach ($recent as $value) {
            if ($value['money_flow_volume'] > 0) {
                $positiveFlows++;
            } elseif ($value['money_flow_volume'] < 0) {
                $negativeFlows++;
            }
        }
        
        $ratio = $negativeFlows > 0 ? $positiveFlows / $negativeFlows : $positiveFlows;
        
        return [
            'positive_flows' => $positiveFlows,
            'negative_flows' => $negativeFlows,
            'flow_ratio' => round($ratio, 2),
            'pattern' => $ratio > 1.5 ? 'accumulation' : ($ratio < 0.67 ? 'distribution' : 'balanced')
        ];
    }

    /**
     * Analyze CMF signal
     */
    private function analyzeCMFSignal(array $cmfValues): string
    {
        if (empty($cmfValues)) {
            return 'neutral';
        }

        $currentCMF = end($cmfValues)['cmf'];
        
        if ($currentCMF > 0.25) {
            return 'strong_bullish';
        } elseif ($currentCMF > 0.1) {
            return 'bullish';
        } elseif ($currentCMF < -0.25) {
            return 'strong_bearish';
        } elseif ($currentCMF < -0.1) {
            return 'bearish';
        }
        
        return 'neutral';
    }

    /**
     * Analyze VWAP signal
     */
    private function analyzeVWAPSignal(array $vwapValues): string
    {
        if (empty($vwapValues)) {
            return 'neutral';
        }

        $current = end($vwapValues);
        $priceVsVwap = $current['price_vs_vwap_pct'];
        
        if ($priceVsVwap > 2) {
            return 'strong_above_vwap';
        } elseif ($priceVsVwap > 0.5) {
            return 'above_vwap';
        } elseif ($priceVsVwap < -2) {
            return 'strong_below_vwap';
        } elseif ($priceVsVwap < -0.5) {
            return 'below_vwap';
        }
        
        return 'near_vwap';
    }

    /**
     * Analyze volume profile
     */
    private function analyzeVolumeProfile(array $priceData): array
    {
        $totalVolume = array_sum(array_column($priceData, 'volume'));
        $avgVolume = $totalVolume / count($priceData);
        
        $highVolumeCount = 0;
        $lowVolumeCount = 0;
        
        foreach ($priceData as $data) {
            $volume = $data['volume'] ?? 0;
            if ($volume > $avgVolume * 1.5) {
                $highVolumeCount++;
            } elseif ($volume < $avgVolume * 0.5) {
                $lowVolumeCount++;
            }
        }
        
        return [
            'total_volume' => $totalVolume,
            'average_volume' => round($avgVolume),
            'high_volume_periods' => $highVolumeCount,
            'low_volume_periods' => $lowVolumeCount,
            'volume_consistency' => 1 - (($highVolumeCount + $lowVolumeCount) / count($priceData))
        ];
    }

    /**
     * Analyze MFI signal
     */
    private function analyzeMFISignal(array $mfiValues): string
    {
        if (empty($mfiValues)) {
            return 'neutral';
        }

        $currentMFI = end($mfiValues)['mfi'];
        
        if ($currentMFI > 80) {
            return 'overbought';
        } elseif ($currentMFI > 70) {
            return 'bullish';
        } elseif ($currentMFI < 20) {
            return 'oversold';
        } elseif ($currentMFI < 30) {
            return 'bearish';
        }
        
        return 'neutral';
    }
}
