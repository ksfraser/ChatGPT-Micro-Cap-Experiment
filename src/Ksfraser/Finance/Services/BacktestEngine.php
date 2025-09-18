<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\Trade;
use Illuminate\Support\Collection;

/**
 * Backtesting Engine
 * 
 * Implements comprehensive backtesting capabilities found in GeniusTrader
 * and other professional trading systems.
 */
class BacktestEngine
{
    private $commission;
    private $slippage;
    private $initialCapital;
    private $trades;
    private $portfolioValues;
    private $positions;
    
    public function __construct(float $initialCapital = 100000, float $commission = 0.001, float $slippage = 0.0005)
    {
        $this->initialCapital = $initialCapital;
        $this->commission = $commission;
        $this->slippage = $slippage;
        $this->trades = [];
        $this->portfolioValues = [];
        $this->positions = [];
    }

    /**
     * Run a complete backtest on a trading strategy
     */
    public function runBacktest($strategy, $data, array $parameters = []): array
    {
        $this->resetBacktest();
        $currentCapital = $this->initialCapital;
        $currentDate = null;
        
        foreach ($data as $index => $quote) {
            $currentDate = $quote->date;
            
            // Get strategy signals
            $signals = $strategy->getSignals($data->slice(0, $index + 1), $parameters);
            
            // Process signals
            foreach ($signals as $signal) {
                if ($signal['action'] === 'buy') {
                    $currentCapital = $this->executeBuy($signal, $quote, $currentCapital);
                } elseif ($signal['action'] === 'sell') {
                    $currentCapital = $this->executeSell($signal, $quote, $currentCapital);
                }
            }
            
            // Update portfolio value
            $portfolioValue = $this->calculatePortfolioValue($currentCapital, $quote);
            $this->portfolioValues[] = [
                'date' => $currentDate,
                'value' => $portfolioValue,
                'cash' => $currentCapital,
                'positions' => $this->positions
            ];
        }
        
        return $this->generateBacktestResults($currentDate);
    }

    /**
     * Execute a buy order with commission and slippage
     */
    private function executeBuy(array $signal, $quote, float $capital): float
    {
        $symbol = $signal['symbol'];
        $quantity = $signal['quantity'] ?? $this->calculatePositionSize($signal, $capital, $quote->close);
        
        // Apply slippage
        $executionPrice = $quote->close * (1 + $this->slippage);
        
        // Calculate total cost including commission
        $grossCost = $quantity * $executionPrice;
        $commissionCost = $grossCost * $this->commission;
        $totalCost = $grossCost + $commissionCost;
        
        if ($totalCost <= $capital) {
            // Execute trade
            $this->trades[] = [
                'date' => $quote->date,
                'symbol' => $symbol,
                'action' => 'buy',
                'quantity' => $quantity,
                'price' => $executionPrice,
                'gross_amount' => $grossCost,
                'commission' => $commissionCost,
                'total_amount' => $totalCost,
                'reason' => $signal['reason'] ?? 'strategy_signal'
            ];
            
            // Update positions
            if (isset($this->positions[$symbol])) {
                $this->positions[$symbol]['quantity'] += $quantity;
                $this->positions[$symbol]['avg_cost'] = 
                    (($this->positions[$symbol]['avg_cost'] * ($this->positions[$symbol]['quantity'] - $quantity)) + $grossCost) 
                    / $this->positions[$symbol]['quantity'];
            } else {
                $this->positions[$symbol] = [
                    'quantity' => $quantity,
                    'avg_cost' => $executionPrice,
                    'current_price' => $quote->close
                ];
            }
            
            return $capital - $totalCost;
        }
        
        return $capital;
    }

    /**
     * Execute a sell order with commission and slippage
     */
    private function executeSell(array $signal, $quote, float $capital): float
    {
        $symbol = $signal['symbol'];
        $quantity = $signal['quantity'] ?? ($this->positions[$symbol]['quantity'] ?? 0);
        
        if (!isset($this->positions[$symbol]) || $this->positions[$symbol]['quantity'] < $quantity) {
            return $capital; // Cannot sell what we don't have
        }
        
        // Apply slippage
        $executionPrice = $quote->close * (1 - $this->slippage);
        
        // Calculate proceeds
        $grossProceeds = $quantity * $executionPrice;
        $commissionCost = $grossProceeds * $this->commission;
        $netProceeds = $grossProceeds - $commissionCost;
        
        // Execute trade
        $this->trades[] = [
            'date' => $quote->date,
            'symbol' => $symbol,
            'action' => 'sell',
            'quantity' => $quantity,
            'price' => $executionPrice,
            'gross_amount' => $grossProceeds,
            'commission' => $commissionCost,
            'net_amount' => $netProceeds,
            'cost_basis' => $this->positions[$symbol]['avg_cost'],
            'profit_loss' => ($executionPrice - $this->positions[$symbol]['avg_cost']) * $quantity,
            'reason' => $signal['reason'] ?? 'strategy_signal'
        ];
        
        // Update positions
        $this->positions[$symbol]['quantity'] -= $quantity;
        if ($this->positions[$symbol]['quantity'] <= 0) {
            unset($this->positions[$symbol]);
        }
        
        return $capital + $netProceeds;
    }

    /**
     * Calculate position size based on strategy
     */
    private function calculatePositionSize(array $signal, float $capital, float $price): int
    {
        $sizingMethod = $signal['sizing_method'] ?? 'fixed_dollar';
        $sizingValue = $signal['sizing_value'] ?? 10000;
        
        switch ($sizingMethod) {
            case 'fixed_dollar':
                return (int) floor($sizingValue / $price);
                
            case 'percent_capital':
                $dollarsToInvest = $capital * ($sizingValue / 100);
                return (int) floor($dollarsToInvest / $price);
                
            case 'kelly_criterion':
                // Simplified Kelly criterion calculation
                $winRate = $signal['win_rate'] ?? 0.6;
                $avgWin = $signal['avg_win'] ?? 0.1;
                $avgLoss = $signal['avg_loss'] ?? 0.05;
                
                $kellyPercent = ($winRate * $avgWin - (1 - $winRate) * $avgLoss) / $avgWin;
                $dollarsToInvest = $capital * max(0, min(0.25, $kellyPercent)); // Cap at 25%
                return (int) floor($dollarsToInvest / $price);
                
            case 'volatility_adjusted':
                $volatility = $signal['volatility'] ?? 0.2;
                $targetVolatility = $signal['target_volatility'] ?? 0.1;
                $adjustment = $targetVolatility / $volatility;
                $dollarsToInvest = $capital * 0.1 * $adjustment; // Base 10% position
                return (int) floor($dollarsToInvest / $price);
                
            default:
                return (int) floor($sizingValue / $price);
        }
    }

    /**
     * Calculate current portfolio value
     */
    private function calculatePortfolioValue(float $cash, $currentQuote): float
    {
        $positionValue = 0;
        
        foreach ($this->positions as $symbol => $position) {
            // In a real system, we'd fetch current prices for all positions
            // For this example, we'll assume the current quote applies to all
            $positionValue += $position['quantity'] * $currentQuote->close;
        }
        
        return $cash + $positionValue;
    }

    /**
     * Generate comprehensive backtest results
     */
    private function generateBacktestResults(string $endDate): array
    {
        if (empty($this->portfolioValues)) {
            return [];
        }

        $initialValue = $this->initialCapital;
        $finalValue = end($this->portfolioValues)['value'];
        $totalReturn = ($finalValue - $initialValue) / $initialValue;
        
        // Calculate returns series
        $returns = [];
        for ($i = 1; $i < count($this->portfolioValues); $i++) {
            $prevValue = $this->portfolioValues[$i - 1]['value'];
            $currentValue = $this->portfolioValues[$i]['value'];
            $returns[] = ($currentValue - $prevValue) / $prevValue;
        }
        
        // Calculate metrics
        $winningTrades = array_filter($this->trades, function($trade) {
            return isset($trade['profit_loss']) && $trade['profit_loss'] > 0;
        });
        
        $losingTrades = array_filter($this->trades, function($trade) {
            return isset($trade['profit_loss']) && $trade['profit_loss'] < 0;
        });
        
        $totalTrades = count(array_filter($this->trades, function($trade) {
            return isset($trade['profit_loss']);
        }));
        
        $winRate = $totalTrades > 0 ? count($winningTrades) / $totalTrades : 0;
        
        $avgWin = !empty($winningTrades) ? 
            array_sum(array_column($winningTrades, 'profit_loss')) / count($winningTrades) : 0;
        
        $avgLoss = !empty($losingTrades) ? 
            array_sum(array_column($losingTrades, 'profit_loss')) / count($losingTrades) : 0;
        
        $profitFactor = ($avgLoss != 0) ? abs($avgWin * count($winningTrades)) / abs($avgLoss * count($losingTrades)) : 0;
        
        // Calculate Sharpe ratio
        $avgReturn = !empty($returns) ? array_sum($returns) / count($returns) : 0;
        $stdDev = $this->calculateStandardDeviation($returns);
        $sharpeRatio = ($stdDev != 0) ? ($avgReturn * sqrt(252)) / ($stdDev * sqrt(252)) : 0;
        
        // Calculate maximum drawdown
        $maxDrawdown = $this->calculateMaxDrawdown(array_column($this->portfolioValues, 'value'));
        
        return [
            'initial_capital' => $initialValue,
            'final_capital' => $finalValue,
            'total_return' => $totalReturn,
            'total_return_percent' => $totalReturn * 100,
            'annualized_return' => $this->calculateAnnualizedReturn($totalReturn, count($this->portfolioValues)),
            'max_drawdown' => $maxDrawdown,
            'sharpe_ratio' => $sharpeRatio,
            'win_rate' => $winRate,
            'profit_factor' => $profitFactor,
            'total_trades' => $totalTrades,
            'winning_trades' => count($winningTrades),
            'losing_trades' => count($losingTrades),
            'avg_win' => $avgWin,
            'avg_loss' => $avgLoss,
            'largest_win' => !empty($winningTrades) ? max(array_column($winningTrades, 'profit_loss')) : 0,
            'largest_loss' => !empty($losingTrades) ? min(array_column($losingTrades, 'profit_loss')) : 0,
            'total_commission' => array_sum(array_column($this->trades, 'commission')),
            'start_date' => $this->portfolioValues[0]['date'],
            'end_date' => $endDate,
            'portfolio_values' => $this->portfolioValues,
            'trades' => $this->trades,
            'final_positions' => $this->positions
        ];
    }

    /**
     * Perform walk-forward analysis
     */
    public function performWalkForwardAnalysis($strategy, $data, array $windows): array
    {
        $results = [];
        $trainingWindow = $windows['training'] ?? 252; // 1 year
        $testingWindow = $windows['testing'] ?? 63;   // 3 months
        $stepSize = $windows['step'] ?? 21;           // 1 month
        
        $totalPeriods = $data->count();
        $currentStart = 0;
        
        while ($currentStart + $trainingWindow + $testingWindow < $totalPeriods) {
            $trainingEnd = $currentStart + $trainingWindow;
            $testingEnd = $trainingEnd + $testingWindow;
            
            // Training data
            $trainingData = $data->slice($currentStart, $trainingWindow);
            
            // Optimize strategy parameters on training data
            $optimizedParams = $this->optimizeStrategy($strategy, $trainingData);
            
            // Test on out-of-sample data
            $testingData = $data->slice($trainingEnd, $testingWindow);
            $testResults = $this->runBacktest($strategy, $testingData, $optimizedParams);
            
            $results[] = [
                'training_start' => $trainingData->first()->date,
                'training_end' => $trainingData->last()->date,
                'testing_start' => $testingData->first()->date,
                'testing_end' => $testingData->last()->date,
                'optimized_params' => $optimizedParams,
                'test_results' => $testResults
            ];
            
            $currentStart += $stepSize;
        }
        
        return [
            'walk_forward_results' => $results,
            'summary' => $this->summarizeWalkForward($results)
        ];
    }

    /**
     * Run Monte Carlo simulation on strategy
     */
    public function runMonteCarloSimulation($strategy, $data, int $iterations = 1000): array
    {
        $results = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            // Bootstrap sampling of the data
            $shuffledData = $this->bootstrapSample($data);
            
            // Run backtest on shuffled data
            $result = $this->runBacktest($strategy, $shuffledData);
            $results[] = $result['total_return'];
        }
        
        sort($results);
        $count = count($results);
        
        return [
            'iterations' => $iterations,
            'mean_return' => array_sum($results) / $count,
            'std_dev' => $this->calculateStandardDeviation($results),
            'percentiles' => [
                '5th' => $results[(int)($count * 0.05)],
                '25th' => $results[(int)($count * 0.25)],
                '50th' => $results[(int)($count * 0.50)],
                '75th' => $results[(int)($count * 0.75)],
                '95th' => $results[(int)($count * 0.95)]
            ],
            'probability_of_loss' => count(array_filter($results, function($r) { return $r < 0; })) / $count,
            'worst_case' => min($results),
            'best_case' => max($results),
            'all_results' => $results
        ];
    }

    /**
     * Optimize strategy parameters
     */
    private function optimizeStrategy($strategy, $data): array
    {
        // This is a simplified optimization - in practice you'd use more sophisticated methods
        $parameterRanges = $strategy->getParameterRanges();
        $bestParams = [];
        $bestReturn = -PHP_FLOAT_MAX;
        
        // Grid search optimization
        $combinations = $this->generateParameterCombinations($parameterRanges);
        
        foreach ($combinations as $params) {
            $result = $this->runBacktest($strategy, $data, $params);
            
            // Optimize for risk-adjusted return
            $score = $result['total_return'] / max(0.01, $result['max_drawdown']['max_drawdown']);
            
            if ($score > $bestReturn) {
                $bestReturn = $score;
                $bestParams = $params;
            }
        }
        
        return $bestParams;
    }

    /**
     * Generate parameter combinations for optimization
     */
    private function generateParameterCombinations(array $ranges): array
    {
        // Simplified: generate a few key combinations
        // In practice, you'd use more sophisticated optimization algorithms
        $combinations = [];
        
        // For demonstration, create 10 random combinations
        for ($i = 0; $i < 10; $i++) {
            $combination = [];
            foreach ($ranges as $param => $range) {
                $min = $range['min'];
                $max = $range['max'];
                $step = $range['step'] ?? 1;
                
                $value = $min + (int)(rand() / getrandmax() * (($max - $min) / $step)) * $step;
                $combination[$param] = $value;
            }
            $combinations[] = $combination;
        }
        
        return $combinations;
    }

    /**
     * Bootstrap sample from data
     */
    private function bootstrapSample($data)
    {
        $sample = array();
        $count = count($data);
        
        for ($i = 0; $i < $count; $i++) {
            $randomIndex = rand(0, $count - 1);
            $sample[] = $data[$randomIndex];
        }
        
        return $sample;
    }

    /**
     * Calculate standard deviation
     */
    private function calculateStandardDeviation(array $values): float
    {
        if (empty($values)) return 0;
        
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values)) / count($values);
        
        return sqrt($variance);
    }

    /**
     * Calculate maximum drawdown
     */
    private function calculateMaxDrawdown(array $values): array
    {
        $maxDrawdown = 0;
        $peak = $values[0];
        $peakIndex = 0;
        $troughIndex = 0;
        
        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i] > $peak) {
                $peak = $values[$i];
                $peakIndex = $i;
            } else {
                $drawdown = ($peak - $values[$i]) / $peak;
                if ($drawdown > $maxDrawdown) {
                    $maxDrawdown = $drawdown;
                    $troughIndex = $i;
                }
            }
        }
        
        return [
            'max_drawdown' => $maxDrawdown,
            'max_drawdown_percent' => $maxDrawdown * 100,
            'peak_index' => $peakIndex,
            'trough_index' => $troughIndex,
            'duration' => $troughIndex - $peakIndex
        ];
    }

    /**
     * Calculate annualized return
     */
    private function calculateAnnualizedReturn(float $totalReturn, int $periods): float
    {
        $years = $periods / 252; // Assuming 252 trading days per year
        return $years > 0 ? pow(1 + $totalReturn, 1 / $years) - 1 : 0;
    }

    /**
     * Summarize walk-forward analysis results
     */
    private function summarizeWalkForward(array $results): array
    {
        if (empty($results)) return [];
        
        $returns = array_column(array_column($results, 'test_results'), 'total_return');
        $sharpeRatios = array_column(array_column($results, 'test_results'), 'sharpe_ratio');
        $maxDrawdowns = array_column(array_column($results, 'test_results'), 'max_drawdown');
        
        return [
            'total_periods' => count($results),
            'avg_return' => array_sum($returns) / count($returns),
            'avg_sharpe' => array_sum($sharpeRatios) / count($sharpeRatios),
            'avg_max_drawdown' => array_sum(array_column($maxDrawdowns, 'max_drawdown')) / count($maxDrawdowns),
            'consistency' => $this->calculateConsistency($returns),
            'win_rate' => count(array_filter($returns, function($r) { return $r > 0; })) / count($returns)
        ];
    }

    /**
     * Calculate strategy consistency
     */
    private function calculateConsistency(array $returns): float
    {
        if (empty($returns)) return 0;
        
        $positiveReturns = count(array_filter($returns, function($r) { return $r > 0; }));
        return $positiveReturns / count($returns);
    }

    /**
     * Reset backtest state
     */
    private function resetBacktest(): void
    {
        $this->trades = [];
        $this->portfolioValues = [];
        $this->positions = [];
    }
}

/**
 * Abstract Trading Strategy Interface
 */
abstract class TradingStrategy
{
    abstract public function getSignals(Collection $data, array $parameters = []): array;
    abstract public function getParameterRanges(): array;
}
