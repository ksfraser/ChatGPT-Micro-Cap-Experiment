<?php

namespace App\Services\Calculators;

use LupeCode\phpTraderNative\Trader;

/**
 * Comprehensive Moving Average Calculator
 * Implements multiple MA variants: SMA, EMA, WMA, DEMA, TEMA, TRIMA, KAMA, MAMA
 */
class MovingAverageCalculator extends TALibCalculatorBase
{
    public const MA_TYPES = [
        'SMA' => 'Simple Moving Average',
        'EMA' => 'Exponential Moving Average', 
        'WMA' => 'Weighted Moving Average',
        'DEMA' => 'Double Exponential Moving Average',
        'TEMA' => 'Triple Exponential Moving Average',
        'TRIMA' => 'Triangular Moving Average',
        'KAMA' => 'Kaufman Adaptive Moving Average',
        'MAMA' => 'MESA Adaptive Moving Average'
    ];

    /**
     * Calculate multiple moving averages for comprehensive analysis
     */
    public function calculateAll(array $priceData, array $periods = [10, 20, 50, 200]): array
    {
        $this->validatePriceData($priceData, ['close']);
        
        $results = [];
        
        foreach (self::MA_TYPES as $type => $name) {
            foreach ($periods as $period) {
                try {
                    $maData = $this->calculateSingle($priceData, $type, $period);
                    $results["{$type}_{$period}"] = $maData;
                } catch (\Exception $e) {
                    $results["{$type}_{$period}"] = ['error' => $e->getMessage()];
                }
            }
        }
        
        // Calculate crossover signals between different MAs
        $crossovers = $this->detectMACrossovers($results, $periods);
        
        return [
            'indicator' => 'MovingAverages',
            'periods' => $periods,
            'types' => array_keys(self::MA_TYPES),
            'data' => $results,
            'crossovers' => $crossovers,
            'summary' => $this->generateMASummary($results, $crossovers),
            'calculation_engine' => 'TA-Lib'
        ];
    }

    /**
     * Calculate single moving average
     */
    public function calculateSingle(array $priceData, string $type, int $period): array
    {
        $this->validatePriceData($priceData, ['close']);
        
        if (count($priceData) < $period) {
            return $this->formatError('insufficient_data', "Need at least {$period} data points for {$type}({$period})");
        }

        $closes = $this->extractArrays($priceData, ['close'])['close'];
        
        try {
            $maValues = $this->calculateByType($closes, $type, $period);
            
            // Combine with dates and analyze
            $dataKeys = array_keys($priceData);
            $resultData = [];
            $signals = [];
            
            foreach ($maValues as $index => $ma) {
                if ($ma !== null && isset($dataKeys[$index])) {
                    $date = $dataKeys[$index];
                    $price = $priceData[$date]['close'];
                    
                    $resultData[$date] = [
                        'ma' => round($ma, 4),
                        'price' => $price,
                        'position' => $price > $ma ? 'ABOVE' : 'BELOW',
                        'distance_pct' => round((($price - $ma) / $ma) * 100, 2),
                        'signal' => $this->determinePriceMASignal($price, $ma)
                    ];
                    
                    // Track significant signals
                    $signal = $this->determinePriceMASignal($price, $ma);
                    if ($signal !== 'HOLD') {
                        $signals[] = [
                            'date' => $date,
                            'signal' => $signal,
                            'ma_value' => round($ma, 4),
                            'price' => $price,
                            'distance_pct' => round((($price - $ma) / $ma) * 100, 2)
                        ];
                    }
                }
            }

            return [
                'type' => $type,
                'period' => $period,
                'data' => $resultData,
                'signals' => $signals,
                'summary' => $this->generateSingleMASummary($resultData, $type, $period)
            ];
            
        } catch (\Exception $e) {
            return $this->formatError('calculation_error', $e->getMessage());
        }
    }

    /**
     * Calculate MA by type using appropriate TA-Lib function
     */
    private function calculateByType(array $closes, string $type, int $period): array
    {
        switch ($type) {
            case 'SMA':
                return Trader::sma($closes, $period);
            case 'EMA':
                return Trader::ema($closes, $period);
            case 'WMA':
                return Trader::wma($closes, $period);
            case 'DEMA':
                return Trader::dema($closes, $period);
            case 'TEMA':
                return Trader::tema($closes, $period);
            case 'TRIMA':
                return Trader::trima($closes, $period);
            case 'KAMA':
                return Trader::kama($closes, $period);
            case 'MAMA':
                // MAMA returns both MAMA and FAMA, we take MAMA
                [$mama, $fama] = Trader::mama($closes);
                return $mama;
            default:
                throw new \InvalidArgumentException("Unsupported MA type: {$type}");
        }
    }

    /**
     * Determine signal based on price vs MA relationship
     */
    private function determinePriceMASignal(float $price, float $ma): string
    {
        $distancePct = (($price - $ma) / $ma) * 100;
        
        if ($distancePct > 5) return 'STRONG_BUY';
        if ($distancePct > 2) return 'BUY';
        if ($distancePct < -5) return 'STRONG_SELL';
        if ($distancePct < -2) return 'SELL';
        
        return 'HOLD';
    }

    /**
     * Detect crossovers between different moving averages
     */
    private function detectMACrossovers(array $results, array $periods): array
    {
        $crossovers = [];
        
        // Check SMA crossovers for common period combinations
        $combinations = [
            ['SMA_10', 'SMA_20'],
            ['SMA_20', 'SMA_50'],
            ['SMA_50', 'SMA_200'],
            ['EMA_12', 'EMA_26']
        ];
        
        foreach ($combinations as [$fastMA, $slowMA]) {
            if (isset($results[$fastMA]['data']) && isset($results[$slowMA]['data'])) {
                $crossoverSignals = $this->findCrossovers(
                    $results[$fastMA]['data'], 
                    $results[$slowMA]['data'],
                    $fastMA,
                    $slowMA
                );
                $crossovers = array_merge($crossovers, $crossoverSignals);
            }
        }
        
        return $crossovers;
    }

    /**
     * Find crossover points between two MAs
     */
    private function findCrossovers(array $fastData, array $slowData, string $fastName, string $slowName): array
    {
        $crossovers = [];
        $commonDates = array_intersect(array_keys($fastData), array_keys($slowData));
        $commonDates = array_values($commonDates);
        
        for ($i = 1; $i < count($commonDates); $i++) {
            $currentDate = $commonDates[$i];
            $previousDate = $commonDates[$i - 1];
            
            $currentFast = $fastData[$currentDate]['ma'];
            $currentSlow = $slowData[$currentDate]['ma'];
            $previousFast = $fastData[$previousDate]['ma'];
            $previousSlow = $slowData[$previousDate]['ma'];
            
            // Golden Cross: Fast MA crosses above Slow MA
            if ($previousFast <= $previousSlow && $currentFast > $currentSlow) {
                $crossovers[] = [
                    'type' => 'GOLDEN_CROSS',
                    'signal' => 'BUY',
                    'date' => $currentDate,
                    'fast_ma' => $fastName,
                    'slow_ma' => $slowName,
                    'fast_value' => round($currentFast, 4),
                    'slow_value' => round($currentSlow, 4),
                    'strength' => $this->calculateCrossoverStrength($currentFast, $currentSlow)
                ];
            }
            
            // Death Cross: Fast MA crosses below Slow MA
            if ($previousFast >= $previousSlow && $currentFast < $currentSlow) {
                $crossovers[] = [
                    'type' => 'DEATH_CROSS',
                    'signal' => 'SELL',
                    'date' => $currentDate,
                    'fast_ma' => $fastName,
                    'slow_ma' => $slowName,
                    'fast_value' => round($currentFast, 4),
                    'slow_value' => round($currentSlow, 4),
                    'strength' => $this->calculateCrossoverStrength($currentFast, $currentSlow)
                ];
            }
        }
        
        return $crossovers;
    }

    /**
     * Calculate crossover strength
     */
    private function calculateCrossoverStrength(float $fast, float $slow): int
    {
        $separation = abs($fast - $slow);
        $average = ($fast + $slow) / 2;
        $separationPct = ($separation / $average) * 100;
        
        if ($separationPct > 5) return 90;
        if ($separationPct > 2) return 70;
        if ($separationPct > 1) return 50;
        return 30;
    }

    /**
     * Generate summary for single MA
     */
    private function generateSingleMASummary(array $resultData, string $type, int $period): array
    {
        if (empty($resultData)) {
            return ['status' => 'no_data'];
        }

        $latest = end($resultData);
        $signals = array_filter($resultData, function($data) {
            return $data['signal'] !== 'HOLD';
        });
        
        return [
            'current_ma' => $latest['ma'],
            'current_price' => $latest['price'],
            'current_position' => $latest['position'],
            'current_distance_pct' => $latest['distance_pct'],
            'current_signal' => $latest['signal'],
            'total_signals' => count($signals),
            'trend' => $this->determineTrend($resultData)
        ];
    }

    /**
     * Determine overall trend from MA data
     */
    private function determineTrend(array $resultData): string
    {
        $recent = array_slice($resultData, -10, 10, true);
        $aboveCount = 0;
        
        foreach ($recent as $data) {
            if ($data['position'] === 'ABOVE') {
                $aboveCount++;
            }
        }
        
        $abovePct = ($aboveCount / count($recent)) * 100;
        
        if ($abovePct >= 80) return 'STRONG_BULLISH';
        if ($abovePct >= 60) return 'BULLISH';
        if ($abovePct <= 20) return 'STRONG_BEARISH';
        if ($abovePct <= 40) return 'BEARISH';
        return 'NEUTRAL';
    }

    /**
     * Generate comprehensive MA summary
     */
    private function generateMASummary(array $results, array $crossovers): array
    {
        $summary = [
            'total_indicators' => count($results),
            'successful_calculations' => 0,
            'failed_calculations' => 0,
            'total_crossovers' => count($crossovers),
            'recent_crossovers' => array_slice($crossovers, -5),
            'ma_alignment' => $this->analyzeMAAlignment($results),
            'trend_consensus' => $this->calculateTrendConsensus($results)
        ];
        
        foreach ($results as $result) {
            if (isset($result['error'])) {
                $summary['failed_calculations']++;
            } else {
                $summary['successful_calculations']++;
            }
        }
        
        return $summary;
    }

    /**
     * Analyze moving average alignment
     */
    private function analyzeMAAlignment(array $results): array
    {
        // Check if shorter-term MAs are above longer-term MAs (bullish alignment)
        $smaResults = [];
        foreach ($results as $key => $result) {
            if (strpos($key, 'SMA_') === 0 && !isset($result['error'])) {
                $period = (int) str_replace('SMA_', '', $key);
                $latest = end($result['data']);
                $smaResults[$period] = $latest['ma'];
            }
        }
        
        ksort($smaResults); // Sort by period
        $periods = array_keys($smaResults);
        $values = array_values($smaResults);
        
        $bullishAlignment = true;
        $bearishAlignment = true;
        
        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i-1] <= $values[$i]) {
                $bullishAlignment = false;
            }
            if ($values[$i-1] >= $values[$i]) {
                $bearishAlignment = false;
            }
        }
        
        return [
            'bullish_alignment' => $bullishAlignment,
            'bearish_alignment' => $bearishAlignment,
            'sma_values' => $smaResults,
            'alignment_score' => $this->calculateAlignmentScore($values)
        ];
    }

    /**
     * Calculate alignment score (0-100)
     */
    private function calculateAlignmentScore(array $values): int
    {
        if (count($values) < 2) return 50;
        
        $alignmentCount = 0;
        $totalComparisons = count($values) - 1;
        
        for ($i = 1; $i < count($values); $i++) {
            // Check if shorter MA > longer MA (bullish) or shorter MA < longer MA (bearish)
            if ($values[$i-1] > $values[$i] || $values[$i-1] < $values[$i]) {
                $alignmentCount++;
            }
        }
        
        return (int) round(($alignmentCount / $totalComparisons) * 100);
    }

    /**
     * Calculate trend consensus across all MAs
     */
    private function calculateTrendConsensus(array $results): array
    {
        $trends = [];
        
        foreach ($results as $key => $result) {
            if (!isset($result['error']) && isset($result['summary']['trend'])) {
                $trends[] = $result['summary']['trend'];
            }
        }
        
        $trendCounts = array_count_values($trends);
        arsort($trendCounts);
        
        $dominantTrend = key($trendCounts);
        $consensus = count($trendCounts) > 0 ? ($trendCounts[$dominantTrend] / count($trends)) * 100 : 0;
        
        return [
            'dominant_trend' => $dominantTrend,
            'consensus_pct' => round($consensus, 1),
            'trend_distribution' => $trendCounts,
            'total_indicators' => count($trends)
        ];
    }
}

/**
 * Volume Indicators Calculator
 * Implements Chaikin A/D Oscillator and other volume-based indicators
 */
class VolumeIndicatorsCalculator extends TALibCalculatorBase
{
    /**
     * Calculate Chaikin A/D Oscillator
     */
    public function calculateChaikinOscillator(array $priceData, int $fastPeriod = 3, int $slowPeriod = 10): array
    {
        $this->validatePriceData($priceData, ['high', 'low', 'close', 'volume']);
        
        if (count($priceData) < $slowPeriod) {
            return $this->formatError('insufficient_data', "Need at least {$slowPeriod} data points for Chaikin Oscillator");
        }

        $arrays = $this->extractArrays($priceData, ['high', 'low', 'close', 'volume']);
        
        try {
            // Calculate Chaikin A/D Oscillator using TA-Lib
            $adOsc = Trader::adosc($arrays['high'], $arrays['low'], $arrays['close'], $arrays['volume'], $fastPeriod, $slowPeriod);
            
            // Combine with dates
            $dataKeys = array_keys($priceData);
            $resultData = [];
            $signals = [];
            
            foreach ($adOsc as $index => $value) {
                if ($value !== null && isset($dataKeys[$index])) {
                    $date = $dataKeys[$index];
                    $price = $priceData[$date]['close'];
                    $volume = $priceData[$date]['volume'];
                    
                    $signal = $this->determineVolumeSignal($value);
                    
                    $resultData[$date] = [
                        'adosc' => round($value, 4),
                        'price' => $price,
                        'volume' => $volume,
                        'signal' => $signal,
                        'momentum' => $this->determineVolumeMomentum($value)
                    ];
                    
                    if ($signal !== 'HOLD') {
                        $signals[] = [
                            'date' => $date,
                            'signal' => $signal,
                            'adosc' => round($value, 4),
                            'price' => $price,
                            'strength' => $this->calculateVolumeSignalStrength($value)
                        ];
                    }
                }
            }

            return [
                'indicator' => 'ChaikinOscillator',
                'parameters' => [
                    'fast_period' => $fastPeriod,
                    'slow_period' => $slowPeriod
                ],
                'data' => $resultData,
                'signals' => $signals,
                'summary' => $this->generateVolumeSummary($resultData),
                'calculation_engine' => 'TA-Lib'
            ];
            
        } catch (\Exception $e) {
            return $this->formatError('calculation_error', $e->getMessage());
        }
    }

    /**
     * Determine volume-based signal
     */
    private function determineVolumeSignal(float $value): string
    {
        if ($value > 1000) return 'STRONG_BUY';
        if ($value > 0) return 'BUY';
        if ($value < -1000) return 'STRONG_SELL';
        if ($value < 0) return 'SELL';
        return 'HOLD';
    }

    /**
     * Determine volume momentum
     */
    private function determineVolumeMomentum(float $value): string
    {
        if ($value > 500) return 'STRONG_ACCUMULATION';
        if ($value > 0) return 'ACCUMULATION';
        if ($value < -500) return 'STRONG_DISTRIBUTION';
        if ($value < 0) return 'DISTRIBUTION';
        return 'NEUTRAL';
    }

    /**
     * Calculate volume signal strength
     */
    private function calculateVolumeSignalStrength(float $value): int
    {
        $absValue = abs($value);
        
        if ($absValue > 2000) return 90;
        if ($absValue > 1000) return 70;
        if ($absValue > 500) return 50;
        return 30;
    }

    /**
     * Generate volume summary
     */
    private function generateVolumeSummary(array $resultData): array
    {
        if (empty($resultData)) {
            return ['status' => 'no_data'];
        }

        $latest = end($resultData);
        $recent = array_slice($resultData, -10, 10, true);
        
        $accumulationCount = 0;
        foreach ($recent as $data) {
            if (strpos($data['momentum'], 'ACCUMULATION') !== false) {
                $accumulationCount++;
            }
        }
        
        return [
            'current_adosc' => $latest['adosc'],
            'current_signal' => $latest['signal'],
            'current_momentum' => $latest['momentum'],
            'recent_accumulation_pct' => round(($accumulationCount / count($recent)) * 100, 1),
            'trend' => $accumulationCount > 5 ? 'ACCUMULATION' : 'DISTRIBUTION'
        ];
    }
}
