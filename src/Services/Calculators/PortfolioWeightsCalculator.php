<?php

namespace App\Services\Calculators;

/**
 * Single Responsibility: Calculate portfolio weights and allocations
 * Follows SRP by handling only weight-related calculations
 */
class PortfolioWeightsCalculator
{
    /**
     * Calculate current portfolio weights based on market values
     */
    public function calculateCurrentWeights($holdings): array
    {
        if ($holdings->isEmpty()) {
            return [];
        }

        $totalValue = $this->calculateTotalPortfolioValue($holdings);
        
        if ($totalValue <= 0) {
            return [];
        }

        $weights = [];
        
        foreach ($holdings as $holding) {
            $holdingValue = $holding->quantity * ($holding->quote->close ?? 0);
            $weight = $holdingValue / $totalValue;
            
            $weights[] = [
                'symbol' => $holding->symbol,
                'weight' => $weight,
                'value' => $holdingValue,
                'quantity' => $holding->quantity,
                'price' => $holding->quote->close ?? 0
            ];
        }
        
        return $weights;
    }

    /**
     * Calculate portfolio weights as simple array (for calculations)
     */
    public function calculateWeightsArray($holdings): array
    {
        $weights = $this->calculateCurrentWeights($holdings);
        return array_column($weights, 'weight');
    }

    /**
     * Calculate concentration risk (Herfindahl-Hirschman Index)
     */
    public function calculateConcentrationRisk($holdings): array
    {
        $weights = $this->calculateWeightsArray($holdings);
        
        if (empty($weights)) {
            return [
                'hhi_index' => 0,
                'concentration_level' => 'N/A',
                'largest_position' => 0,
                'top_5_concentration' => 0
            ];
        }

        // Calculate HHI (Herfindahl-Hirschman Index)
        $hhi = 0;
        foreach ($weights as $weight) {
            $hhi += pow($weight, 2);
        }

        // Sort weights to find largest positions
        rsort($weights);
        
        $largestPosition = $weights[0] ?? 0;
        $top5Concentration = array_sum(array_slice($weights, 0, min(5, count($weights))));
        
        // Determine concentration level
        $concentrationLevel = $this->getConcentrationLevel($hhi);

        return [
            'hhi_index' => $hhi,
            'concentration_level' => $concentrationLevel,
            'largest_position' => $largestPosition,
            'top_5_concentration' => $top5Concentration,
            'number_of_positions' => count($weights)
        ];
    }

    /**
     * Rebalance portfolio to target weights
     */
    public function calculateRebalancing($holdings, array $targetWeights): array
    {
        $currentWeights = $this->calculateCurrentWeights($holdings);
        $totalValue = $this->calculateTotalPortfolioValue($holdings);
        
        $rebalancing = [];
        
        foreach ($targetWeights as $symbol => $targetWeight) {
            $currentWeight = 0;
            $currentValue = 0;
            $currentPrice = 0;
            
            // Find current position
            foreach ($currentWeights as $position) {
                if ($position['symbol'] === $symbol) {
                    $currentWeight = $position['weight'];
                    $currentValue = $position['value'];
                    $currentPrice = $position['price'];
                    break;
                }
            }
            
            $targetValue = $totalValue * $targetWeight;
            $rebalanceAmount = $targetValue - $currentValue;
            
            $rebalancing[] = [
                'symbol' => $symbol,
                'current_weight' => $currentWeight,
                'target_weight' => $targetWeight,
                'current_value' => $currentValue,
                'target_value' => $targetValue,
                'rebalance_amount' => $rebalanceAmount,
                'shares_to_trade' => $currentPrice > 0 ? $rebalanceAmount / $currentPrice : 0,
                'action' => $rebalanceAmount > 0 ? 'BUY' : ($rebalanceAmount < 0 ? 'SELL' : 'HOLD')
            ];
        }
        
        return $rebalancing;
    }

    /**
     * Calculate equal weight allocation
     */
    public function calculateEqualWeights(array $symbols): array
    {
        if (empty($symbols)) {
            return [];
        }
        
        $equalWeight = 1.0 / count($symbols);
        $weights = [];
        
        foreach ($symbols as $symbol) {
            $weights[$symbol] = $equalWeight;
        }
        
        return $weights;
    }

    /**
     * Calculate market cap weighted allocation (if market cap data available)
     */
    public function calculateMarketCapWeights(array $symbolsWithMarketCap): array
    {
        if (empty($symbolsWithMarketCap)) {
            return [];
        }
        
        $totalMarketCap = array_sum(array_column($symbolsWithMarketCap, 'market_cap'));
        
        if ($totalMarketCap <= 0) {
            return $this->calculateEqualWeights(array_keys($symbolsWithMarketCap));
        }
        
        $weights = [];
        
        foreach ($symbolsWithMarketCap as $symbol => $data) {
            $weights[$symbol] = $data['market_cap'] / $totalMarketCap;
        }
        
        return $weights;
    }

    /**
     * Calculate total portfolio value
     */
    private function calculateTotalPortfolioValue($holdings): float
    {
        return $holdings->sum(function($holding) {
            return $holding->quantity * ($holding->quote->close ?? 0);
        });
    }

    /**
     * Determine concentration level based on HHI
     */
    private function getConcentrationLevel(float $hhi): string
    {
        if ($hhi < 0.15) {
            return 'Low Concentration';
        } elseif ($hhi < 0.25) {
            return 'Moderate Concentration';
        } elseif ($hhi < 0.50) {
            return 'High Concentration';
        } else {
            return 'Very High Concentration';
        }
    }
}
