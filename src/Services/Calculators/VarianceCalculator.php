<?php

namespace App\Services\Calculators;

/**
 * Single Responsibility: Calculate variance and statistical measures
 * Follows SRP by handling only variance-related calculations
 */
class VarianceCalculator
{
    /**
     * Calculate variance of an array of returns
     */
    public function calculateVariance(array $returns): float
    {
        if (empty($returns) || count($returns) < 2) {
            return 0.0;
        }
        
        $mean = $this->calculateMean($returns);
        $sum = 0.0;
        
        foreach ($returns as $return) {
            $sum += pow($return - $mean, 2);
        }
        
        return $sum / (count($returns) - 1);
    }

    /**
     * Calculate standard deviation
     */
    public function calculateStandardDeviation(array $returns): float
    {
        return sqrt($this->calculateVariance($returns));
    }

    /**
     * Calculate mean of returns
     */
    public function calculateMean(array $returns): float
    {
        if (empty($returns)) {
            return 0.0;
        }
        
        return array_sum($returns) / count($returns);
    }

    /**
     * Calculate skewness of returns distribution
     */
    public function calculateSkewness(array $returns): float
    {
        if (count($returns) < 3) {
            return 0.0;
        }

        $mean = $this->calculateMean($returns);
        $stdDev = $this->calculateStandardDeviation($returns);
        
        if ($stdDev == 0) {
            return 0.0;
        }

        $sum = 0.0;
        $n = count($returns);
        
        foreach ($returns as $return) {
            $sum += pow(($return - $mean) / $stdDev, 3);
        }
        
        return ($n / (($n - 1) * ($n - 2))) * $sum;
    }

    /**
     * Calculate kurtosis of returns distribution
     */
    public function calculateKurtosis(array $returns): float
    {
        if (count($returns) < 4) {
            return 0.0;
        }

        $mean = $this->calculateMean($returns);
        $stdDev = $this->calculateStandardDeviation($returns);
        
        if ($stdDev == 0) {
            return 0.0;
        }

        $sum = 0.0;
        $n = count($returns);
        
        foreach ($returns as $return) {
            $sum += pow(($return - $mean) / $stdDev, 4);
        }
        
        // Calculate excess kurtosis (normal distribution has kurtosis of 3)
        $kurtosis = (($n * ($n + 1)) / (($n - 1) * ($n - 2) * ($n - 3))) * $sum;
        $adjustment = (3 * pow($n - 1, 2)) / (($n - 2) * ($n - 3));
        
        return $kurtosis - $adjustment;
    }

    /**
     * Calculate correlation coefficient between two return series
     */
    public function calculateCorrelation(array $returns1, array $returns2): float
    {
        if (count($returns1) !== count($returns2) || count($returns1) < 2) {
            return 0.0;
        }

        $mean1 = $this->calculateMean($returns1);
        $mean2 = $this->calculateMean($returns2);
        
        $numerator = 0.0;
        $sum1 = 0.0;
        $sum2 = 0.0;
        
        for ($i = 0; $i < count($returns1); $i++) {
            $diff1 = $returns1[$i] - $mean1;
            $diff2 = $returns2[$i] - $mean2;
            
            $numerator += $diff1 * $diff2;
            $sum1 += pow($diff1, 2);
            $sum2 += pow($diff2, 2);
        }
        
        $denominator = sqrt($sum1 * $sum2);
        
        return $denominator != 0 ? $numerator / $denominator : 0.0;
    }

    /**
     * Calculate covariance between two return series
     */
    public function calculateCovariance(array $returns1, array $returns2): float
    {
        if (count($returns1) !== count($returns2) || count($returns1) < 2) {
            return 0.0;
        }

        $mean1 = $this->calculateMean($returns1);
        $mean2 = $this->calculateMean($returns2);
        
        $sum = 0.0;
        
        for ($i = 0; $i < count($returns1); $i++) {
            $sum += ($returns1[$i] - $mean1) * ($returns2[$i] - $mean2);
        }
        
        return $sum / (count($returns1) - 1);
    }
}
