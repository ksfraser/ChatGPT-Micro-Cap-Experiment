<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;

/**
 * Single Responsibility: Calculate Shannon Probability
 * Follows SRP by handling only Shannon probability calculations
 */
class ShannonProbabilityCalculator
{
    /**
     * Calculate Shannon probability for a given dataset
     */
    public function calculate(Collection $data, int $window = 20): array
    {
        if ($data->count() < $window + 1) {
            return [
                'shannon_probability' => null,
                'effective_probability' => null,
                'entropy' => null,
                'accuracy_estimate' => null,
                'confidence_level' => null
            ];
        }

        // Get price movements for the window
        $movements = $this->getPriceMovements($data, $window);
        
        if (empty($movements)) {
            return [
                'shannon_probability' => null,
                'effective_probability' => null,
                'entropy' => null,
                'accuracy_estimate' => null,
                'confidence_level' => null
            ];
        }

        // Count up and down movements
        $upMoves = array_sum(array_map(function($move) {
            return $move > 0 ? 1 : 0;
        }, $movements));
        
        $downMoves = count($movements) - $upMoves;
        $totalMoves = count($movements);

        // Calculate raw Shannon probability
        $shannonProbability = $totalMoves > 0 ? $upMoves / $totalMoves : 0.5;

        // Calculate entropy
        $entropy = $this->calculateEntropy($shannonProbability);

        // Calculate effective probability with statistical adjustments
        $effectiveProbability = $this->calculateEffectiveProbability(
            $shannonProbability, 
            $totalMoves
        );

        // Estimate accuracy of the probability
        $accuracyEstimate = $this->estimateAccuracy($totalMoves, $shannonProbability);
        
        // Calculate confidence level
        $confidenceLevel = $this->calculateConfidenceLevel($totalMoves, $accuracyEstimate);

        return [
            'shannon_probability' => round($shannonProbability, 6),
            'effective_probability' => round($effectiveProbability, 6),
            'entropy' => round($entropy, 6),
            'accuracy_estimate' => round($accuracyEstimate, 2),
            'confidence_level' => round($confidenceLevel, 2),
            'sample_size' => $totalMoves,
            'up_moves' => $upMoves,
            'down_moves' => $downMoves,
            'window_size' => $window
        ];
    }

    /**
     * Calculate Shannon probability with multiple time windows
     */
    public function calculateMultiWindow(Collection $data, array $windows = [10, 20, 50]): array
    {
        $results = [];
        
        foreach ($windows as $window) {
            $result = $this->calculate($data, $window);
            if ($result['shannon_probability'] !== null) {
                $results["window_{$window}"] = $result;
            }
        }

        // Calculate consensus probability
        if (!empty($results)) {
            $probabilities = array_column($results, 'shannon_probability');
            $weights = array_column($results, 'confidence_level');
            
            $consensusProbability = $this->calculateWeightedAverage($probabilities, $weights);
            $results['consensus'] = [
                'shannon_probability' => round($consensusProbability, 6),
                'weight_count' => count($probabilities)
            ];
        }

        return $results;
    }

    /**
     * Get price movements from data
     */
    private function getPriceMovements(Collection $data, int $window): array
    {
        $movements = [];
        $endIndex = $data->count() - 1;
        $startIndex = max(0, $endIndex - $window);
        
        for ($i = $startIndex + 1; $i <= $endIndex; $i++) {
            $currentPrice = $data->get($i)->close;
            $previousPrice = $data->get($i - 1)->close;
            
            if ($previousPrice > 0) {
                $movement = ($currentPrice - $previousPrice) / $previousPrice;
                $movements[] = $movement;
            }
        }
        
        return $movements;
    }

    /**
     * Calculate Shannon entropy
     */
    private function calculateEntropy(float $probability): float
    {
        if ($probability <= 0 || $probability >= 1) {
            return 0;
        }
        
        $p = $probability;
        $q = 1 - $probability;
        
        return -($p * log($p, 2)) - ($q * log($q, 2));
    }

    /**
     * Calculate effective probability with small sample corrections
     */
    private function calculateEffectiveProbability(float $rawProbability, int $sampleSize): float
    {
        if ($sampleSize < 10) {
            // For very small samples, move toward 0.5 (maximum uncertainty)
            $adjustment = (10 - $sampleSize) / 10 * 0.5;
            return $rawProbability * (1 - $adjustment) + 0.5 * $adjustment;
        }
        
        // Apply Bayesian adjustment for moderate samples
        $priorWeight = max(1, 20 - $sampleSize);
        $adjustedProbability = ($rawProbability * $sampleSize + 0.5 * $priorWeight) / 
                               ($sampleSize + $priorWeight);
        
        return $adjustedProbability;
    }

    /**
     * Estimate accuracy of probability calculation
     */
    private function estimateAccuracy(int $sampleSize, float $probability): float
    {
        if ($sampleSize < 5) {
            return 0;
        }
        
        // Based on binomial distribution confidence intervals
        $variance = $probability * (1 - $probability) / $sampleSize;
        $standardError = sqrt($variance);
        
        // Convert to accuracy percentage (higher sample size = higher accuracy)
        $accuracy = max(0, 100 - ($standardError * 100 * 2));
        
        // Penalize extreme probabilities (they're often less reliable)
        if ($probability < 0.2 || $probability > 0.8) {
            $accuracy *= 0.8;
        }
        
        return min(100, $accuracy);
    }

    /**
     * Calculate confidence level in the probability estimate
     */
    private function calculateConfidenceLevel(int $sampleSize, float $accuracy): float
    {
        if ($sampleSize < 10) {
            return max(0, $sampleSize * 5); // Very low confidence for small samples
        }
        
        // Base confidence on sample size and accuracy
        $baseConfidence = min(95, $sampleSize);
        $adjustedConfidence = $baseConfidence * ($accuracy / 100);
        
        return max(0, $adjustedConfidence);
    }

    /**
     * Calculate weighted average
     */
    private function calculateWeightedAverage(array $values, array $weights): float
    {
        if (count($values) !== count($weights) || empty($values)) {
            return 0;
        }
        
        $weightedSum = 0;
        $totalWeight = 0;
        
        for ($i = 0; $i < count($values); $i++) {
            $weightedSum += $values[$i] * $weights[$i];
            $totalWeight += $weights[$i];
        }
        
        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
    }

    /**
     * Analyze probability trend over time
     */
    public function analyzeTrend(Collection $data, int $window = 20, int $periods = 10): array
    {
        if ($data->count() < $window + $periods) {
            return [];
        }

        $trendData = [];
        
        for ($i = 0; $i < $periods; $i++) {
            $endIndex = $data->count() - 1 - $i;
            $subset = $data->slice(max(0, $endIndex - $window), $window + 1);
            
            $result = $this->calculate($subset, $window);
            if ($result['shannon_probability'] !== null) {
                $trendData[] = [
                    'period_ago' => $i,
                    'shannon_probability' => $result['shannon_probability'],
                    'effective_probability' => $result['effective_probability'],
                    'confidence_level' => $result['confidence_level']
                ];
            }
        }

        // Calculate trend direction
        if (count($trendData) >= 2) {
            $recent = $trendData[0]['shannon_probability'];
            $older = $trendData[count($trendData) - 1]['shannon_probability'];
            
            $trendDirection = 'stable';
            if ($recent > $older + 0.05) {
                $trendDirection = 'increasing';
            } elseif ($recent < $older - 0.05) {
                $trendDirection = 'decreasing';
            }
            
            return [
                'trend_data' => array_reverse($trendData),
                'trend_direction' => $trendDirection,
                'trend_strength' => abs($recent - $older)
            ];
        }

        return ['trend_data' => array_reverse($trendData)];
    }

    /**
     * Calculate optimal prediction horizon based on Shannon probability
     */
    public function calculateOptimalHorizon(Collection $data, int $maxWindow = 50): array
    {
        $horizonResults = [];
        
        for ($window = 5; $window <= min($maxWindow, $data->count() - 1); $window += 5) {
            $result = $this->calculate($data, $window);
            
            if ($result['shannon_probability'] !== null) {
                $horizonResults[] = [
                    'window' => $window,
                    'shannon_probability' => $result['shannon_probability'],
                    'accuracy_estimate' => $result['accuracy_estimate'],
                    'confidence_level' => $result['confidence_level'],
                    'entropy' => $result['entropy']
                ];
            }
        }

        // Find optimal window (highest accuracy * confidence)
        $optimalWindow = null;
        $bestScore = 0;
        
        foreach ($horizonResults as $result) {
            $score = $result['accuracy_estimate'] * $result['confidence_level'] / 100;
            if ($score > $bestScore) {
                $bestScore = $score;
                $optimalWindow = $result['window'];
            }
        }

        return [
            'horizon_analysis' => $horizonResults,
            'optimal_window' => $optimalWindow,
            'optimal_score' => $bestScore
        ];
    }
}
