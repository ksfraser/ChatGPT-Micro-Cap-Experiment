<?php

namespace App\Services\Calculators;

use LupeCode\phpTraderNative\Trader;

/**
 * Missing Indicators Implementation using TA-Lib
 * Implements the high-priority indicators identified in the TA-Lib integration analysis
 */

/**
 * RSI (Relative Strength Index) Calculator
 * Most critical missing momentum indicator - measures overbought/oversold conditions
 */
class RSICalculator extends TALibCalculatorBase
{
    private const DEFAULT_PERIOD = 14;
    private const OVERBOUGHT_THRESHOLD = 70;
    private const OVERSOLD_THRESHOLD = 30;
    private const EXTREME_OVERBOUGHT = 80;
    private const EXTREME_OVERSOLD = 20;

    /**
     * Calculate RSI with comprehensive analysis
     */
    public function calculate(array $priceData, int $period = self::DEFAULT_PERIOD): array
    {
        $this->validatePriceData($priceData, ['close']);
        
        if (count($priceData) < $period + 1) {
            return $this->formatError('insufficient_data', "Need at least " . ($period + 1) . " data points for RSI($period)");
        }

        $closes = $this->extractArrays($priceData, ['close'])['close'];
        
        try {
            // Calculate RSI using TA-Lib
            $rsiValues = Trader::rsi($closes, $period);
            
            // Remove initial null values
            $validRsi = array_filter($rsiValues, function($value) {
                return $value !== null;
            });
            
            if (empty($validRsi)) {
                return $this->formatError('calculation_failed', 'RSI calculation produced no valid results');
            }

            // Combine with dates
            $dataKeys = array_keys($priceData);
            $resultData = [];
            $signals = [];
            $divergences = [];
            
            foreach ($rsiValues as $index => $rsi) {
                if ($rsi !== null && isset($dataKeys[$index])) {
                    $date = $dataKeys[$index];
                    $price = $priceData[$date]['close'];
                    
                    $resultData[$date] = [
                        'rsi' => round($rsi, 4),
                        'price' => $price,
                        'signal' => $this->determineSignal($rsi),
                        'condition' => $this->determineCondition($rsi)
                    ];
                    
                    // Track signals
                    $signal = $this->determineSignal($rsi);
                    if ($signal !== 'HOLD') {
                        $signals[] = [
                            'date' => $date,
                            'signal' => $signal,
                            'rsi' => round($rsi, 2),
                            'price' => $price,
                            'strength' => $this->calculateSignalStrength($rsi)
                        ];
                    }
                }
            }

            // Detect divergences
            $divergences = $this->detectDivergences($resultData, $priceData);
            
            return [
                'indicator' => 'RSI',
                'period' => $period,
                'data' => $resultData,
                'signals' => $signals,
                'divergences' => $divergences,
                'summary' => $this->generateSummary($resultData, $signals, $divergences),
                'calculation_engine' => 'TA-Lib'
            ];
            
        } catch (\Exception $e) {
            return $this->formatError('calculation_error', $e->getMessage());
        }
    }

    /**
     * Determine RSI trading signal
     */
    private function determineSignal(float $rsi): string
    {
        if ($rsi >= self::EXTREME_OVERBOUGHT) {
            return 'STRONG_SELL';
        } elseif ($rsi >= self::OVERBOUGHT_THRESHOLD) {
            return 'SELL';
        } elseif ($rsi <= self::EXTREME_OVERSOLD) {
            return 'STRONG_BUY';
        } elseif ($rsi <= self::OVERSOLD_THRESHOLD) {
            return 'BUY';
        } else {
            return 'HOLD';
        }
    }

    /**
     * Determine market condition based on RSI
     */
    private function determineCondition(float $rsi): string
    {
        if ($rsi >= self::OVERBOUGHT_THRESHOLD) {
            return 'OVERBOUGHT';
        } elseif ($rsi <= self::OVERSOLD_THRESHOLD) {
            return 'OVERSOLD';
        } else {
            return 'NEUTRAL';
        }
    }

    /**
     * Calculate signal strength (0-100)
     */
    private function calculateSignalStrength(float $rsi): int
    {
        if ($rsi >= self::EXTREME_OVERBOUGHT || $rsi <= self::EXTREME_OVERSOLD) {
            return 90;
        } elseif ($rsi >= self::OVERBOUGHT_THRESHOLD || $rsi <= self::OVERSOLD_THRESHOLD) {
            return 70;
        } else {
            return 30;
        }
    }

    /**
     * Detect RSI divergences with price
     */
    private function detectDivergences(array $rsiData, array $priceData): array
    {
        $divergences = [];
        $lookbackPeriod = 20;
        
        $dates = array_keys($rsiData);
        $dataCount = count($dates);
        
        if ($dataCount < $lookbackPeriod * 2) {
            return $divergences;
        }

        // Look for divergences in recent data
        for ($i = $lookbackPeriod; $i < $dataCount - $lookbackPeriod; $i++) {
            $currentDate = $dates[$i];
            $pastDate = $dates[$i - $lookbackPeriod];
            $futureDate = $dates[$i + $lookbackPeriod] ?? null;
            
            if (!$futureDate) continue;

            $currentRsi = $rsiData[$currentDate]['rsi'];
            $pastRsi = $rsiData[$pastDate]['rsi'];
            
            $currentPrice = $priceData[$currentDate]['close'];
            $pastPrice = $priceData[$pastDate]['close'];

            // Bullish divergence: Price makes lower low, RSI makes higher low
            if ($currentPrice < $pastPrice && $currentRsi > $pastRsi && $currentRsi < 40) {
                $divergences[] = [
                    'type' => 'BULLISH',
                    'date' => $currentDate,
                    'strength' => $this->calculateDivergenceStrength($currentRsi, $pastRsi, $currentPrice, $pastPrice),
                    'description' => 'Price lower low, RSI higher low - potential reversal up'
                ];
            }
            
            // Bearish divergence: Price makes higher high, RSI makes lower high
            if ($currentPrice > $pastPrice && $currentRsi < $pastRsi && $currentRsi > 60) {
                $divergences[] = [
                    'type' => 'BEARISH',
                    'date' => $currentDate,
                    'strength' => $this->calculateDivergenceStrength($currentRsi, $pastRsi, $currentPrice, $pastPrice),
                    'description' => 'Price higher high, RSI lower high - potential reversal down'
                ];
            }
        }

        return $divergences;
    }

    /**
     * Calculate divergence strength
     */
    private function calculateDivergenceStrength(float $currentRsi, float $pastRsi, float $currentPrice, float $pastPrice): int
    {
        $rsiDiff = abs($currentRsi - $pastRsi);
        $priceDiff = abs(($currentPrice - $pastPrice) / $pastPrice * 100);
        
        // Stronger divergence when RSI difference is large and price difference is small
        $strength = min(100, ($rsiDiff * 2) + (10 - min(10, $priceDiff)) * 5);
        
        return (int) round($strength);
    }

    /**
     * Generate analysis summary
     */
    private function generateSummary(array $rsiData, array $signals, array $divergences): array
    {
        if (empty($rsiData)) {
            return ['status' => 'no_data'];
        }

        $latestRsi = end($rsiData)['rsi'];
        $signalCount = count($signals);
        $divergenceCount = count($divergences);
        
        $recentSignals = array_slice($signals, -5);
        $recentDivergences = array_slice($divergences, -3);

        return [
            'current_rsi' => round($latestRsi, 2),
            'current_condition' => $this->determineCondition($latestRsi),
            'current_signal' => $this->determineSignal($latestRsi),
            'signal_strength' => $this->calculateSignalStrength($latestRsi),
            'total_signals' => $signalCount,
            'recent_signals' => $recentSignals,
            'total_divergences' => $divergenceCount,
            'recent_divergences' => $recentDivergences,
            'analysis' => $this->generateAnalysis($latestRsi, $recentSignals, $recentDivergences)
        ];
    }

    /**
     * Generate trading analysis
     */
    private function generateAnalysis(float $currentRsi, array $recentSignals, array $recentDivergences): string
    {
        $analysis = [];

        // Current condition analysis
        if ($currentRsi >= self::EXTREME_OVERBOUGHT) {
            $analysis[] = "Extremely overbought - high probability of pullback";
        } elseif ($currentRsi >= self::OVERBOUGHT_THRESHOLD) {
            $analysis[] = "Overbought conditions - consider taking profits";
        } elseif ($currentRsi <= self::EXTREME_OVERSOLD) {
            $analysis[] = "Extremely oversold - potential buying opportunity";
        } elseif ($currentRsi <= self::OVERSOLD_THRESHOLD) {
            $analysis[] = "Oversold conditions - watch for reversal signals";
        } else {
            $analysis[] = "RSI in neutral territory - trend continuation likely";
        }

        // Recent signals analysis
        if (!empty($recentSignals)) {
            $lastSignal = end($recentSignals);
            $analysis[] = "Latest signal: {$lastSignal['signal']} at RSI {$lastSignal['rsi']}";
        }

        // Divergence analysis
        if (!empty($recentDivergences)) {
            $lastDivergence = end($recentDivergences);
            $analysis[] = "Recent {$lastDivergence['type']} divergence detected - {$lastDivergence['description']}";
        }

        return implode('. ', $analysis);
    }
}

/**
 * MACD (Moving Average Convergence Divergence) Calculator
 * Essential trend-following oscillator
 */
class MACDCalculator extends TALibCalculatorBase
{
    private const DEFAULT_FAST_PERIOD = 12;
    private const DEFAULT_SLOW_PERIOD = 26;
    private const DEFAULT_SIGNAL_PERIOD = 9;

    /**
     * Calculate MACD with signal line and histogram
     */
    public function calculate(array $priceData, int $fastPeriod = self::DEFAULT_FAST_PERIOD, 
                             int $slowPeriod = self::DEFAULT_SLOW_PERIOD, 
                             int $signalPeriod = self::DEFAULT_SIGNAL_PERIOD): array
    {
        $this->validatePriceData($priceData, ['close']);
        
        $minRequired = max($fastPeriod, $slowPeriod) + $signalPeriod;
        if (count($priceData) < $minRequired) {
            return $this->formatError('insufficient_data', "Need at least {$minRequired} data points for MACD({$fastPeriod},{$slowPeriod},{$signalPeriod})");
        }

        $closes = $this->extractArrays($priceData, ['close'])['close'];
        
        try {
            // Calculate MACD using TA-Lib
            [$macdLine, $signalLine, $histogram] = Trader::macd($closes, $fastPeriod, $slowPeriod, $signalPeriod);
            
            // Combine with dates
            $dataKeys = array_keys($priceData);
            $resultData = [];
            $signals = [];
            $crossovers = [];
            
            foreach ($macdLine as $index => $macd) {
                if ($macd !== null && isset($dataKeys[$index])) {
                    $date = $dataKeys[$index];
                    $signal = $signalLine[$index];
                    $hist = $histogram[$index];
                    $price = $priceData[$date]['close'];
                    
                    $resultData[$date] = [
                        'macd' => round($macd, 6),
                        'signal' => round($signal, 6),
                        'histogram' => round($hist, 6),
                        'price' => $price,
                        'trend' => $this->determineTrend($macd, $signal, $hist),
                        'strength' => $this->calculateStrength($hist)
                    ];
                }
            }

            // Detect crossovers and generate signals
            $crossovers = $this->detectCrossovers($resultData);
            $signals = $this->generateSignals($resultData, $crossovers);
            
            return [
                'indicator' => 'MACD',
                'parameters' => [
                    'fast_period' => $fastPeriod,
                    'slow_period' => $slowPeriod,
                    'signal_period' => $signalPeriod
                ],
                'data' => $resultData,
                'crossovers' => $crossovers,
                'signals' => $signals,
                'summary' => $this->generateMACDSummary($resultData, $crossovers, $signals),
                'calculation_engine' => 'TA-Lib'
            ];
            
        } catch (\Exception $e) {
            return $this->formatError('calculation_error', $e->getMessage());
        }
    }

    /**
     * Determine trend based on MACD components
     */
    private function determineTrend(float $macd, float $signal, float $histogram): string
    {
        if ($macd > $signal && $histogram > 0) {
            return $histogram > 0.001 ? 'STRONG_BULLISH' : 'BULLISH';
        } elseif ($macd < $signal && $histogram < 0) {
            return $histogram < -0.001 ? 'STRONG_BEARISH' : 'BEARISH';
        } else {
            return 'NEUTRAL';
        }
    }

    /**
     * Calculate signal strength based on histogram magnitude
     */
    private function calculateStrength(float $histogram): int
    {
        $absHist = abs($histogram);
        
        if ($absHist > 0.01) return 90;
        if ($absHist > 0.005) return 70;
        if ($absHist > 0.001) return 50;
        return 30;
    }

    /**
     * Detect MACD crossovers
     */
    private function detectCrossovers(array $macdData): array
    {
        $crossovers = [];
        $dates = array_keys($macdData);
        
        for ($i = 1; $i < count($dates); $i++) {
            $currentDate = $dates[$i];
            $previousDate = $dates[$i - 1];
            
            $currentMacd = $macdData[$currentDate]['macd'];
            $currentSignal = $macdData[$currentDate]['signal'];
            $previousMacd = $macdData[$previousDate]['macd'];
            $previousSignal = $macdData[$previousDate]['signal'];
            
            // Bullish crossover: MACD crosses above signal line
            if ($previousMacd <= $previousSignal && $currentMacd > $currentSignal) {
                $crossovers[] = [
                    'type' => 'BULLISH',
                    'date' => $currentDate,
                    'macd' => round($currentMacd, 6),
                    'signal' => round($currentSignal, 6),
                    'strength' => $this->calculateCrossoverStrength($currentMacd, $currentSignal)
                ];
            }
            
            // Bearish crossover: MACD crosses below signal line  
            if ($previousMacd >= $previousSignal && $currentMacd < $currentSignal) {
                $crossovers[] = [
                    'type' => 'BEARISH',
                    'date' => $currentDate,
                    'macd' => round($currentMacd, 6),
                    'signal' => round($currentSignal, 6),
                    'strength' => $this->calculateCrossoverStrength($currentMacd, $currentSignal)
                ];
            }
        }
        
        return $crossovers;
    }

    /**
     * Calculate crossover strength
     */
    private function calculateCrossoverStrength(float $macd, float $signal): int
    {
        $separation = abs($macd - $signal);
        
        if ($separation > 0.01) return 90;
        if ($separation > 0.005) return 70;
        if ($separation > 0.001) return 50;
        return 30;
    }

    /**
     * Generate trading signals from MACD data
     */
    private function generateSignals(array $macdData, array $crossovers): array
    {
        $signals = [];
        
        foreach ($crossovers as $crossover) {
            $date = $crossover['date'];
            $signal = $crossover['type'] === 'BULLISH' ? 'BUY' : 'SELL';
            
            $signals[] = [
                'date' => $date,
                'signal' => $signal,
                'reason' => "MACD {$crossover['type']} crossover",
                'strength' => $crossover['strength'],
                'macd' => $crossover['macd'],
                'signal_line' => $crossover['signal']
            ];
        }
        
        return $signals;
    }

    /**
     * Generate MACD summary
     */
    private function generateMACDSummary(array $macdData, array $crossovers, array $signals): array
    {
        if (empty($macdData)) {
            return ['status' => 'no_data'];
        }

        $latest = end($macdData);
        $recentCrossovers = array_slice($crossovers, -5);
        $recentSignals = array_slice($signals, -5);
        
        return [
            'current_macd' => round($latest['macd'], 6),
            'current_signal' => round($latest['signal'], 6),
            'current_histogram' => round($latest['histogram'], 6),
            'current_trend' => $latest['trend'],
            'current_strength' => $latest['strength'],
            'total_crossovers' => count($crossovers),
            'recent_crossovers' => $recentCrossovers,
            'total_signals' => count($signals),
            'recent_signals' => $recentSignals,
            'analysis' => $this->generateMACDAnalysis($latest, $recentCrossovers)
        ];
    }

    /**
     * Generate MACD analysis
     */
    private function generateMACDAnalysis(array $latest, array $recentCrossovers): string
    {
        $analysis = [];
        
        $macd = $latest['macd'];
        $signal = $latest['signal'];
        $histogram = $latest['histogram'];
        
        // Position analysis
        if ($macd > $signal) {
            $analysis[] = "MACD above signal line - bullish momentum";
        } else {
            $analysis[] = "MACD below signal line - bearish momentum";
        }
        
        // Histogram analysis
        if ($histogram > 0) {
            $analysis[] = $histogram > 0.005 ? "Strong positive histogram" : "Weak positive histogram";
        } else {
            $analysis[] = $histogram < -0.005 ? "Strong negative histogram" : "Weak negative histogram";
        }
        
        // Recent crossover analysis
        if (!empty($recentCrossovers)) {
            $lastCrossover = end($recentCrossovers);
            $analysis[] = "Latest crossover: {$lastCrossover['type']} on {$lastCrossover['date']}";
        }
        
        return implode('. ', $analysis);
    }
}
