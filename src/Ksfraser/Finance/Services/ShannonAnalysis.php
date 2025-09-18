<?php

namespace App\Services;

use App\Models\Quote;

/**
 * Shannon Probability Analysis Service
 * 
 * Implements Shannon information theory-based analysis algorithms
 * found in the TsInvest system for optimal portfolio allocation.
 */
class ShannonAnalysis
{
    /**
     * Calculate Shannon probability for a time series
     * Based on the TsInvest algorithm for equity selection
     */
    public function calculateShannonProbability(array $priceData, int $window = 20): array
    {
        if (count($priceData) < $window + 1) {
            return [];
        }

        $shannonValues = [];
        
        for ($i = $window; $i < count($priceData); $i++) {
            $subset = array_slice($priceData, $i - $window, $window);
            $returns = [];
            
            // Calculate returns
            for ($j = 1; $j < count($subset); $j++) {
                $currentPrice = $subset[$j]['close'];
                $previousPrice = $subset[$j - 1]['close'];
                $returns[] = ($currentPrice - $previousPrice) / $previousPrice;
            }
            
            if (empty($returns)) continue;
            
            // Calculate basic statistics
            $meanReturn = array_sum($returns) / count($returns);
            $variance = $this->calculateVariance($returns, $meanReturn);
            $stdDev = sqrt($variance);
            
            // Count up moves for probability calculation
            $upMoves = array_filter($returns, function($return) { 
                return $return > 0; 
            });
            $probability = count($upMoves) / count($returns);
            
            // Shannon entropy calculation
            $entropy = $this->calculateShannonEntropy($returns);
            
            // Estimate accuracy based on sample size
            $accuracy = $this->estimateAccuracy($probability, count($returns));
            
            // Calculate effective probability with confidence intervals
            $standardError = sqrt($probability * (1 - $probability) / count($returns));
            $effectiveProbability = max(0.5, $probability - (1.96 * $standardError));
            
            $shannonValues[] = [
                'date' => $priceData[$i]['date'],
                'probability' => $probability,
                'entropy' => $entropy,
                'accuracy' => $accuracy,
                'mean_return' => $meanReturn,
                'volatility' => $stdDev,
                'effective_probability' => $effectiveProbability,
                'standard_error' => $standardError,
                'sample_size' => count($returns),
                'up_moves' => count($upMoves),
                'down_moves' => count($returns) - count($upMoves)
            ];
        }
        
        return $shannonValues;
    }

    /**
     * Calculate Shannon entropy for a return series
     */
    private function calculateShannonEntropy(array $returns): float
    {
        if (empty($returns)) return 0;
        
        // Discretize returns into bins
        $bins = $this->discretizeReturns($returns, 10);
        $total = count($returns);
        $entropy = 0;
        
        foreach ($bins as $count) {
            if ($count > 0) {
                $probability = $count / $total;
                $entropy -= $probability * log($probability, 2);
            }
        }
        
        return $entropy;
    }

    /**
     * Discretize returns into bins for entropy calculation
     */
    private function discretizeReturns(array $returns, int $numBins = 10): array
    {
        $min = min($returns);
        $max = max($returns);
        $binWidth = ($max - $min) / $numBins;
        
        $bins = array_fill(0, $numBins, 0);
        
        foreach ($returns as $return) {
            $binIndex = (int) floor(($return - $min) / $binWidth);
            $binIndex = min($binIndex, $numBins - 1); // Ensure we don't exceed array bounds
            $bins[$binIndex]++;
        }
        
        return $bins;
    }

    /**
     * Estimate accuracy of Shannon probability calculation
     */
    public function estimateAccuracy(float $probability, int $dataSetSize): float
    {
        if ($dataSetSize <= 0) return 0;
        
        // Calculate confidence interval
        $standardError = sqrt(($probability * (1 - $probability)) / $dataSetSize);
        $marginOfError = 1.96 * $standardError; // 95% confidence
        
        // Return accuracy as percentage
        return max(0, min(100, (1 - $marginOfError) * 100));
    }

    /**
     * Calculate effective Shannon probability with statistical validation
     */
    public function calculateEffectiveProbability(float $measured, float $confidence, int $sampleSize): float
    {
        if ($sampleSize <= 0) return $measured;
        
        // Calculate confidence interval
        $standardError = sqrt(($measured * (1 - $measured)) / $sampleSize);
        $zScore = $this->getZScore($confidence);
        
        // Conservative estimate (lower bound of confidence interval)
        $effectiveProbability = $measured - ($zScore * $standardError);
        
        // Ensure probability is between 0 and 1
        return max(0, min(1, $effectiveProbability));
    }

    /**
     * Analyze market persistence using Hurst exponent
     */
    public function analyzeMarketPersistence(array $data, string $method = 'hurst'): array
    {
        switch ($method) {
            case 'hurst':
                return $this->calculateHurstExponent($data);
            case 'variance_ratio':
                return $this->calculateHurstExponent($data); // Fallback to Hurst
            case 'detrended_fluctuation':
                return $this->calculateHurstExponent($data); // Fallback to Hurst
            default:
                return $this->calculateHurstExponent($data);
        }
    }

    /**
     * Calculate Hurst exponent for persistence analysis
     */
    private function calculateHurstExponent(array $priceData, int $minPeriod = 10, int $maxPeriod = 100): array
    {
        if (count($priceData) < $maxPeriod) {
            return ['hurst_exponent' => 0.5, 'interpretation' => 'insufficient_data'];
        }

        $logPeriods = [];
        $logRanges = [];
        
        // Calculate R/S statistic for different time periods
        for ($period = $minPeriod; $period <= $maxPeriod; $period += 5) {
            $rsStatistic = $this->calculateRSStatistic($priceData, $period);
            
            if ($rsStatistic > 0) {
                $logPeriods[] = log($period);
                $logRanges[] = log($rsStatistic);
            }
        }
        
        if (count($logPeriods) < 2) {
            return ['hurst_exponent' => 0.5, 'interpretation' => 'insufficient_data'];
        }

        // Calculate Hurst exponent as slope of log(R/S) vs log(n)
        $hurstExponent = $this->calculateSlope($logPeriods, $logRanges);
        
        // Interpret result
        $interpretation = 'random_walk';
        if ($hurstExponent > 0.55) {
            $interpretation = 'persistent';
        } elseif ($hurstExponent < 0.45) {
            $interpretation = 'anti_persistent';
        }
        
        return [
            'hurst_exponent' => $hurstExponent,
            'interpretation' => $interpretation,
            'confidence' => $this->calculateHurstConfidence($hurstExponent, count($logPeriods)),
            'sample_periods' => count($logPeriods)
        ];
    }

    /**
     * Calculate R/S statistic for a given period
     */
    private function calculateRSStatistic(array $priceData, int $period): float
    {
        $segments = (int) floor(count($priceData) / $period);
        $rsValues = [];
        
        for ($s = 0; $s < $segments; $s++) {
            $start = $s * $period;
            $segment = array_slice($priceData, $start, $period);
            
            // Calculate returns for segment
            $returns = [];
            for ($i = 1; $i < count($segment); $i++) {
                $returns[] = ($segment[$i]['close'] - $segment[$i - 1]['close']) / $segment[$i - 1]['close'];
            }
            
            if (empty($returns)) continue;
            
            $mean = array_sum($returns) / count($returns);
            
            // Calculate cumulative deviations
            $cumulativeDeviations = [];
            $cumulative = 0;
            foreach ($returns as $return) {
                $cumulative += ($return - $mean);
                $cumulativeDeviations[] = $cumulative;
            }
            
            // Calculate range
            $range = max($cumulativeDeviations) - min($cumulativeDeviations);
            
            // Calculate standard deviation
            $variance = $this->calculateVariance($returns, $mean);
            $standardDeviation = sqrt($variance);
            
            // Calculate R/S ratio
            if ($standardDeviation > 0) {
                $rsValues[] = $range / $standardDeviation;
            }
        }
        
        return !empty($rsValues) ? array_sum($rsValues) / count($rsValues) : 0;
    }

    /**
     * Detect mean reversion characteristics
     */
    public function detectMeanReversion(array $priceData, float $threshold = 0.5): array
    {
        if (count($priceData) < 20) {
            return ['mean_reversion_score' => 0, 'is_mean_reverting' => false];
        }

        $prices = array_column($priceData, 'close');
        $returns = [];
        
        // Calculate returns
        for ($i = 1; $i < count($prices); $i++) {
            $returns[] = ($prices[$i] - $prices[$i - 1]) / $prices[$i - 1];
        }
        
        // Calculate autocorrelation at lag 1
        $autocorrelation = $this->calculateAutocorrelation($returns, 1);
        
        // Mean reversion score (negative autocorrelation indicates mean reversion)
        $meanReversionScore = -$autocorrelation;
        $isMeanReverting = $meanReversionScore > $threshold;
        
        // Additional analysis
        $volatility = sqrt($this->calculateVariance($returns, array_sum($returns) / count($returns)));
        $halfLife = $this->calculateMeanReversionHalfLife($returns);
        
        return [
            'mean_reversion_score' => $meanReversionScore,
            'is_mean_reverting' => $isMeanReverting,
            'autocorrelation_lag1' => $autocorrelation,
            'volatility' => $volatility,
            'half_life' => $halfLife,
            'confidence' => abs($autocorrelation) * 100
        ];
    }

    /**
     * Optimize portfolio allocation using Shannon information theory
     */
    public function optimizePortfolioAllocation(array $equities, string $method = 'shannon'): array
    {
        switch ($method) {
            case 'shannon':
                return $this->shannonOptimization($equities);
            case 'kelly':
                return $this->kellyOptimization($equities);
            case 'entropy_weighted':
                return $this->shannonOptimization($equities); // Fallback to Shannon
            default:
                return $this->shannonOptimization($equities);
        }
    }

    /**
     * Shannon-based portfolio optimization
     */
    private function shannonOptimization(array $equities): array
    {
        $totalScore = 0;
        $scores = [];
        
        foreach ($equities as $symbol => $data) {
            $shannonData = $this->calculateShannonProbability($data['prices']);
            
            if (empty($shannonData)) {
                $scores[$symbol] = 0;
                continue;
            }
            
            $latestShannonData = end($shannonData);
            
            // Score based on effective probability and accuracy
            $score = $latestShannonData['effective_probability'] * 
                    ($latestShannonData['accuracy'] / 100) * 
                    (1 / (1 + $latestShannonData['volatility'])); // Penalize high volatility
            
            $scores[$symbol] = max(0, $score);
            $totalScore += $scores[$symbol];
        }
        
        // Normalize to get weights
        $weights = [];
        foreach ($scores as $symbol => $score) {
            $weights[$symbol] = $totalScore > 0 ? $score / $totalScore : 0;
        }
        
        return [
            'method' => 'shannon',
            'weights' => $weights,
            'scores' => $scores,
            'total_score' => $totalScore
        ];
    }

    /**
     * Kelly criterion optimization
     */
    private function kellyOptimization(array $equities): array
    {
        $weights = [];
        $totalWeight = 0;
        
        foreach ($equities as $symbol => $data) {
            $returns = $this->calculateReturns($data['prices']);
            
            if (empty($returns)) {
                $weights[$symbol] = 0;
                continue;
            }
            
            $winRate = count(array_filter($returns, function($r) { return $r > 0; })) / count($returns);
            $avgWin = 0;
            $avgLoss = 0;
            
            $wins = array_filter($returns, function($r) { return $r > 0; });
            $losses = array_filter($returns, function($r) { return $r < 0; });
            
            if (!empty($wins)) $avgWin = array_sum($wins) / count($wins);
            if (!empty($losses)) $avgLoss = abs(array_sum($losses) / count($losses));
            
            // Kelly formula: f = (bp - q) / b
            // Where b = odds, p = win probability, q = loss probability
            if ($avgLoss > 0) {
                $kellyFraction = ($winRate * $avgWin - (1 - $winRate) * $avgLoss) / $avgWin;
                $kellyFraction = max(0, min(0.25, $kellyFraction)); // Cap at 25%
            } else {
                $kellyFraction = 0;
            }
            
            $weights[$symbol] = $kellyFraction;
            $totalWeight += $kellyFraction;
        }
        
        // Normalize weights
        if ($totalWeight > 0) {
            foreach ($weights as $symbol => $weight) {
                $weights[$symbol] = $weight / $totalWeight;
            }
        }
        
        return [
            'method' => 'kelly',
            'weights' => $weights,
            'total_weight' => $totalWeight
        ];
    }

    /**
     * Calculate returns from price data
     */
    private function calculateReturns(array $priceData): array
    {
        $returns = [];
        for ($i = 1; $i < count($priceData); $i++) {
            $current = $priceData[$i]['close'];
            $previous = $priceData[$i - 1]['close'];
            $returns[] = ($current - $previous) / $previous;
        }
        return $returns;
    }

    /**
     * Calculate variance of an array
     */
    private function calculateVariance(array $values, float $mean): float
    {
        if (empty($values)) return 0;
        
        $sum = 0;
        foreach ($values as $value) {
            $sum += pow($value - $mean, 2);
        }
        
        return $sum / (count($values) - 1);
    }

    /**
     * Calculate autocorrelation at specified lag
     */
    private function calculateAutocorrelation(array $series, int $lag): float
    {
        if (count($series) <= $lag) return 0;
        
        $n = count($series);
        $mean = array_sum($series) / $n;
        
        $numerator = 0;
        $denominator = 0;
        
        for ($i = 0; $i < $n - $lag; $i++) {
            $numerator += ($series[$i] - $mean) * ($series[$i + $lag] - $mean);
        }
        
        for ($i = 0; $i < $n; $i++) {
            $denominator += pow($series[$i] - $mean, 2);
        }
        
        return $denominator > 0 ? $numerator / $denominator : 0;
    }

    /**
     * Calculate mean reversion half-life
     */
    private function calculateMeanReversionHalfLife(array $returns): float
    {
        if (count($returns) < 2) return 0;
        
        // Simplified half-life calculation
        $autocorr = $this->calculateAutocorrelation($returns, 1);
        
        if ($autocorr >= 0) return PHP_FLOAT_MAX; // No mean reversion
        
        return -log(2) / log(abs($autocorr));
    }

    /**
     * Calculate slope using least squares regression
     */
    private function calculateSlope(array $x, array $y): float
    {
        if (count($x) !== count($y) || count($x) < 2) return 0;
        
        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumXX = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumXX += $x[$i] * $x[$i];
        }
        
        $denominator = ($n * $sumXX - $sumX * $sumX);
        return $denominator != 0 ? ($n * $sumXY - $sumX * $sumY) / $denominator : 0;
    }

    /**
     * Calculate confidence in Hurst exponent estimate
     */
    private function calculateHurstConfidence(float $hurst, int $sampleSize): float
    {
        // Simplified confidence calculation based on sample size and deviation from 0.5
        $deviation = abs($hurst - 0.5);
        $sampleFactor = min(1, $sampleSize / 20); // Normalize for sample size
        
        return min(100, $deviation * 200 * $sampleFactor);
    }

    /**
     * Get Z-score for confidence level
     */
    private function getZScore(float $confidence): float
    {
        $zScores = [
            0.90 => 1.282,
            0.95 => 1.645,
            0.99 => 2.326,
            0.999 => 3.090
        ];
        
        return $zScores[$confidence] ?? 1.645; // Default to 95%
    }
}
