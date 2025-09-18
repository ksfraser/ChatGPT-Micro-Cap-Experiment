<?php

namespace App\Services\Calculators;

use LupeCode\phpTraderNative\Trader;

/**
 * TA-Lib Base Calculator
 * Abstract base class for TA-Lib wrapper calculators
 * Provides common functionality for data validation, conversion, and result formatting
 */
abstract class TALibCalculatorBase
{
    /**
     * Validate input data has required fields
     */
    protected function validatePriceData(array $priceData, array $requiredFields = ['close']): void
    {
        if (empty($priceData)) {
            throw new \InvalidArgumentException('Price data cannot be empty');
        }

        foreach ($priceData as $index => $data) {
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new \InvalidArgumentException("Missing '{$field}' field at index {$index}");
                }
                if (!is_numeric($data[$field])) {
                    throw new \InvalidArgumentException("Invalid '{$field}' value at index {$index}");
                }
            }
        }
    }

    /**
     * Extract arrays from price data for TA-Lib functions
     */
    protected function extractArrays(array $priceData, array $fields): array
    {
        $result = [];
        
        foreach ($fields as $field) {
            $result[$field] = [];
            foreach ($priceData as $data) {
                $result[$field][] = (float)$data[$field];
            }
        }

        return $result;
    }

    /**
     * Format TA-Lib results with metadata and signals
     */
    protected function formatResults(
        array $taLibResult, 
        array $metadata, 
        array $originalData,
        string $indicatorName
    ): array {
        return [
            'indicator' => $indicatorName,
            'values' => $this->combineWithDates($taLibResult, $originalData, $metadata['lookback'] ?? 0),
            'current_value' => $this->getCurrentValue($taLibResult),
            'signal' => $this->generateSignal($taLibResult, $metadata),
            'metadata' => $metadata,
            'calculation_engine' => 'TA-Lib',
            'data_points' => count($originalData),
            'result_points' => count($taLibResult)
        ];
    }

    /**
     * Combine TA-Lib results with original dates
     */
    private function combineWithDates(array $results, array $originalData, int $lookback): array
    {
        $combined = [];
        $startIndex = $lookback;

        foreach ($results as $index => $value) {
            $dataIndex = $startIndex + $index;
            $combined[] = [
                'date' => $originalData[$dataIndex]['date'] ?? $dataIndex,
                'value' => $value
            ];
        }

        return $combined;
    }

    /**
     * Get current (latest) value from results
     */
    private function getCurrentValue(array $results): ?float
    {
        return !empty($results) ? end($results) : null;
    }

    /**
     * Generate signal based on indicator values and metadata
     * Override in specific calculators for custom signal logic
     */
    protected function generateSignal(array $results, array $metadata): string
    {
        if (empty($results)) {
            return 'neutral';
        }

        $currentValue = end($results);
        
        // Default signal logic - override in child classes
        if (isset($metadata['overbought_level']) && $currentValue > $metadata['overbought_level']) {
            return 'overbought';
        }
        
        if (isset($metadata['oversold_level']) && $currentValue < $metadata['oversold_level']) {
            return 'oversold';
        }

        return 'neutral';
    }

    /**
     * Calculate lookback period for TA-Lib function
     */
    protected function calculateLookback(string $function, array $parameters = []): int
    {
        // TA-Lib provides lookback calculation functions
        // This is a simplified version - could be enhanced with actual TA-Lib lookback functions
        $lookbacks = [
            'rsi' => $parameters['period'] ?? 14,
            'adx' => $parameters['period'] ?? 14,
            'bbands' => $parameters['period'] ?? 20,
            'stoch' => max($parameters['fastK_period'] ?? 5, $parameters['fastD_period'] ?? 3),
            'macd' => max($parameters['fastPeriod'] ?? 12, $parameters['slowPeriod'] ?? 26) + ($parameters['signalPeriod'] ?? 9),
        ];

        return $lookbacks[$function] ?? 14;
    }
}

/**
 * RSI Calculator using TA-Lib
 * Single Responsibility: Calculate Relative Strength Index using TA-Lib
 */
class RSICalculator extends TALibCalculatorBase
{
    /**
     * Calculate RSI using TA-Lib
     */
    public function calculate(array $priceData, int $period = 14): array
    {
        $this->validatePriceData($priceData, ['close']);

        if (count($priceData) < $period + 1) {
            return [
                'indicator' => 'RSI',
                'values' => [],
                'current_value' => null,
                'signal' => 'insufficient_data',
                'metadata' => ['period' => $period, 'min_data_points' => $period + 1],
                'calculation_engine' => 'TA-Lib'
            ];
        }

        // Extract close prices for TA-Lib
        $arrays = $this->extractArrays($priceData, ['close']);
        
        // Calculate RSI using TA-Lib
        $rsiResults = Trader::rsi($arrays['close'], $period);

        // Prepare metadata
        $metadata = [
            'period' => $period,
            'overbought_level' => 70,
            'oversold_level' => 30,
            'lookback' => $this->calculateLookback('rsi', ['period' => $period])
        ];

        // Format and return results
        return $this->formatResults($rsiResults, $metadata, $priceData, 'RSI');
    }

    /**
     * Generate RSI-specific signals
     */
    protected function generateSignal(array $results, array $metadata): string
    {
        if (empty($results)) {
            return 'neutral';
        }

        $currentRSI = end($results);
        $previousRSI = count($results) > 1 ? $results[count($results) - 2] : $currentRSI;

        // RSI-specific signal logic
        if ($currentRSI > 80) {
            return 'extremely_overbought';
        } elseif ($currentRSI > 70) {
            return 'overbought';
        } elseif ($currentRSI < 20) {
            return 'extremely_oversold';
        } elseif ($currentRSI < 30) {
            return 'oversold';
        } elseif ($currentRSI > 50 && $previousRSI <= 50) {
            return 'bullish_momentum';
        } elseif ($currentRSI < 50 && $previousRSI >= 50) {
            return 'bearish_momentum';
        }

        return 'neutral';
    }

    /**
     * Calculate RSI with divergence analysis
     */
    public function calculateWithDivergence(array $priceData, int $period = 14): array
    {
        $baseResult = $this->calculate($priceData, $period);
        
        if (empty($baseResult['values'])) {
            return $baseResult;
        }

        // Add divergence analysis
        $divergence = $this->detectDivergence($priceData, $baseResult['values']);
        $baseResult['divergence'] = $divergence;

        return $baseResult;
    }

    /**
     * Detect bullish/bearish divergence between price and RSI
     */
    private function detectDivergence(array $priceData, array $rsiValues): array
    {
        if (count($rsiValues) < 10) {
            return ['detected' => false, 'type' => null];
        }

        // Get recent price and RSI data
        $recentPrices = array_slice($priceData, -10);
        $recentRSI = array_slice($rsiValues, -10);

        $priceChange = end($recentPrices)['close'] - $recentPrices[0]['close'];
        $rsiChange = end($recentRSI)['value'] - $recentRSI[0]['value'];

        // Bullish divergence: price down, RSI up
        if ($priceChange < -0.02 && $rsiChange > 5) {
            return [
                'detected' => true, 
                'type' => 'bullish',
                'strength' => min(abs($rsiChange) / 10, 1.0),
                'confidence' => $this->calculateDivergenceConfidence($recentPrices, $recentRSI)
            ];
        }

        // Bearish divergence: price up, RSI down
        if ($priceChange > 0.02 && $rsiChange < -5) {
            return [
                'detected' => true, 
                'type' => 'bearish',
                'strength' => min(abs($rsiChange) / 10, 1.0),
                'confidence' => $this->calculateDivergenceConfidence($recentPrices, $recentRSI)
            ];
        }

        return ['detected' => false, 'type' => null];
    }

    /**
     * Calculate confidence level of divergence signal
     */
    private function calculateDivergenceConfidence(array $prices, array $rsiValues): float
    {
        // Simple confidence calculation based on trend consistency
        $priceDir = 0;
        $rsiDir = 0;

        for ($i = 1; $i < count($prices); $i++) {
            if ($prices[$i]['close'] > $prices[$i-1]['close']) $priceDir++;
            if ($rsiValues[$i]['value'] > $rsiValues[$i-1]['value']) $rsiDir++;
        }

        $totalPeriods = count($prices) - 1;
        $priceConsistency = abs($priceDir - $totalPeriods/2) / ($totalPeriods/2);
        $rsiConsistency = abs($rsiDir - $totalPeriods/2) / ($totalPeriods/2);

        return min(($priceConsistency + $rsiConsistency) / 2, 1.0);
    }

    /**
     * Get RSI interpretation and trading suggestions
     */
    public function getInterpretation(float $rsiValue): array
    {
        $interpretations = [
            'extremely_overbought' => [
                'condition' => $rsiValue > 80,
                'meaning' => 'Extremely overbought - strong sell signal',
                'action' => 'Consider selling or taking profits',
                'risk_level' => 'high'
            ],
            'overbought' => [
                'condition' => $rsiValue > 70,
                'meaning' => 'Overbought - potential reversal',
                'action' => 'Consider reducing position or wait for pullback',
                'risk_level' => 'medium-high'
            ],
            'neutral_bullish' => [
                'condition' => $rsiValue >= 50 && $rsiValue <= 70,
                'meaning' => 'Bullish momentum - uptrend likely to continue',
                'action' => 'Hold or consider buying on dips',
                'risk_level' => 'medium'
            ],
            'neutral_bearish' => [
                'condition' => $rsiValue >= 30 && $rsiValue < 50,
                'meaning' => 'Bearish momentum - downtrend likely to continue',
                'action' => 'Avoid buying or consider shorting',
                'risk_level' => 'medium'
            ],
            'oversold' => [
                'condition' => $rsiValue < 30,
                'meaning' => 'Oversold - potential bounce',
                'action' => 'Consider buying opportunity',
                'risk_level' => 'medium-high'
            ],
            'extremely_oversold' => [
                'condition' => $rsiValue < 20,
                'meaning' => 'Extremely oversold - strong buy signal',
                'action' => 'Strong buying opportunity',
                'risk_level' => 'high'
            ]
        ];

        foreach ($interpretations as $key => $interpretation) {
            if ($interpretation['condition']) {
                return [
                    'category' => $key,
                    'meaning' => $interpretation['meaning'],
                    'action' => $interpretation['action'],
                    'risk_level' => $interpretation['risk_level'],
                    'rsi_value' => $rsiValue
                ];
            }
        }

        return [
            'category' => 'neutral',
            'meaning' => 'Neutral - no clear directional bias',
            'action' => 'Wait for clearer signals',
            'risk_level' => 'low',
            'rsi_value' => $rsiValue
        ];
    }
}

/**
 * MACD Calculator using TA-Lib
 * Single Responsibility: Calculate MACD using TA-Lib
 */
class MACDCalculator extends TALibCalculatorBase
{
    /**
     * Calculate MACD using TA-Lib
     */
    public function calculate(
        array $priceData, 
        int $fastPeriod = 12, 
        int $slowPeriod = 26, 
        int $signalPeriod = 9
    ): array {
        $this->validatePriceData($priceData, ['close']);

        $minDataPoints = $slowPeriod + $signalPeriod;
        if (count($priceData) < $minDataPoints) {
            return [
                'indicator' => 'MACD',
                'values' => [],
                'current_values' => null,
                'signal' => 'insufficient_data',
                'metadata' => [
                    'fast_period' => $fastPeriod,
                    'slow_period' => $slowPeriod,
                    'signal_period' => $signalPeriod,
                    'min_data_points' => $minDataPoints
                ],
                'calculation_engine' => 'TA-Lib'
            ];
        }

        // Extract close prices for TA-Lib
        $arrays = $this->extractArrays($priceData, ['close']);
        
        // Calculate MACD using TA-Lib
        $macdResults = Trader::macd($arrays['close'], $fastPeriod, $slowPeriod, $signalPeriod);

        // Prepare metadata
        $metadata = [
            'fast_period' => $fastPeriod,
            'slow_period' => $slowPeriod,
            'signal_period' => $signalPeriod,
            'lookback' => $this->calculateLookback('macd', [
                'fastPeriod' => $fastPeriod,
                'slowPeriod' => $slowPeriod,
                'signalPeriod' => $signalPeriod
            ])
        ];

        // Format results with MACD-specific structure
        return $this->formatMACDResults($macdResults, $metadata, $priceData);
    }

    /**
     * Format MACD results (special handling for multiple outputs)
     */
    private function formatMACDResults(array $macdResults, array $metadata, array $originalData): array
    {
        $macd = $macdResults['MACD'] ?? [];
        $signal = $macdResults['MACDSignal'] ?? [];
        $histogram = $macdResults['MACDHist'] ?? [];

        $combined = [];
        $lookback = $metadata['lookback'];

        for ($i = 0; $i < count($macd); $i++) {
            $dataIndex = $lookback + $i;
            $combined[] = [
                'date' => $originalData[$dataIndex]['date'] ?? $dataIndex,
                'macd' => $macd[$i],
                'signal' => $signal[$i],
                'histogram' => $histogram[$i]
            ];
        }

        $currentValues = !empty($combined) ? end($combined) : null;

        return [
            'indicator' => 'MACD',
            'values' => $combined,
            'current_values' => $currentValues,
            'signal' => $this->generateMACDSignal($combined),
            'metadata' => $metadata,
            'calculation_engine' => 'TA-Lib',
            'data_points' => count($originalData),
            'result_points' => count($combined)
        ];
    }

    /**
     * Generate MACD-specific signals
     */
    private function generateMACDSignal(array $macdData): string
    {
        if (count($macdData) < 2) {
            return 'neutral';
        }

        $current = end($macdData);
        $previous = $macdData[count($macdData) - 2];

        // MACD line crosses above signal line (bullish)
        if ($current['macd'] > $current['signal'] && $previous['macd'] <= $previous['signal']) {
            return 'bullish_crossover';
        }

        // MACD line crosses below signal line (bearish)
        if ($current['macd'] < $current['signal'] && $previous['macd'] >= $previous['signal']) {
            return 'bearish_crossover';
        }

        // Histogram analysis
        if ($current['histogram'] > 0 && $previous['histogram'] <= 0) {
            return 'histogram_bullish';
        }

        if ($current['histogram'] < 0 && $previous['histogram'] >= 0) {
            return 'histogram_bearish';
        }

        // Trend continuation signals
        if ($current['macd'] > $current['signal'] && $current['histogram'] > $previous['histogram']) {
            return 'bullish_momentum';
        }

        if ($current['macd'] < $current['signal'] && $current['histogram'] < $previous['histogram']) {
            return 'bearish_momentum';
        }

        return 'neutral';
    }
}
