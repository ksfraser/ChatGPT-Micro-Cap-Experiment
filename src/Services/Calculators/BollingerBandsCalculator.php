<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;

/**
 * Single Responsibility: Calculate Bollinger Bands
 * Follows SRP by handling only Bollinger Band calculations
 */
class BollingerBandsCalculator
{
    /**
     * Calculate Bollinger Bands
     */
    public function calculate(Collection $data, int $period = 20, float $stdDev = 2.0): array
    {
        if ($data->count() < $period) {
            return [
                'upper_band' => null,
                'middle_band' => null,
                'lower_band' => null,
                'bandwidth' => null,
                'percent_b' => null,
                'squeeze' => false
            ];
        }

        // Get closing prices
        $closes = $data->map(function($quote) {
            return $quote->close;
        })->toArray();

        // Calculate Simple Moving Average (middle band)
        $sma = $this->simpleMovingAverage($closes, $period);
        $currentSMA = end($sma);

        // Calculate standard deviation for the period
        $recentCloses = array_slice($closes, -$period);
        $variance = 0;
        
        foreach ($recentCloses as $close) {
            $variance += pow($close - $currentSMA, 2);
        }
        
        $standardDeviation = sqrt($variance / $period);

        // Calculate bands
        $upperBand = $currentSMA + ($stdDev * $standardDeviation);
        $lowerBand = $currentSMA - ($stdDev * $standardDeviation);
        
        // Current price for additional calculations
        $currentPrice = end($closes);
        
        // Calculate %B (position within bands)
        $percentB = null;
        if ($upperBand != $lowerBand) {
            $percentB = ($currentPrice - $lowerBand) / ($upperBand - $lowerBand);
        }

        // Calculate bandwidth (volatility measure)
        $bandwidth = null;
        if ($currentSMA != 0) {
            $bandwidth = ($upperBand - $lowerBand) / $currentSMA;
        }

        // Detect squeeze (low volatility)
        $squeeze = $this->detectSqueeze($data, $period, $stdDev);

        return [
            'upper_band' => round($upperBand, 4),
            'middle_band' => round($currentSMA, 4),
            'lower_band' => round($lowerBand, 4),
            'bandwidth' => round($bandwidth * 100, 2), // As percentage
            'percent_b' => round($percentB, 4),
            'squeeze' => $squeeze,
            'standard_deviation' => round($standardDeviation, 4),
            'signal' => $this->getBollingerSignal($currentPrice, $upperBand, $lowerBand, $percentB)
        ];
    }

    /**
     * Calculate historical Bollinger Bands
     */
    public function calculateHistorical(Collection $data, int $period = 20, float $stdDev = 2.0): array
    {
        if ($data->count() < $period) {
            return [];
        }

        $results = [];
        $closes = $data->map(function($quote) {
            return $quote->close;
        })->toArray();

        for ($i = $period - 1; $i < count($closes); $i++) {
            $subset = array_slice($closes, $i - $period + 1, $period);
            $sma = array_sum($subset) / $period;
            
            // Calculate standard deviation
            $variance = 0;
            foreach ($subset as $close) {
                $variance += pow($close - $sma, 2);
            }
            $standardDeviation = sqrt($variance / $period);
            
            $upperBand = $sma + ($stdDev * $standardDeviation);
            $lowerBand = $sma - ($stdDev * $standardDeviation);
            $currentPrice = $closes[$i];
            
            $percentB = ($upperBand != $lowerBand) ? 
                ($currentPrice - $lowerBand) / ($upperBand - $lowerBand) : null;
            
            $results[] = [
                'date' => $data[$i]->date ?? date('Y-m-d', strtotime("-" . (count($closes) - $i - 1) . " days")),
                'upper_band' => round($upperBand, 4),
                'middle_band' => round($sma, 4),
                'lower_band' => round($lowerBand, 4),
                'percent_b' => round($percentB, 4),
                'close' => $currentPrice
            ];
        }

        return $results;
    }

    /**
     * Calculate simple moving average
     */
    private function simpleMovingAverage(array $data, int $period): array
    {
        $sma = [];
        
        for ($i = $period - 1; $i < count($data); $i++) {
            $sum = array_sum(array_slice($data, $i - $period + 1, $period));
            $sma[] = $sum / $period;
        }
        
        return $sma;
    }

    /**
     * Detect Bollinger Band squeeze (low volatility)
     */
    private function detectSqueeze(Collection $data, int $period = 20, float $stdDev = 2.0): bool
    {
        if ($data->count() < $period * 2) {
            return false;
        }

        // Calculate current bandwidth
        $current = $this->calculate($data->slice(-$period), $period, $stdDev);
        $currentBandwidth = $current['bandwidth'];

        // Calculate average bandwidth over longer period
        $longerPeriod = min($period * 2, $data->count());
        $bandwidths = [];
        
        for ($i = $period; $i <= $longerPeriod; $i++) {
            $subset = $data->slice(-$i, $period);
            $result = $this->calculate($subset, $period, $stdDev);
            if ($result['bandwidth'] !== null) {
                $bandwidths[] = $result['bandwidth'];
            }
        }

        if (empty($bandwidths)) {
            return false;
        }

        $avgBandwidth = array_sum($bandwidths) / count($bandwidths);
        
        // Squeeze detected if current bandwidth is significantly below average
        return $currentBandwidth < ($avgBandwidth * 0.7);
    }

    /**
     * Generate trading signal based on Bollinger Bands
     */
    private function getBollingerSignal(float $price, float $upperBand, float $lowerBand, ?float $percentB): string
    {
        if ($percentB === null) {
            return 'NEUTRAL';
        }

        // Strong oversold condition
        if ($percentB < 0) {
            return 'STRONG_BUY';
        }
        
        // Oversold
        if ($percentB < 0.2) {
            return 'BUY';
        }
        
        // Strong overbought condition
        if ($percentB > 1) {
            return 'STRONG_SELL';
        }
        
        // Overbought
        if ($percentB > 0.8) {
            return 'SELL';
        }
        
        return 'NEUTRAL';
    }

    /**
     * Calculate Bollinger Band accuracy
     */
    public function calculateAccuracy(Collection $data, int $period = 20, float $stdDev = 2.0, int $lookback = 50): array
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
        
        for ($i = $period; $i < min($data->count() - 1, $period + $lookback); $i++) {
            $subset = $data->slice($i - $period, $period);
            $bbResult = $this->calculate($subset, $period, $stdDev);
            
            if ($bbResult['percent_b'] !== null) {
                $signal = $bbResult['signal'];
                
                // Check if signal was profitable
                $entry_price = $data[$i]->close;
                $exit_price = $data[$i + 1]->close;
                
                $profitable = false;
                
                if (strpos($signal, 'BUY') !== false && $exit_price > $entry_price) {
                    $profitable = true;
                } elseif (strpos($signal, 'SELL') !== false && $exit_price < $entry_price) {
                    $profitable = true;
                } elseif ($signal === 'NEUTRAL') {
                    // Neutral signals are "correct" if price doesn't move significantly
                    $change = abs($exit_price - $entry_price) / $entry_price;
                    if ($change < 0.01) { // Less than 1% move
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
        
        $baseConfidence = min(95, $sampleSize * 1.5);
        
        // Adjust based on accuracy
        if ($accuracy > 75 || $accuracy < 25) {
            $baseConfidence *= 0.85;
        }
        
        return $baseConfidence;
    }
}
