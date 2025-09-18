<?php

namespace App\Services;

use App\Models\Quote;
use Illuminate\Support\Collection;

/**
 * Advanced Technical Indicators Service
 * 
 * Implements sophisticated technical analysis algorithms found in legacy financial software
 * including BeanCounter, GeniusTrader, and TsInvest systems.
 */
class AdvancedTechnicalIndicators
{
    /**
     * Calculate Average Directional Index (ADX)
     * Measures trend strength regardless of direction
     */
    public function calculateADX(Collection $data, int $period = 14): array
    {
        if ($data->count() < $period + 1) {
            return [];
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
            
            $plusDM = ($upMove > $downMove && $upMove > 0) ? $upMove : 0;
            $minusDM = ($downMove > $upMove && $downMove > 0) ? $downMove : 0;
            
            $plusDMs[] = $plusDM;
            $minusDMs[] = $minusDM;
        }
        
        $adxValues = [];
        
        // Calculate smoothed values and ADX
        for ($i = $period - 1; $i < count($trueRanges); $i++) {
            $atr = array_sum(array_slice($trueRanges, $i - $period + 1, $period)) / $period;
            $plusDI = (array_sum(array_slice($plusDMs, $i - $period + 1, $period)) / $period) / $atr * 100;
            $minusDI = (array_sum(array_slice($minusDMs, $i - $period + 1, $period)) / $period) / $atr * 100;
            
            $dx = abs($plusDI - $minusDI) / ($plusDI + $minusDI) * 100;
            $adxValues[] = [
                'date' => $data[$i + 1]->date,
                'adx' => $dx,
                'plus_di' => $plusDI,
                'minus_di' => $minusDI
            ];
        }
        
        return $adxValues;
    }

    /**
     * Calculate Bollinger Bands
     * Volatility bands based on standard deviation
     */
    public function calculateBollingerBands(Collection $data, int $period = 20, float $stdDev = 2): array
    {
        if ($data->count() < $period) {
            return [];
        }

        $bollingerBands = [];
        
        for ($i = $period - 1; $i < $data->count(); $i++) {
            $subset = $data->slice($i - $period + 1, $period);
            $closes = $subset->pluck('close')->toArray();
            
            $sma = array_sum($closes) / $period;
            $variance = array_sum(array_map(function($close) use ($sma) {
                return pow($close - $sma, 2);
            }, $closes)) / $period;
            
            $standardDeviation = sqrt($variance);
            
            $bollingerBands[] = [
                'date' => $data[$i]->date,
                'middle' => $sma,
                'upper' => $sma + ($stdDev * $standardDeviation),
                'lower' => $sma - ($stdDev * $standardDeviation),
                'bandwidth' => (2 * $stdDev * $standardDeviation) / $sma * 100
            ];
        }
        
        return $bollingerBands;
    }

    /**
     * Calculate Stochastic Oscillator
     * Momentum indicator comparing closing price to price range
     */
    public function calculateStochastic(Collection $data, int $kPeriod = 14, int $dPeriod = 3): array
    {
        if ($data->count() < $kPeriod) {
            return [];
        }

        $kValues = [];
        
        // Calculate %K values
        for ($i = $kPeriod - 1; $i < $data->count(); $i++) {
            $subset = $data->slice($i - $kPeriod + 1, $kPeriod);
            $current = $data[$i];
            
            $highest = $subset->max('high');
            $lowest = $subset->min('low');
            
            $k = (($current->close - $lowest) / ($highest - $lowest)) * 100;
            $kValues[] = [
                'date' => $current->date,
                'k' => $k
            ];
        }
        
        // Calculate %D values (SMA of %K)
        $stochasticValues = [];
        for ($i = $dPeriod - 1; $i < count($kValues); $i++) {
            $dSubset = array_slice($kValues, $i - $dPeriod + 1, $dPeriod);
            $d = array_sum(array_column($dSubset, 'k')) / $dPeriod;
            
            $stochasticValues[] = [
                'date' => $kValues[$i]['date'],
                'k' => $kValues[$i]['k'],
                'd' => $d
            ];
        }
        
        return $stochasticValues;
    }

    /**
     * Calculate Williams %R
     * Momentum oscillator measuring overbought/oversold levels
     */
    public function calculateWilliamsR(Collection $data, int $period = 14): array
    {
        if ($data->count() < $period) {
            return [];
        }

        $williamsR = [];
        
        for ($i = $period - 1; $i < $data->count(); $i++) {
            $subset = $data->slice($i - $period + 1, $period);
            $current = $data[$i];
            
            $highest = $subset->max('high');
            $lowest = $subset->min('low');
            
            $r = (($highest - $current->close) / ($highest - $lowest)) * -100;
            
            $williamsR[] = [
                'date' => $current->date,
                'williams_r' => $r
            ];
        }
        
        return $williamsR;
    }

    /**
     * Calculate Commodity Channel Index (CCI)
     * Momentum-based oscillator used to determine overbought/oversold conditions
     */
    public function calculateCCI(Collection $data, int $period = 20): array
    {
        if ($data->count() < $period) {
            return [];
        }

        $cciValues = [];
        
        for ($i = $period - 1; $i < $data->count(); $i++) {
            $subset = $data->slice($i - $period + 1, $period);
            
            // Calculate Typical Price for each period
            $typicalPrices = $subset->map(function($quote) {
                return ($quote->high + $quote->low + $quote->close) / 3;
            })->toArray();
            
            $smaTP = array_sum($typicalPrices) / $period;
            $currentTP = ($data[$i]->high + $data[$i]->low + $data[$i]->close) / 3;
            
            // Calculate Mean Deviation
            $meanDeviation = array_sum(array_map(function($tp) use ($smaTP) {
                return abs($tp - $smaTP);
            }, $typicalPrices)) / $period;
            
            $cci = ($currentTP - $smaTP) / (0.015 * $meanDeviation);
            
            $cciValues[] = [
                'date' => $data[$i]->date,
                'cci' => $cci
            ];
        }
        
        return $cciValues;
    }

    /**
     * Calculate Rate of Change (ROC)
     * Momentum oscillator measuring percentage change
     */
    public function calculateROC(Collection $data, int $period = 10): array
    {
        if ($data->count() < $period + 1) {
            return [];
        }

        $rocValues = [];
        
        for ($i = $period; $i < $data->count(); $i++) {
            $current = $data[$i]->close;
            $previous = $data[$i - $period]->close;
            
            $roc = (($current - $previous) / $previous) * 100;
            
            $rocValues[] = [
                'date' => $data[$i]->date,
                'roc' => $roc
            ];
        }
        
        return $rocValues;
    }

    /**
     * Calculate On Balance Volume (OBV)
     * Volume indicator that shows crowd sentiment
     */
    public function calculateOBV(Collection $data): array
    {
        if ($data->count() < 2) {
            return [];
        }

        $obvValues = [];
        $obv = 0;
        
        for ($i = 1; $i < $data->count(); $i++) {
            $current = $data[$i];
            $previous = $data[$i - 1];
            
            if ($current->close > $previous->close) {
                $obv += $current->volume;
            } elseif ($current->close < $previous->close) {
                $obv -= $current->volume;
            }
            // If close is equal, OBV remains unchanged
            
            $obvValues[] = [
                'date' => $current->date,
                'obv' => $obv
            ];
        }
        
        return $obvValues;
    }

    /**
     * Calculate Parabolic SAR
     * Trend-following indicator that provides stop and reverse points
     */
    public function calculateParabolicSAR(Collection $data, float $acceleration = 0.02, float $maximum = 0.20): array
    {
        if ($data->count() < 2) {
            return [];
        }

        $sarValues = [];
        $af = $acceleration;
        $ep = $data[0]->high; // Extreme Point
        $sar = $data[0]->low;
        $uptrend = true;
        
        for ($i = 1; $i < $data->count(); $i++) {
            $current = $data[$i];
            $previous = $data[$i - 1];
            
            if ($uptrend) {
                $sar = $sar + $af * ($ep - $sar);
                
                // Check for trend reversal
                if ($current->low <= $sar) {
                    $uptrend = false;
                    $sar = $ep;
                    $ep = $current->low;
                    $af = $acceleration;
                } else {
                    // Update extreme point and acceleration factor
                    if ($current->high > $ep) {
                        $ep = $current->high;
                        $af = min($af + $acceleration, $maximum);
                    }
                }
            } else {
                $sar = $sar + $af * ($ep - $sar);
                
                // Check for trend reversal
                if ($current->high >= $sar) {
                    $uptrend = true;
                    $sar = $ep;
                    $ep = $current->high;
                    $af = $acceleration;
                } else {
                    // Update extreme point and acceleration factor
                    if ($current->low < $ep) {
                        $ep = $current->low;
                        $af = min($af + $acceleration, $maximum);
                    }
                }
            }
            
            $sarValues[] = [
                'date' => $current->date,
                'sar' => $sar,
                'trend' => $uptrend ? 'up' : 'down'
            ];
        }
        
        return $sarValues;
    }

    /**
     * Calculate Shannon Probability
     * Information theory-based probability analysis from TsInvest system
     */
    public function calculateShannonProbability(Collection $data, int $window = 20): array
    {
        if ($data->count() < $window + 1) {
            return [];
        }

        $shannonValues = [];
        
        for ($i = $window; $i < $data->count(); $i++) {
            $subset = $data->slice($i - $window, $window);
            $returns = [];
            
            // Calculate returns
            for ($j = 1; $j < $subset->count(); $j++) {
                $currentPrice = $subset[$j]->close;
                $previousPrice = $subset[$j - 1]->close;
                $returns[] = ($currentPrice - $previousPrice) / $previousPrice;
            }
            
            if (empty($returns)) continue;
            
            $meanReturn = array_sum($returns) / count($returns);
            $variance = array_sum(array_map(function($return) use ($meanReturn) {
                return pow($return - $meanReturn, 2);
            }, $returns)) / count($returns);
            
            $stdDev = sqrt($variance);
            
            // Shannon probability calculation
            $upMoves = array_filter($returns, function($return) { return $return > 0; });
            $probability = count($upMoves) / count($returns);
            
            // Estimate accuracy based on sample size
            $accuracy = $this->estimateProbabilityAccuracy($probability, count($returns));
            
            $shannonValues[] = [
                'date' => $data[$i]->date,
                'probability' => $probability,
                'accuracy' => $accuracy,
                'mean_return' => $meanReturn,
                'volatility' => $stdDev,
                'effective_probability' => max(0.5, $probability - (1.96 * sqrt($probability * (1 - $probability) / count($returns))))
            ];
        }
        
        return $shannonValues;
    }

    /**
     * Calculate Aroon Oscillator
     * Identifies trend changes and measures trend strength
     */
    public function calculateAroon(Collection $data, int $period = 25): array
    {
        if ($data->count() < $period) {
            return [];
        }

        $aroonValues = [];
        
        for ($i = $period - 1; $i < $data->count(); $i++) {
            $subset = $data->slice($i - $period + 1, $period);
            
            $highestHigh = $subset->max('high');
            $lowestLow = $subset->min('low');
            
            // Find periods since highest high and lowest low
            $periodsSinceHigh = 0;
            $periodsSinceLow = 0;
            
            for ($j = $subset->count() - 1; $j >= 0; $j--) {
                if ($subset[$j]->high == $highestHigh && $periodsSinceHigh == 0) {
                    $periodsSinceHigh = $subset->count() - 1 - $j;
                }
                if ($subset[$j]->low == $lowestLow && $periodsSinceLow == 0) {
                    $periodsSinceLow = $subset->count() - 1 - $j;
                }
            }
            
            $aroonUp = (($period - $periodsSinceHigh) / $period) * 100;
            $aroonDown = (($period - $periodsSinceLow) / $period) * 100;
            $aroonOscillator = $aroonUp - $aroonDown;
            
            $aroonValues[] = [
                'date' => $data[$i]->date,
                'aroon_up' => $aroonUp,
                'aroon_down' => $aroonDown,
                'aroon_oscillator' => $aroonOscillator
            ];
        }
        
        return $aroonValues;
    }

    /**
     * Estimate accuracy of Shannon probability calculation
     * Based on sample size and statistical confidence
     */
    private function estimateProbabilityAccuracy(float $probability, int $sampleSize): float
    {
        if ($sampleSize <= 0) return 0;
        
        // Standard error calculation
        $standardError = sqrt(($probability * (1 - $probability)) / $sampleSize);
        
        // 95% confidence interval
        $marginOfError = 1.96 * $standardError;
        
        // Return accuracy as percentage
        return max(0, min(100, (1 - $marginOfError) * 100));
    }

    /**
     * Calculate Average True Range (ATR)
     * Volatility indicator measuring market volatility
     */
    public function calculateATR(Collection $data, int $period = 14): array
    {
        if ($data->count() < $period + 1) {
            return [];
        }

        $trueRanges = [];
        
        // Calculate True Ranges
        for ($i = 1; $i < $data->count(); $i++) {
            $current = $data[$i];
            $previous = $data[$i - 1];
            
            $tr1 = $current->high - $current->low;
            $tr2 = abs($current->high - $previous->close);
            $tr3 = abs($current->low - $previous->close);
            
            $trueRanges[] = max($tr1, $tr2, $tr3);
        }
        
        $atrValues = [];
        
        // Calculate ATR using simple moving average
        for ($i = $period - 1; $i < count($trueRanges); $i++) {
            $atr = array_sum(array_slice($trueRanges, $i - $period + 1, $period)) / $period;
            
            $atrValues[] = [
                'date' => $data[$i + 1]->date,
                'atr' => $atr,
                'true_range' => $trueRanges[$i]
            ];
        }
        
        return $atrValues;
    }
}
