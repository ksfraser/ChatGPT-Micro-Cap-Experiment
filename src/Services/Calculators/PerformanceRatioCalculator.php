<?php

namespace App\Services\Calculators;

/**
 * Single Responsibility: Calculate Performance Ratios
 * Follows SRP by handling only performance ratio calculations
 */
class PerformanceRatioCalculator
{
    /**
     * Calculate Sharpe Ratio
     */
    public function calculateSharpeRatio(array $returns, float $riskFreeRate = 0.02): array
    {
        if (empty($returns)) {
            return [
                'sharpe_ratio' => null,
                'excess_return' => null,
                'volatility' => null
            ];
        }

        $meanReturn = array_sum($returns) / count($returns);
        $annualizedReturn = $meanReturn * 252; // Assuming daily returns
        $annualizedRiskFreeRate = $riskFreeRate;
        
        $excessReturn = $annualizedReturn - $annualizedRiskFreeRate;
        $volatility = $this->calculateVolatility($returns);
        
        $sharpeRatio = $volatility > 0 ? $excessReturn / $volatility : 0;
        
        return [
            'sharpe_ratio' => round($sharpeRatio, 4),
            'excess_return' => round($excessReturn, 4),
            'volatility' => round($volatility, 4),
            'annualized_return' => round($annualizedReturn, 4),
            'risk_free_rate' => $riskFreeRate
        ];
    }

    /**
     * Calculate Sortino Ratio (downside deviation only)
     */
    public function calculateSortinoRatio(array $returns, float $targetReturn = 0.0): array
    {
        if (empty($returns)) {
            return [
                'sortino_ratio' => null,
                'downside_deviation' => null,
                'excess_return' => null
            ];
        }

        $meanReturn = array_sum($returns) / count($returns);
        $annualizedReturn = $meanReturn * 252;
        $excessReturn = $annualizedReturn - $targetReturn;
        
        // Calculate downside deviation
        $downsideVariance = 0;
        $downsideCount = 0;
        
        foreach ($returns as $return) {
            if ($return < $targetReturn / 252) { // Daily target return
                $downsideVariance += pow($return - ($targetReturn / 252), 2);
                $downsideCount++;
            }
        }
        
        $downsideDeviation = 0;
        if ($downsideCount > 0) {
            $downsideDeviation = sqrt($downsideVariance / $downsideCount) * sqrt(252);
        }
        
        $sortinoRatio = $downsideDeviation > 0 ? $excessReturn / $downsideDeviation : 0;
        
        return [
            'sortino_ratio' => round($sortinoRatio, 4),
            'downside_deviation' => round($downsideDeviation, 4),
            'excess_return' => round($excessReturn, 4),
            'target_return' => $targetReturn,
            'downside_periods' => $downsideCount
        ];
    }

    /**
     * Calculate Calmar Ratio (return over maximum drawdown)
     */
    public function calculateCalmarRatio(array $cumulativeReturns): array
    {
        if (empty($cumulativeReturns)) {
            return [
                'calmar_ratio' => null,
                'max_drawdown' => null,
                'annualized_return' => null
            ];
        }

        $maxDrawdown = $this->calculateMaxDrawdown($cumulativeReturns);
        
        // Calculate annualized return
        $totalReturn = end($cumulativeReturns) / $cumulativeReturns[0] - 1;
        $periods = count($cumulativeReturns);
        $annualizedReturn = pow(1 + $totalReturn, 252 / $periods) - 1;
        
        $calmarRatio = $maxDrawdown['max_drawdown'] != 0 ? 
            $annualizedReturn / abs($maxDrawdown['max_drawdown']) : 0;
        
        return [
            'calmar_ratio' => round($calmarRatio, 4),
            'max_drawdown' => $maxDrawdown['max_drawdown'],
            'annualized_return' => round($annualizedReturn, 4),
            'drawdown_details' => $maxDrawdown
        ];
    }

    /**
     * Calculate Information Ratio
     */
    public function calculateInformationRatio(array $portfolioReturns, array $benchmarkReturns): array
    {
        if (empty($portfolioReturns) || empty($benchmarkReturns) || 
            count($portfolioReturns) !== count($benchmarkReturns)) {
            return [
                'information_ratio' => null,
                'alpha' => null,
                'tracking_error' => null
            ];
        }

        // Calculate excess returns
        $excessReturns = [];
        for ($i = 0; $i < count($portfolioReturns); $i++) {
            $excessReturns[] = $portfolioReturns[$i] - $benchmarkReturns[$i];
        }
        
        $alpha = array_sum($excessReturns) / count($excessReturns) * 252; // Annualized
        $trackingError = $this->calculateVolatility($excessReturns);
        
        $informationRatio = $trackingError > 0 ? $alpha / $trackingError : 0;
        
        return [
            'information_ratio' => round($informationRatio, 4),
            'alpha' => round($alpha, 4),
            'tracking_error' => round($trackingError, 4),
            'excess_returns_count' => count($excessReturns)
        ];
    }

    /**
     * Calculate Treynor Ratio
     */
    public function calculateTreynorRatio(array $returns, float $beta, float $riskFreeRate = 0.02): array
    {
        if (empty($returns) || $beta == 0) {
            return [
                'treynor_ratio' => null,
                'excess_return' => null,
                'beta' => $beta
            ];
        }

        $meanReturn = array_sum($returns) / count($returns);
        $annualizedReturn = $meanReturn * 252;
        $excessReturn = $annualizedReturn - $riskFreeRate;
        
        $treynorRatio = $excessReturn / $beta;
        
        return [
            'treynor_ratio' => round($treynorRatio, 4),
            'excess_return' => round($excessReturn, 4),
            'beta' => $beta,
            'annualized_return' => round($annualizedReturn, 4)
        ];
    }

    /**
     * Calculate Maximum Drawdown
     */
    public function calculateMaxDrawdown(array $values): array
    {
        if (empty($values)) {
            return [
                'max_drawdown' => 0,
                'drawdown_start' => null,
                'drawdown_end' => null,
                'recovery_date' => null,
                'drawdown_duration' => 0
            ];
        }

        $peak = $values[0];
        $maxDrawdown = 0;
        $drawdownStart = 0;
        $drawdownEnd = 0;
        $currentDrawdownStart = 0;
        
        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i] > $peak) {
                $peak = $values[$i];
                $currentDrawdownStart = $i;
            } else {
                $drawdown = ($values[$i] - $peak) / $peak;
                if ($drawdown < $maxDrawdown) {
                    $maxDrawdown = $drawdown;
                    $drawdownStart = $currentDrawdownStart;
                    $drawdownEnd = $i;
                }
            }
        }
        
        // Find recovery date
        $recoveryDate = null;
        $peakValue = $values[$drawdownStart];
        
        for ($i = $drawdownEnd + 1; $i < count($values); $i++) {
            if ($values[$i] >= $peakValue) {
                $recoveryDate = $i;
                break;
            }
        }
        
        return [
            'max_drawdown' => round($maxDrawdown, 4),
            'drawdown_start' => $drawdownStart,
            'drawdown_end' => $drawdownEnd,
            'recovery_date' => $recoveryDate,
            'drawdown_duration' => $drawdownEnd - $drawdownStart,
            'recovery_duration' => $recoveryDate ? $recoveryDate - $drawdownEnd : null
        ];
    }

    /**
     * Calculate Beta (market sensitivity)
     */
    public function calculateBeta(array $assetReturns, array $marketReturns): array
    {
        if (empty($assetReturns) || empty($marketReturns) || 
            count($assetReturns) !== count($marketReturns)) {
            return [
                'beta' => null,
                'alpha' => null,
                'r_squared' => null,
                'correlation' => null
            ];
        }

        // Calculate means
        $assetMean = array_sum($assetReturns) / count($assetReturns);
        $marketMean = array_sum($marketReturns) / count($marketReturns);
        
        // Calculate covariance and variance
        $covariance = 0;
        $marketVariance = 0;
        
        for ($i = 0; $i < count($assetReturns); $i++) {
            $assetDiff = $assetReturns[$i] - $assetMean;
            $marketDiff = $marketReturns[$i] - $marketMean;
            
            $covariance += $assetDiff * $marketDiff;
            $marketVariance += $marketDiff * $marketDiff;
        }
        
        $covariance /= (count($assetReturns) - 1);
        $marketVariance /= (count($marketReturns) - 1);
        
        $beta = $marketVariance > 0 ? $covariance / $marketVariance : 0;
        $alpha = $assetMean - ($beta * $marketMean);
        
        // Calculate correlation and R-squared
        $assetStdDev = sqrt($this->calculateVariance($assetReturns));
        $marketStdDev = sqrt($marketVariance);
        
        $correlation = 0;
        if ($assetStdDev > 0 && $marketStdDev > 0) {
            $correlation = $covariance / ($assetStdDev * $marketStdDev);
        }
        
        $rSquared = $correlation * $correlation;
        
        return [
            'beta' => round($beta, 4),
            'alpha' => round($alpha * 252, 4), // Annualized
            'r_squared' => round($rSquared, 4),
            'correlation' => round($correlation, 4),
            'covariance' => round($covariance, 6)
        ];
    }

    /**
     * Calculate all performance ratios in one call
     */
    public function calculateAllRatios(
        array $returns, 
        array $benchmarkReturns = null,
        array $cumulativeValues = null,
        float $riskFreeRate = 0.02
    ): array {
        $results = [];
        
        // Basic ratios
        $results['sharpe'] = $this->calculateSharpeRatio($returns, $riskFreeRate);
        $results['sortino'] = $this->calculateSortinoRatio($returns);
        
        // Beta-based ratios
        if ($benchmarkReturns !== null) {
            $beta = $this->calculateBeta($returns, $benchmarkReturns);
            $results['beta_analysis'] = $beta;
            $results['treynor'] = $this->calculateTreynorRatio($returns, $beta['beta'], $riskFreeRate);
            $results['information'] = $this->calculateInformationRatio($returns, $benchmarkReturns);
        }
        
        // Drawdown-based ratios
        if ($cumulativeValues !== null) {
            $results['calmar'] = $this->calculateCalmarRatio($cumulativeValues);
            $results['drawdown'] = $this->calculateMaxDrawdown($cumulativeValues);
        }
        
        return $results;
    }

    /**
     * Calculate volatility (annualized standard deviation)
     */
    private function calculateVolatility(array $returns): float
    {
        if (empty($returns)) {
            return 0;
        }
        
        $variance = $this->calculateVariance($returns);
        return sqrt($variance * 252); // Annualized
    }

    /**
     * Calculate variance
     */
    private function calculateVariance(array $returns): float
    {
        if (empty($returns)) {
            return 0;
        }
        
        $mean = array_sum($returns) / count($returns);
        $variance = 0;
        
        foreach ($returns as $return) {
            $variance += pow($return - $mean, 2);
        }
        
        return $variance / (count($returns) - 1);
    }
}
