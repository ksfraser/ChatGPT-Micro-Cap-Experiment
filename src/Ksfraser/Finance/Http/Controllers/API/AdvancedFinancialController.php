<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AdvancedTechnicalIndicators;
use App\Services\PortfolioRiskManager;
use App\Services\ShannonAnalysis;
use App\Services\BacktestEngine;
use App\Models\Quote;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Enhanced API Controller for Advanced Financial Features
 * 
 * Provides endpoints for advanced technical indicators, risk management,
 * Shannon probability analysis, and backtesting capabilities.
 */
class AdvancedFinancialController extends Controller
{
    private $technicalIndicators;
    private $riskManager;
    private $shannonAnalysis;
    private $backtestEngine;

    public function __construct()
    {
        $this->technicalIndicators = new AdvancedTechnicalIndicators();
        $this->riskManager = new PortfolioRiskManager();
        $this->shannonAnalysis = new ShannonAnalysis();
        $this->backtestEngine = new BacktestEngine();
    }

    /**
     * Get Advanced Technical Indicators
     */
    public function getAdvancedIndicators(Request $request, string $symbol): JsonResponse
    {
        try {
            $period = $request->get('period', 14);
            $limit = $request->get('limit', 100);
            
            $quotes = Quote::where('symbol', $symbol)
                          ->orderBy('date', 'desc')
                          ->limit($limit)
                          ->get()
                          ->reverse()
                          ->values();

            if ($quotes->isEmpty()) {
                return response()->json(['error' => 'No data found for symbol'], 404);
            }

            $indicators = [];

            // ADX (Average Directional Index)
            if ($request->has('adx') || $request->get('all', false)) {
                $indicators['adx'] = $this->technicalIndicators->calculateADX($quotes, $period);
            }

            // Bollinger Bands
            if ($request->has('bollinger') || $request->get('all', false)) {
                $bbPeriod = $request->get('bb_period', 20);
                $bbStdDev = $request->get('bb_std_dev', 2);
                $indicators['bollinger_bands'] = $this->technicalIndicators
                    ->calculateBollingerBands($quotes, $bbPeriod, $bbStdDev);
            }

            // Stochastic Oscillator
            if ($request->has('stochastic') || $request->get('all', false)) {
                $kPeriod = $request->get('k_period', 14);
                $dPeriod = $request->get('d_period', 3);
                $indicators['stochastic'] = $this->technicalIndicators
                    ->calculateStochastic($quotes, $kPeriod, $dPeriod);
            }

            // Williams %R
            if ($request->has('williams_r') || $request->get('all', false)) {
                $indicators['williams_r'] = $this->technicalIndicators
                    ->calculateWilliamsR($quotes, $period);
            }

            // CCI (Commodity Channel Index)
            if ($request->has('cci') || $request->get('all', false)) {
                $indicators['cci'] = $this->technicalIndicators->calculateCCI($quotes, $period);
            }

            // ROC (Rate of Change)
            if ($request->has('roc') || $request->get('all', false)) {
                $indicators['roc'] = $this->technicalIndicators->calculateROC($quotes, $period);
            }

            // OBV (On Balance Volume)
            if ($request->has('obv') || $request->get('all', false)) {
                $indicators['obv'] = $this->technicalIndicators->calculateOBV($quotes);
            }

            // Parabolic SAR
            if ($request->has('sar') || $request->get('all', false)) {
                $acceleration = $request->get('sar_acceleration', 0.02);
                $maximum = $request->get('sar_maximum', 0.20);
                $indicators['parabolic_sar'] = $this->technicalIndicators
                    ->calculateParabolicSAR($quotes, $acceleration, $maximum);
            }

            // Aroon Oscillator
            if ($request->has('aroon') || $request->get('all', false)) {
                $indicators['aroon'] = $this->technicalIndicators->calculateAroon($quotes, $period);
            }

            // ATR (Average True Range)
            if ($request->has('atr') || $request->get('all', false)) {
                $indicators['atr'] = $this->technicalIndicators->calculateATR($quotes, $period);
            }

            return response()->json([
                'symbol' => $symbol,
                'period' => $period,
                'data_points' => $quotes->count(),
                'indicators' => $indicators
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get Shannon Probability Analysis
     */
    public function getShannonAnalysis(Request $request, string $symbol): JsonResponse
    {
        try {
            $window = $request->get('window', 20);
            $limit = $request->get('limit', 100);
            
            $quotes = Quote::where('symbol', $symbol)
                          ->orderBy('date', 'desc')
                          ->limit($limit)
                          ->get()
                          ->reverse()
                          ->values();

            if ($quotes->isEmpty()) {
                return response()->json(['error' => 'No data found for symbol'], 404);
            }

            // Convert to array format for Shannon analysis
            $priceData = $quotes->map(function($quote) {
                return [
                    'date' => $quote->date,
                    'close' => $quote->close,
                    'high' => $quote->high,
                    'low' => $quote->low,
                    'volume' => $quote->volume
                ];
            })->toArray();

            $shannonResults = $this->technicalIndicators->calculateShannonProbability($quotes, $window);

            // Additional Shannon analysis
            $persistenceAnalysis = $this->shannonAnalysis->analyzeMarketPersistence($priceData);
            $meanReversionAnalysis = $this->shannonAnalysis->detectMeanReversion($priceData);

            return response()->json([
                'symbol' => $symbol,
                'window' => $window,
                'shannon_probability' => $shannonResults,
                'persistence_analysis' => $persistenceAnalysis,
                'mean_reversion_analysis' => $meanReversionAnalysis
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Calculate Portfolio Risk Metrics
     */
    public function getPortfolioRiskMetrics(Request $request, int $portfolioId): JsonResponse
    {
        try {
            $portfolio = Portfolio::findOrFail($portfolioId);
            $period = $request->get('period', 252); // 1 year default
            
            // Get portfolio holdings and historical returns
            $holdings = $portfolio->holdings()->with('quote')->get();
            $portfolioReturns = $this->calculatePortfolioReturns($holdings, $period);
            
            if (empty($portfolioReturns)) {
                return response()->json(['error' => 'Insufficient data for risk calculation'], 400);
            }

            // Calculate various risk metrics
            $confidence95 = $request->get('confidence_95', 0.95);
            $confidence99 = $request->get('confidence_99', 0.99);
            $riskFreeRate = $request->get('risk_free_rate', 0.02);

            $var95 = $this->riskManager->calculateVaR($portfolioReturns, $confidence95);
            $var99 = $this->riskManager->calculateVaR($portfolioReturns, $confidence99);
            $sharpeRatio = $this->riskManager->calculateSharpeRatio($portfolioReturns, $riskFreeRate);
            
            // Portfolio value history for drawdown calculation
            $portfolioValues = $this->getPortfolioValueHistory($holdings, $period);
            $maxDrawdown = $this->riskManager->calculateMaxDrawdown($portfolioValues);

            // Concentration risk
            $weights = $this->getPortfolioWeights($holdings);
            $concentrationRisk = $this->riskManager->calculateConcentrationRisk($weights);

            return response()->json([
                'portfolio_id' => $portfolioId,
                'analysis_period' => $period,
                'var_analysis' => [
                    'var_95' => $var95,
                    'var_99' => $var99
                ],
                'performance_metrics' => [
                    'sharpe_ratio' => $sharpeRatio,
                    'max_drawdown' => $maxDrawdown,
                    'volatility' => sqrt($this->calculateVariance($portfolioReturns))
                ],
                'concentration_risk' => $concentrationRisk,
                'portfolio_weights' => $weights
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Optimize Portfolio Allocation
     */
    public function optimizePortfolio(Request $request): JsonResponse
    {
        try {
            $symbols = $request->get('symbols', []);
            $method = $request->get('method', 'shannon');
            $riskTolerance = $request->get('risk_tolerance', 0.5);
            
            if (empty($symbols)) {
                return response()->json(['error' => 'No symbols provided'], 400);
            }

            $equityData = [];
            
            // Gather data for each equity
            foreach ($symbols as $symbol) {
                $quotes = Quote::where('symbol', $symbol)
                              ->orderBy('date', 'desc')
                              ->limit(100)
                              ->get()
                              ->reverse()
                              ->values();

                if ($quotes->isNotEmpty()) {
                    $priceData = $quotes->map(function($quote) {
                        return [
                            'date' => $quote->date,
                            'close' => $quote->close,
                            'high' => $quote->high,
                            'low' => $quote->low,
                            'volume' => $quote->volume
                        ];
                    })->toArray();

                    $equityData[$symbol] = ['prices' => $priceData];
                }
            }

            if (empty($equityData)) {
                return response()->json(['error' => 'No valid data found for symbols'], 400);
            }

            // Perform optimization
            $optimization = $this->shannonAnalysis->optimizePortfolioAllocation($equityData, $method);

            // Calculate expected metrics
            $expectedReturn = 0;
            $expectedRisk = 0;
            
            foreach ($optimization['weights'] as $symbol => $weight) {
                $returns = $this->calculateReturns($equityData[$symbol]['prices']);
                if (!empty($returns)) {
                    $expectedReturn += $weight * (array_sum($returns) / count($returns)) * 252;
                    $volatility = sqrt($this->calculateVariance($returns));
                    $expectedRisk += $weight * $weight * $volatility * $volatility * 252;
                }
            }
            
            $expectedRisk = sqrt($expectedRisk);

            return response()->json([
                'optimization_method' => $method,
                'symbols' => $symbols,
                'optimal_weights' => $optimization['weights'],
                'expected_return' => $expectedReturn,
                'expected_risk' => $expectedRisk,
                'sharpe_ratio' => $expectedRisk > 0 ? $expectedReturn / $expectedRisk : 0,
                'optimization_details' => $optimization
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Run Strategy Backtest
     */
    public function runBacktest(Request $request): JsonResponse
    {
        try {
            $symbol = $request->get('symbol');
            $strategy = $request->get('strategy', 'simple_ma');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $initialCapital = $request->get('initial_capital', 100000);
            $parameters = $request->get('parameters', []);

            if (!$symbol) {
                return response()->json(['error' => 'Symbol is required'], 400);
            }

            // Get historical data
            $query = Quote::where('symbol', $symbol)->orderBy('date');
            
            if ($startDate) {
                $query->where('date', '>=', $startDate);
            }
            if ($endDate) {
                $query->where('date', '<=', $endDate);
            }

            $quotes = $query->get();

            if ($quotes->isEmpty()) {
                return response()->json(['error' => 'No data found for the specified period'], 404);
            }

            // Create strategy instance (simplified)
            $strategyInstance = $this->createStrategy($strategy, $parameters);
            
            // Run backtest
            $backtestEngine = new BacktestEngine($initialCapital);
            $results = $backtestEngine->runBacktest($strategyInstance, $quotes, $parameters);

            return response()->json([
                'symbol' => $symbol,
                'strategy' => $strategy,
                'parameters' => $parameters,
                'period' => [
                    'start' => $startDate ?: $quotes->first()->date,
                    'end' => $endDate ?: $quotes->last()->date
                ],
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get Correlation Matrix
     */
    public function getCorrelationMatrix(Request $request): JsonResponse
    {
        try {
            $symbols = $request->get('symbols', []);
            $period = $request->get('period', 252);

            if (count($symbols) < 2) {
                return response()->json(['error' => 'At least 2 symbols required'], 400);
            }

            $assetReturns = [];

            foreach ($symbols as $symbol) {
                $quotes = Quote::where('symbol', $symbol)
                              ->orderBy('date', 'desc')
                              ->limit($period)
                              ->get()
                              ->reverse()
                              ->values();

                if ($quotes->count() >= $period) {
                    $returns = [];
                    for ($i = 1; $i < $quotes->count(); $i++) {
                        $returns[] = ($quotes[$i]->close - $quotes[$i-1]->close) / $quotes[$i-1]->close;
                    }
                    $assetReturns[$symbol] = $returns;
                }
            }

            if (count($assetReturns) < 2) {
                return response()->json(['error' => 'Insufficient data for correlation analysis'], 400);
            }

            $correlationMatrix = $this->riskManager->calculateCorrelationMatrix($assetReturns);

            return response()->json([
                'symbols' => array_keys($assetReturns),
                'period' => $period,
                'correlation_matrix' => $correlationMatrix
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper method to calculate portfolio returns
     */
    private function calculatePortfolioReturns($holdings, int $period): array
    {
        // Simplified portfolio return calculation
        // In practice, this would be more sophisticated
        $returns = [];
        
        if ($holdings->isEmpty()) {
            return $returns;
        }

        // For simplicity, use the first holding's returns as proxy
        $firstHolding = $holdings->first();
        $quotes = Quote::where('symbol', $firstHolding->symbol)
                      ->orderBy('date', 'desc')
                      ->limit($period)
                      ->get()
                      ->reverse()
                      ->values();

        for ($i = 1; $i < $quotes->count(); $i++) {
            $returns[] = ($quotes[$i]->close - $quotes[$i-1]->close) / $quotes[$i-1]->close;
        }

        return $returns;
    }

    /**
     * Helper method to get portfolio value history
     */
    private function getPortfolioValueHistory($holdings, int $period): array
    {
        // Simplified - returns mock values
        // In practice, this would calculate actual portfolio values over time
        $values = [];
        $baseValue = 100000;
        
        for ($i = 0; $i < $period; $i++) {
            $randomChange = (mt_rand() / mt_getrandmax() - 0.5) * 0.04; // ±2% random change
            $baseValue *= (1 + $randomChange);
            $values[] = $baseValue;
        }
        
        return $values;
    }

    /**
     * Helper method to get portfolio weights
     */
    private function getPortfolioWeights($holdings): array
    {
        $totalValue = $holdings->sum(function($holding) {
            return $holding->quantity * ($holding->quote->close ?? 0);
        });

        $weights = [];
        foreach ($holdings as $holding) {
            $value = $holding->quantity * ($holding->quote->close ?? 0);
            $weights[] = $totalValue > 0 ? $value / $totalValue : 0;
        }

        return $weights;
    }

    /**
     * Helper method to calculate variance
     */
    private function calculateVariance(array $returns): float
    {
        if (empty($returns)) return 0;
        
        $mean = array_sum($returns) / count($returns);
        $sum = 0;
        
        foreach ($returns as $return) {
            $sum += pow($return - $mean, 2);
        }
        
        return $sum / (count($returns) - 1);
    }

    /**
     * Helper method to calculate returns from price data
     */
    private function calculateReturns(array $priceData): array
    {
        $returns = [];
        for ($i = 1; $i < count($priceData); $i++) {
            $current = $priceData[$i]['close'];
            $previous = $priceData[$i-1]['close'];
            $returns[] = ($current - $previous) / $previous;
        }
        return $returns;
    }

    /**
     * Helper method to create strategy instance
     */
    private function createStrategy(string $strategyName, array $parameters)
    {
        // This would be implemented with actual strategy classes
        // For now, return a mock strategy
        return new class {
            public function getSignals($data, $parameters = []) {
                return []; // Mock implementation
            }
            public function getParameterRanges() {
                return []; // Mock implementation
            }
        };
    }
}
