<?php

namespace App\Services\Calculators;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Single Responsibility: Calculate portfolio returns
 * Follows SRP by handling only portfolio return calculations
 */
class PortfolioReturnsCalculator
{
    /**
     * Calculate portfolio returns based on holdings and their historical data
     */
    public function calculateReturns(Collection $holdings, int $period): array
    {
        if ($holdings->isEmpty()) {
            return [];
        }

        $portfolioReturns = [];
        $totalValue = $this->calculateTotalPortfolioValue($holdings);
        
        if ($totalValue <= 0) {
            return [];
        }

        // Get the earliest common date across all holdings
        $dateRange = $this->getCommonDateRange($holdings, $period);
        
        if (empty($dateRange)) {
            return [];
        }

        // Calculate weighted returns for each date
        foreach ($dateRange as $i => $date) {
            if ($i === 0) continue; // Skip first date as we need previous for return calculation
            
            $portfolioReturn = 0;
            $previousDate = $dateRange[$i - 1];
            
            foreach ($holdings as $holding) {
                $weight = $this->calculateHoldingWeight($holding, $totalValue);
                $stockReturn = $this->calculateStockReturn($holding->symbol, $previousDate, $date);
                $portfolioReturn += $weight * $stockReturn;
            }
            
            $portfolioReturns[] = $portfolioReturn;
        }

        return $portfolioReturns;
    }

    /**
     * Calculate individual stock return between two dates
     */
    private function calculateStockReturn(string $symbol, string $previousDate, string $currentDate): float
    {
        // This would query the {symbol}_prices table
        // For now, simplified implementation
        $tableName = $symbol . '_prices';
        
        try {
            $previousPrice = DB::table($tableName)
                ->where('date', $previousDate)
                ->value('close');
                
            $currentPrice = DB::table($tableName)
                ->where('date', $currentDate)
                ->value('close');

            if ($previousPrice && $currentPrice && $previousPrice > 0) {
                return ($currentPrice - $previousPrice) / $previousPrice;
            }
        } catch (\Exception $e) {
            // Handle case where symbol table doesn't exist
            return 0;
        }

        return 0;
    }

    /**
     * Calculate the weight of a holding in the portfolio
     */
    private function calculateHoldingWeight($holding, float $totalValue): float
    {
        if ($totalValue <= 0) {
            return 0;
        }

        $holdingValue = $holding->quantity * ($holding->quote->close ?? 0);
        return $holdingValue / $totalValue;
    }

    /**
     * Calculate total portfolio value
     */
    private function calculateTotalPortfolioValue(Collection $holdings): float
    {
        return $holdings->sum(function($holding) {
            return $holding->quantity * ($holding->quote->close ?? 0);
        });
    }

    /**
     * Get common date range across all holdings
     */
    private function getCommonDateRange(Collection $holdings, int $period): array
    {
        // Simplified implementation - would need to find common dates across all symbol tables
        // For now, return a mock date range
        $dates = [];
        $startDate = Carbon::now()->subDays($period);
        
        for ($i = 0; $i < $period; $i++) {
            $dates[] = $startDate->addDay()->format('Y-m-d');
        }
        
        return $dates;
    }
}
