<?php

namespace App\Services;

use App\Models\Portfolio;
use App\Models\Quote;
use Illuminate\Support\Collection;

/**
 * Portfolio Risk Management Service
 * 
 * Implements sophisticated risk analysis algorithms found in BeanCounter
 * and other professional portfolio management systems.
 */
class PortfolioRiskManager
{
    /**
     * Calculate Value at Risk (VaR)
     * Estimates potential loss over a specific time period and confidence level
     */
    public function calculateVaR(array $portfolioReturns, float $confidence = 0.95, int $timeHorizon = 1): array
    {
        if (empty($portfolioReturns)) {
            return ['var' => 0, 'method' => 'insufficient_data'];
        }

        sort($portfolioReturns);
        $count = count($portfolioReturns);
        
        // Parametric VaR (assumes normal distribution)
        $mean = array_sum($portfolioReturns) / $count;
        $variance = array_sum(array_map(function($return) use ($mean) {
            return pow($return - $mean, 2);
        }, $portfolioReturns)) / ($count - 1);
        
        $stdDev = sqrt($variance);
        $zScore = $this->getZScore($confidence);
        $parametricVaR = $mean - ($zScore * $stdDev * sqrt($timeHorizon));
        
        // Historical VaR (non-parametric)
        $percentileIndex = (int) floor((1 - $confidence) * $count);
        $historicalVaR = $portfolioReturns[$percentileIndex];
        
        // Monte Carlo VaR (simplified)
        $monteCarloVaR = $this->calculateMonteCarloVaR($mean, $stdDev, $confidence, $timeHorizon);
        
        return [
            'parametric_var' => $parametricVaR,
            'historical_var' => $historicalVaR,
            'monte_carlo_var' => $monteCarloVaR,
            'confidence_level' => $confidence,
            'time_horizon' => $timeHorizon,
            'sample_size' => $count,
            'mean_return' => $mean,
            'volatility' => $stdDev
        ];
    }

    /**
     * Calculate portfolio beta relative to market
     */
    public function calculateBeta(array $assetReturns, array $marketReturns): float
    {
        if (count($assetReturns) !== count($marketReturns) || empty($assetReturns)) {
            return 0;
        }

        $assetMean = array_sum($assetReturns) / count($assetReturns);
        $marketMean = array_sum($marketReturns) / count($marketReturns);
        
        $covariance = 0;
        $marketVariance = 0;
        
        for ($i = 0; $i < count($assetReturns); $i++) {
            $assetDev = $assetReturns[$i] - $assetMean;
            $marketDev = $marketReturns[$i] - $marketMean;
            
            $covariance += $assetDev * $marketDev;
            $marketVariance += pow($marketDev, 2);
        }
        
        $covariance /= (count($assetReturns) - 1);
        $marketVariance /= (count($marketReturns) - 1);
        
        return $marketVariance != 0 ? $covariance / $marketVariance : 0;
    }

    /**
     * Calculate Sharpe Ratio
     * Risk-adjusted return measure
     */
    public function calculateSharpeRatio(array $returns, float $riskFreeRate = 0.02): float
    {
        if (empty($returns)) {
            return 0;
        }

        $mean = array_sum($returns) / count($returns);
        $variance = array_sum(array_map(function($return) use ($mean) {
            return pow($return - $mean, 2);
        }, $returns)) / (count($returns) - 1);
        
        $stdDev = sqrt($variance);
        
        // Annualize the returns and risk-free rate
        $annualizedReturn = $mean * 252; // Assuming 252 trading days
        $annualizedStdDev = $stdDev * sqrt(252);
        
        return $annualizedStdDev != 0 ? ($annualizedReturn - $riskFreeRate) / $annualizedStdDev : 0;
    }

    /**
     * Calculate Maximum Drawdown
     * Largest peak-to-trough decline in portfolio value
     */
    public function calculateMaxDrawdown(array $portfolioValues): array
    {
        if (count($portfolioValues) < 2) {
            return ['max_drawdown' => 0, 'drawdown_duration' => 0, 'peak_date' => null, 'trough_date' => null];
        }

        $maxDrawdown = 0;
        $peakValue = $portfolioValues[0];
        $peakIndex = 0;
        $maxDrawdownStart = 0;
        $maxDrawdownEnd = 0;
        $currentDrawdownStart = 0;
        
        for ($i = 1; $i < count($portfolioValues); $i++) {
            if ($portfolioValues[$i] > $peakValue) {
                $peakValue = $portfolioValues[$i];
                $peakIndex = $i;
                $currentDrawdownStart = $i;
            } else {
                $drawdown = ($peakValue - $portfolioValues[$i]) / $peakValue;
                if ($drawdown > $maxDrawdown) {
                    $maxDrawdown = $drawdown;
                    $maxDrawdownStart = $currentDrawdownStart;
                    $maxDrawdownEnd = $i;
                }
            }
        }
        
        return [
            'max_drawdown' => $maxDrawdown,
            'max_drawdown_percent' => $maxDrawdown * 100,
            'drawdown_duration' => $maxDrawdownEnd - $maxDrawdownStart,
            'peak_index' => $maxDrawdownStart,
            'trough_index' => $maxDrawdownEnd,
            'peak_value' => $peakValue,
            'trough_value' => $portfolioValues[$maxDrawdownEnd] ?? 0
        ];
    }

    /**
     * Calculate correlation matrix for portfolio assets
     */
    public function calculateCorrelationMatrix(array $assetReturns): array
    {
        $assets = array_keys($assetReturns);
        $correlationMatrix = [];
        
        foreach ($assets as $asset1) {
            foreach ($assets as $asset2) {
                if ($asset1 === $asset2) {
                    $correlationMatrix[$asset1][$asset2] = 1.0;
                } else {
                    $correlation = $this->calculateCorrelation(
                        $assetReturns[$asset1],
                        $assetReturns[$asset2]
                    );
                    $correlationMatrix[$asset1][$asset2] = $correlation;
                }
            }
        }
        
        return $correlationMatrix;
    }

    /**
     * Calculate correlation between two return series
     */
    private function calculateCorrelation(array $returns1, array $returns2): float
    {
        if (count($returns1) !== count($returns2) || empty($returns1)) {
            return 0;
        }

        $mean1 = array_sum($returns1) / count($returns1);
        $mean2 = array_sum($returns2) / count($returns2);
        
        $numerator = 0;
        $sum1Sq = 0;
        $sum2Sq = 0;
        
        for ($i = 0; $i < count($returns1); $i++) {
            $dev1 = $returns1[$i] - $mean1;
            $dev2 = $returns2[$i] - $mean2;
            
            $numerator += $dev1 * $dev2;
            $sum1Sq += pow($dev1, 2);
            $sum2Sq += pow($dev2, 2);
        }
        
        $denominator = sqrt($sum1Sq * $sum2Sq);
        
        return $denominator != 0 ? $numerator / $denominator : 0;
    }

    /**
     * Optimize portfolio allocation using Modern Portfolio Theory
     */
    public function optimizePortfolio(array $expectedReturns, array $covarianceMatrix, float $riskTolerance = 0.5): array
    {
        $numAssets = count($expectedReturns);
        
        if ($numAssets < 2) {
            return ['weights' => [], 'expected_return' => 0, 'expected_risk' => 0];
        }

        // Simplified optimization using equal weighting with adjustments
        // In production, this would use quadratic programming
        $baseWeight = 1.0 / $numAssets;
        $weights = array_fill(0, $numAssets, $baseWeight);
        
        // Adjust weights based on risk-return profile
        $assets = array_keys($expectedReturns);
        $totalAdjustment = 0;
        
        for ($i = 0; $i < $numAssets; $i++) {
            $asset = $assets[$i];
            $returnScore = $expectedReturns[$asset];
            $riskScore = sqrt($covarianceMatrix[$asset][$asset]); // Volatility
            
            // Risk-adjusted score
            $score = $riskTolerance * $returnScore - (1 - $riskTolerance) * $riskScore;
            $adjustment = $score * 0.1; // Scale factor
            
            $weights[$i] += $adjustment;
            $totalAdjustment += $adjustment;
        }
        
        // Normalize weights to sum to 1
        $weightSum = array_sum($weights);
        if ($weightSum > 0) {
            $weights = array_map(function($weight) use ($weightSum) {
                return max(0, $weight / $weightSum);
            }, $weights);
        }
        
        // Calculate portfolio metrics
        $portfolioReturn = 0;
        $portfolioVariance = 0;
        
        for ($i = 0; $i < $numAssets; $i++) {
            $asset1 = $assets[$i];
            $portfolioReturn += $weights[$i] * $expectedReturns[$asset1];
            
            for ($j = 0; $j < $numAssets; $j++) {
                $asset2 = $assets[$j];
                $portfolioVariance += $weights[$i] * $weights[$j] * $covarianceMatrix[$asset1][$asset2];
            }
        }
        
        return [
            'weights' => array_combine($assets, $weights),
            'expected_return' => $portfolioReturn,
            'expected_risk' => sqrt($portfolioVariance),
            'sharpe_ratio' => sqrt($portfolioVariance) != 0 ? $portfolioReturn / sqrt($portfolioVariance) : 0
        ];
    }

    /**
     * Calculate portfolio concentration risk
     */
    public function calculateConcentrationRisk(array $weights): array
    {
        $numAssets = count($weights);
        
        if ($numAssets === 0) {
            return ['hhi' => 0, 'effective_assets' => 0, 'concentration_ratio' => 0];
        }

        // Herfindahl-Hirschman Index
        $hhi = array_sum(array_map(function($weight) {
            return pow($weight, 2);
        }, $weights));
        
        // Effective number of assets
        $effectiveAssets = $hhi > 0 ? 1 / $hhi : 0;
        
        // Concentration ratio (top 3 holdings)
        rsort($weights);
        $top3Weight = array_sum(array_slice($weights, 0, min(3, $numAssets)));
        
        return [
            'hhi' => $hhi,
            'effective_assets' => $effectiveAssets,
            'concentration_ratio' => $top3Weight,
            'diversification_score' => min(1, $effectiveAssets / $numAssets)
        ];
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

    /**
     * Calculate Monte Carlo VaR
     */
    private function calculateMonteCarloVaR(float $mean, float $stdDev, float $confidence, int $timeHorizon, int $simulations = 10000): float
    {
        $simulatedReturns = [];
        
        for ($i = 0; $i < $simulations; $i++) {
            // Generate random return using Box-Muller transformation
            $u1 = mt_rand() / mt_getrandmax();
            $u2 = mt_rand() / mt_getrandmax();
            
            $z = sqrt(-2 * log($u1)) * cos(2 * pi() * $u2);
            $simulatedReturn = $mean + ($stdDev * $z * sqrt($timeHorizon));
            $simulatedReturns[] = $simulatedReturn;
        }
        
        sort($simulatedReturns);
        $percentileIndex = (int) floor((1 - $confidence) * $simulations);
        
        return $simulatedReturns[$percentileIndex];
    }

    /**
     * Calculate tracking error relative to benchmark
     */
    public function calculateTrackingError(array $portfolioReturns, array $benchmarkReturns): float
    {
        if (count($portfolioReturns) !== count($benchmarkReturns) || empty($portfolioReturns)) {
            return 0;
        }

        $activeReturns = [];
        for ($i = 0; $i < count($portfolioReturns); $i++) {
            $activeReturns[] = $portfolioReturns[$i] - $benchmarkReturns[$i];
        }
        
        $mean = array_sum($activeReturns) / count($activeReturns);
        $variance = array_sum(array_map(function($return) use ($mean) {
            return pow($return - $mean, 2);
        }, $activeReturns)) / (count($activeReturns) - 1);
        
        return sqrt($variance) * sqrt(252); // Annualized
    }

    /**
     * Calculate Information Ratio
     */
    public function calculateInformationRatio(array $portfolioReturns, array $benchmarkReturns): float
    {
        if (count($portfolioReturns) !== count($benchmarkReturns) || empty($portfolioReturns)) {
            return 0;
        }

        $activeReturns = [];
        for ($i = 0; $i < count($portfolioReturns); $i++) {
            $activeReturns[] = $portfolioReturns[$i] - $benchmarkReturns[$i];
        }
        
        $activeReturn = array_sum($activeReturns) / count($activeReturns) * 252; // Annualized
        $trackingError = $this->calculateTrackingError($portfolioReturns, $benchmarkReturns);
        
        return $trackingError != 0 ? $activeReturn / $trackingError : 0;
    }

    /**
     * Calculate portfolio alpha
     */
    public function calculateAlpha(array $portfolioReturns, array $marketReturns, float $riskFreeRate = 0.02): float
    {
        if (empty($portfolioReturns) || empty($marketReturns)) {
            return 0;
        }

        $beta = $this->calculateBeta($portfolioReturns, $marketReturns);
        $portfolioReturn = array_sum($portfolioReturns) / count($portfolioReturns) * 252; // Annualized
        $marketReturn = array_sum($marketReturns) / count($marketReturns) * 252; // Annualized
        
        return $portfolioReturn - ($riskFreeRate + $beta * ($marketReturn - $riskFreeRate));
    }
}
