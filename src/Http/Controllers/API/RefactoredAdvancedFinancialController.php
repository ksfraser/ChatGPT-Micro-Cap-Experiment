<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AdvancedTechnicalIndicators;
use App\Services\PortfolioRiskManager;
use App\Services\ShannonAnalysis;
use App\Services\BacktestEngine;
use App\Services\Calculators\PortfolioReturnsCalculator;
use App\Services\Calculators\VarianceCalculator;
use App\Services\Calculators\PortfolioWeightsCalculator;
use App\Repositories\IndicatorRepository;
use App\Models\Quote;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * REFACTORED: Enhanced API Controller following SOLID principles
 * 
 * Single Responsibility: This controller only handles HTTP requests/responses
 * and delegates calculations to specialized service classes.
 * 
 * Open/Closed: Easy to extend with new endpoints without modifying existing code
 * 
 * Dependency Inversion: Depends on abstractions (service interfaces) not concrete classes
 */
class RefactoredAdvancedFinancialController extends Controller
{
    private AdvancedTechnicalIndicators $technicalIndicators;
    private PortfolioRiskManager $riskManager;
    private ShannonAnalysis $shannonAnalysis;
    private BacktestEngine $backtestEngine;
    private PortfolioReturnsCalculator $returnsCalculator;
    private VarianceCalculator $varianceCalculator;
    private PortfolioWeightsCalculator $weightsCalculator;
    private IndicatorRepository $indicatorRepository;

    public function __construct(
        AdvancedTechnicalIndicators $technicalIndicators,
        PortfolioRiskManager $riskManager,
        ShannonAnalysis $shannonAnalysis,
        BacktestEngine $backtestEngine,
        PortfolioReturnsCalculator $returnsCalculator,
        VarianceCalculator $varianceCalculator,
        PortfolioWeightsCalculator $weightsCalculator,
        IndicatorRepository $indicatorRepository
    ) {
        $this->technicalIndicators = $technicalIndicators;
        $this->riskManager = $riskManager;
        $this->shannonAnalysis = $shannonAnalysis;
        $this->backtestEngine = $backtestEngine;
        $this->returnsCalculator = $returnsCalculator;
        $this->varianceCalculator = $varianceCalculator;
        $this->weightsCalculator = $weightsCalculator;
        $this->indicatorRepository = $indicatorRepository;
    }

    /**
     * Get Advanced Technical Indicators
     * Now properly delegates to service layer and stores results in per-symbol tables
     */
    public function getAdvancedIndicators(Request $request, string $symbol): JsonResponse
    {
        try {
            $period = $request->get('period', 14);
            $limit = $request->get('limit', 100);
            $forceRecalculate = $request->get('force_recalculate', false);
            
            // Check if we have recent cached indicators (unless force recalculate)
            if (!$forceRecalculate) {
                $cachedIndicators = $this->getCachedIndicators($symbol, $request);
                if (!empty($cachedIndicators)) {
                    return response()->json([
                        'symbol' => $symbol,
                        'source' => 'cached',
                        'indicators' => $cachedIndicators
                    ]);
                }
            }

            // Get historical price data for calculations
            $priceData = $this->indicatorRepository->getHistoricalPrices($symbol, $limit);
            
            if (empty($priceData)) {
                return response()->json(['error' => 'No price data found for symbol'], 404);
            }

            $indicators = [];
            $today = date('Y-m-d');

            // Calculate and store each requested indicator
            if ($request->has('adx') || $request->get('all', false)) {
                $adxData = $this->technicalIndicators->calculateADX($priceData, $period);
                $indicators['adx'] = $adxData;
                
                // Store in per-symbol table with additional fields
                $this->indicatorRepository->storeIndicatorValue(
                    $symbol,
                    $today,
                    'adx',
                    $adxData['value'],
                    [
                        'plus_di' => $adxData['plus_di'],
                        'minus_di' => $adxData['minus_di'],
                        'trend_direction' => $adxData['trend_direction']
                    ]
                );
            }

            if ($request->has('bollinger') || $request->get('all', false)) {
                $bbPeriod = $request->get('bb_period', 20);
                $bbStdDev = $request->get('bb_std_dev', 2);
                $bollingerData = $this->technicalIndicators->calculateBollingerBands($priceData, $bbPeriod, $bbStdDev);
                $indicators['bollinger_bands'] = $bollingerData;
                
                // Store with band data
                $this->indicatorRepository->storeIndicatorValue(
                    $symbol,
                    $today,
                    'bollinger_bands',
                    $bollingerData['middle_band'],
                    [
                        'upper_band' => $bollingerData['upper_band'],
                        'lower_band' => $bollingerData['lower_band']
                    ]
                );
            }

            if ($request->has('stochastic') || $request->get('all', false)) {
                $kPeriod = $request->get('k_period', 14);
                $dPeriod = $request->get('d_period', 3);
                $stochasticData = $this->technicalIndicators->calculateStochastic($priceData, $kPeriod, $dPeriod);
                $indicators['stochastic'] = $stochasticData;
                
                // Store stochastic components
                $this->indicatorRepository->storeIndicatorValue(
                    $symbol,
                    $today,
                    'stochastic',
                    $stochasticData['k_percent'],
                    [
                        'k_percent' => $stochasticData['k_percent'],
                        'd_percent' => $stochasticData['d_percent']
                    ]
                );
            }

            if ($request->has('shannon') || $request->get('all', false)) {
                $window = $request->get('shannon_window', 20);
                $shannonData = $this->technicalIndicators->calculateShannonProbability($priceData, $window);
                $indicators['shannon_probability'] = $shannonData;
                
                // Store in Shannon-specific table
                $this->indicatorRepository->storeShannonAnalysis(
                    $symbol,
                    $today,
                    $shannonData['shannon_probability'],
                    $shannonData['effective_probability'],
                    [
                        'entropy' => $shannonData['entropy'],
                        'accuracy_estimate' => $shannonData['accuracy_estimate'],
                        'window_size' => $window
                    ]
                );
            }

            return response()->json([
                'symbol' => $symbol,
                'calculation_date' => $today,
                'source' => 'calculated',
                'data_points' => count($priceData),
                'indicators' => $indicators
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Calculate Portfolio Risk Metrics
     * Now uses dedicated calculator classes following SRP
     */
    public function getPortfolioRiskMetrics(Request $request, int $portfolioId): JsonResponse
    {
        try {
            $portfolio = Portfolio::findOrFail($portfolioId);
            $period = $request->get('period', 252);
            $confidence95 = $request->get('confidence_95', 0.95);
            $confidence99 = $request->get('confidence_99', 0.99);
            $riskFreeRate = $request->get('risk_free_rate', 0.02);
            
            $holdings = $portfolio->holdings()->with('quote')->get();
            
            if ($holdings->isEmpty()) {
                return response()->json(['error' => 'Portfolio has no holdings'], 400);
            }

            // Use dedicated calculators following SRP
            $portfolioReturns = $this->returnsCalculator->calculateReturns($holdings, $period);
            
            if (empty($portfolioReturns)) {
                return response()->json(['error' => 'Insufficient data for risk calculation'], 400);
            }

            // Calculate risk metrics using specialized services
            $var95 = $this->riskManager->calculateVaR($portfolioReturns, $confidence95);
            $var99 = $this->riskManager->calculateVaR($portfolioReturns, $confidence99);
            $sharpeRatio = $this->riskManager->calculateSharpeRatio($portfolioReturns, $riskFreeRate);
            $maxDrawdown = $this->riskManager->calculateMaxDrawdown($portfolioReturns);
            
            // Use variance calculator
            $volatility = $this->varianceCalculator->calculateStandardDeviation($portfolioReturns);
            $skewness = $this->varianceCalculator->calculateSkewness($portfolioReturns);
            $kurtosis = $this->varianceCalculator->calculateKurtosis($portfolioReturns);
            
            // Use weights calculator for concentration analysis
            $concentrationRisk = $this->weightsCalculator->calculateConcentrationRisk($holdings);
            $portfolioWeights = $this->weightsCalculator->calculateCurrentWeights($holdings);

            // Store risk metrics in per-symbol tables
            $today = date('Y-m-d');
            foreach ($holdings as $holding) {
                $this->indicatorRepository->storeRiskMetrics(
                    $holding->symbol,
                    $today,
                    'portfolio_var_95',
                    $var95,
                    [
                        'var_99' => $var99,
                        'sharpe_ratio' => $sharpeRatio,
                        'max_drawdown' => $maxDrawdown,
                        'volatility' => $volatility,
                        'skewness' => $skewness,
                        'kurtosis' => $kurtosis
                    ]
                );
            }

            return response()->json([
                'portfolio_id' => $portfolioId,
                'analysis_period' => $period,
                'calculation_date' => $today,
                'var_analysis' => [
                    'var_95' => $var95,
                    'var_99' => $var99,
                    'expected_shortfall' => $this->riskManager->calculateExpectedShortfall($portfolioReturns, $confidence95)
                ],
                'performance_metrics' => [
                    'sharpe_ratio' => $sharpeRatio,
                    'max_drawdown' => $maxDrawdown,
                    'volatility' => $volatility,
                    'skewness' => $skewness,
                    'kurtosis' => $kurtosis
                ],
                'concentration_analysis' => $concentrationRisk,
                'portfolio_weights' => $portfolioWeights
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get Correlation Matrix
     * Now uses dedicated variance calculator for correlation calculations
     */
    public function getCorrelationMatrix(Request $request): JsonResponse
    {
        try {
            $symbols = $request->get('symbols', []);
            $period = $request->get('period', 252);
            $correlationType = $request->get('correlation_type', 'pearson');

            if (count($symbols) < 2) {
                return response()->json(['error' => 'At least 2 symbols required'], 400);
            }

            $assetReturns = [];
            $today = date('Y-m-d');

            // Get returns for each symbol using repository
            foreach ($symbols as $symbol) {
                $priceData = $this->indicatorRepository->getHistoricalPrices($symbol, $period);
                
                if (count($priceData) >= $period) {
                    $returns = [];
                    for ($i = 1; $i < count($priceData); $i++) {
                        $returns[] = ($priceData[$i]->close - $priceData[$i-1]->close) / $priceData[$i-1]->close;
                    }
                    $assetReturns[$symbol] = $returns;
                }
            }

            if (count($assetReturns) < 2) {
                return response()->json(['error' => 'Insufficient data for correlation analysis'], 400);
            }

            // Calculate correlation matrix using dedicated calculator
            $correlationMatrix = [];
            $symbols = array_keys($assetReturns);
            
            for ($i = 0; $i < count($symbols); $i++) {
                for ($j = 0; $j < count($symbols); $j++) {
                    if ($i === $j) {
                        $correlationMatrix[$symbols[$i]][$symbols[$j]] = 1.0;
                    } else {
                        $correlation = $this->varianceCalculator->calculateCorrelation(
                            $assetReturns[$symbols[$i]],
                            $assetReturns[$symbols[$j]]
                        );
                        $correlationMatrix[$symbols[$i]][$symbols[$j]] = $correlation;
                        
                        // Store correlation data in per-symbol tables
                        $this->indicatorRepository->storeCorrelationData(
                            $symbols[$i],
                            $symbols[$j],
                            $today,
                            $correlation,
                            [
                                'correlation_type' => $correlationType,
                                'sample_period' => $period,
                                'covariance' => $this->varianceCalculator->calculateCovariance(
                                    $assetReturns[$symbols[$i]],
                                    $assetReturns[$symbols[$j]]
                                )
                            ]
                        );
                    }
                }
            }

            return response()->json([
                'symbols' => $symbols,
                'period' => $period,
                'correlation_type' => $correlationType,
                'calculation_date' => $today,
                'correlation_matrix' => $correlationMatrix
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Run Strategy Backtest
     * Delegates to backtest engine and stores results in per-symbol table
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

            // Get historical data using repository
            $priceData = $this->indicatorRepository->getHistoricalPrices($symbol, 1000);

            if (empty($priceData)) {
                return response()->json(['error' => 'No data found for the specified period'], 404);
            }

            // Run backtest using dedicated engine
            $results = $this->backtestEngine->runBacktest($strategy, $priceData, $parameters, $initialCapital);

            // Store backtest results in per-symbol table
            $this->indicatorRepository->storeBacktestResults($symbol, $strategy, [
                'start_date' => $startDate ?: $priceData[0]->date,
                'end_date' => $endDate ?: end($priceData)->date,
                'initial_capital' => $initialCapital,
                'final_capital' => $results['final_value'],
                'total_return' => $results['total_return'],
                'max_drawdown' => $results['max_drawdown'],
                'sharpe_ratio' => $results['sharpe_ratio'],
                'total_trades' => $results['total_trades'],
                'win_rate' => $results['win_rate'],
                'parameters' => json_encode($parameters)
            ]);

            return response()->json([
                'symbol' => $symbol,
                'strategy' => $strategy,
                'parameters' => $parameters,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper method to get cached indicators from per-symbol tables
     */
    private function getCachedIndicators(string $symbol, Request $request): array
    {
        $cached = [];
        $today = date('Y-m-d');
        
        if ($request->has('adx') || $request->get('all', false)) {
            $adxData = $this->indicatorRepository->getLatestIndicatorValue($symbol, 'adx');
            if ($adxData && $adxData->date === $today) {
                $cached['adx'] = [
                    'value' => $adxData->value,
                    'plus_di' => $adxData->plus_di,
                    'minus_di' => $adxData->minus_di,
                    'trend_direction' => $adxData->trend_direction
                ];
            }
        }

        if ($request->has('bollinger') || $request->get('all', false)) {
            $bbData = $this->indicatorRepository->getLatestIndicatorValue($symbol, 'bollinger_bands');
            if ($bbData && $bbData->date === $today) {
                $cached['bollinger_bands'] = [
                    'middle_band' => $bbData->value,
                    'upper_band' => $bbData->upper_band,
                    'lower_band' => $bbData->lower_band
                ];
            }
        }

        return $cached;
    }
}
