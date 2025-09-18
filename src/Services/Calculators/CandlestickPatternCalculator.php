<?php

namespace App\Services\Calculators;

use LupeCode\phpTraderNative\Trader;

/**
 * Candlestick Pattern Recognition Calculator using TA-Lib
 * Single Responsibility: Detect and analyze candlestick patterns using TA-Lib
 * 
 * This is entirely new functionality - we didn't have pattern recognition before
 */
class CandlestickPatternCalculator extends TALibCalculatorBase
{
    /**
     * All available TA-Lib candlestick patterns with their trading significance
     */
    private const PATTERNS = [
        // Most Important Patterns (High Trading Value)
        'doji' => ['name' => 'Doji', 'type' => 'reversal', 'significance' => 5],
        'hammer' => ['name' => 'Hammer', 'type' => 'bullish_reversal', 'significance' => 5],
        'shootingstar' => ['name' => 'Shooting Star', 'type' => 'bearish_reversal', 'significance' => 5],
        'engulfing' => ['name' => 'Engulfing Pattern', 'type' => 'reversal', 'significance' => 5],
        'morningstar' => ['name' => 'Morning Star', 'type' => 'bullish_reversal', 'significance' => 5],
        'eveningstar' => ['name' => 'Evening Star', 'type' => 'bearish_reversal', 'significance' => 5],
        'piercing' => ['name' => 'Piercing Pattern', 'type' => 'bullish_reversal', 'significance' => 4],
        'darkcloudcover' => ['name' => 'Dark Cloud Cover', 'type' => 'bearish_reversal', 'significance' => 4],
        'harami' => ['name' => 'Harami Pattern', 'type' => 'reversal', 'significance' => 4],
        'invertedhammer' => ['name' => 'Inverted Hammer', 'type' => 'bullish_reversal', 'significance' => 4],
        
        // Secondary Patterns (Medium Trading Value)
        'hangingman' => ['name' => 'Hanging Man', 'type' => 'bearish_reversal', 'significance' => 3],
        'dojistar' => ['name' => 'Doji Star', 'type' => 'reversal', 'significance' => 3],
        'morningdojistar' => ['name' => 'Morning Doji Star', 'type' => 'bullish_reversal', 'significance' => 4],
        'eveningdojistar' => ['name' => 'Evening Doji Star', 'type' => 'bearish_reversal', 'significance' => 4],
        'belthold' => ['name' => 'Belt Hold', 'type' => 'continuation', 'significance' => 3],
        'marubozu' => ['name' => 'Marubozu', 'type' => 'continuation', 'significance' => 3],
        'spinningtop' => ['name' => 'Spinning Top', 'type' => 'indecision', 'significance' => 2],
        
        // Complex Patterns
        '3blackcrows' => ['name' => 'Three Black Crows', 'type' => 'bearish_reversal', 'significance' => 4],
        '3whitesoldiers' => ['name' => 'Three White Soldiers', 'type' => 'bullish_reversal', 'significance' => 4],
        '3inside' => ['name' => 'Three Inside Up/Down', 'type' => 'reversal', 'significance' => 3],
        '3outside' => ['name' => 'Three Outside Up/Down', 'type' => 'reversal', 'significance' => 3],
    ];

    /**
     * Scan for all candlestick patterns
     */
    public function scanAllPatterns(array $priceData): array
    {
        $this->validatePriceData($priceData, ['open', 'high', 'low', 'close']);

        if (count($priceData) < 5) {
            return [
                'patterns_detected' => [],
                'pattern_count' => 0,
                'scan_summary' => 'insufficient_data',
                'data_points' => count($priceData)
            ];
        }

        $arrays = $this->extractArrays($priceData, ['open', 'high', 'low', 'close']);
        $detectedPatterns = [];

        // Scan for each pattern
        foreach (self::PATTERNS as $patternKey => $patternInfo) {
            $results = $this->detectPattern($patternKey, $arrays);
            if (!empty($results)) {
                $detectedPatterns[$patternKey] = [
                    'pattern_info' => $patternInfo,
                    'occurrences' => $this->combineWithDates($results, $priceData, $patternKey),
                    'recent_signals' => $this->getRecentSignals($results, $priceData, 10),
                    'pattern_strength' => $this->calculatePatternStrength($results)
                ];
            }
        }

        return [
            'patterns_detected' => $detectedPatterns,
            'pattern_count' => count($detectedPatterns),
            'scan_summary' => $this->generateScanSummary($detectedPatterns),
            'composite_signal' => $this->generateCompositeSignal($detectedPatterns),
            'data_points' => count($priceData),
            'calculation_engine' => 'TA-Lib'
        ];
    }

    /**
     * Detect specific pattern using TA-Lib
     */
    private function detectPattern(string $pattern, array $arrays): array
    {
        $open = $arrays['open'];
        $high = $arrays['high'];
        $low = $arrays['low'];
        $close = $arrays['close'];

        try {
            switch ($pattern) {
                case 'doji':
                    return Trader::cdldoji($open, $high, $low, $close);
                case 'hammer':
                    return Trader::cdlhammer($open, $high, $low, $close);
                case 'shootingstar':
                    return Trader::cdlshootingstar($open, $high, $low, $close);
                case 'engulfing':
                    return Trader::cdlengulfing($open, $high, $low, $close);
                case 'morningstar':
                    return Trader::cdlmorningstar($open, $high, $low, $close);
                case 'eveningstar':
                    return Trader::cdleveningstar($open, $high, $low, $close);
                case 'piercing':
                    return Trader::cdlpiercing($open, $high, $low, $close);
                case 'darkcloudcover':
                    return Trader::cdldarkcloudcover($open, $high, $low, $close);
                case 'harami':
                    return Trader::cdlharami($open, $high, $low, $close);
                case 'invertedhammer':
                    return Trader::cdlinvertedhammer($open, $high, $low, $close);
                case 'hangingman':
                    return Trader::cdlhangingman($open, $high, $low, $close);
                case 'dojistar':
                    return Trader::cdldojistar($open, $high, $low, $close);
                case 'morningdojistar':
                    return Trader::cdlmorningdojistar($open, $high, $low, $close);
                case 'eveningdojistar':
                    return Trader::cdleveningdojistar($open, $high, $low, $close);
                case 'belthold':
                    return Trader::cdlbelthold($open, $high, $low, $close);
                case 'marubozu':
                    return Trader::cdlmarubozu($open, $high, $low, $close);
                case 'spinningtop':
                    return Trader::cdlspinningtop($open, $high, $low, $close);
                case '3blackcrows':
                    return Trader::cdl3blackcrows($open, $high, $low, $close);
                case '3whitesoldiers':
                    return Trader::cdl3whitesoldiers($open, $high, $low, $close);
                case '3inside':
                    return Trader::cdl3inside($open, $high, $low, $close);
                case '3outside':
                    return Trader::cdl3outside($open, $high, $low, $close);
                default:
                    return [];
            }
        } catch (\Exception $e) {
            // Some patterns might not be available in this TA-Lib version
            return [];
        }
    }

    /**
     * Combine pattern results with dates and filter non-zero values
     */
    private function combineWithDates(array $results, array $priceData, string $pattern): array
    {
        $combined = [];
        $lookback = $this->getPatternLookback($pattern);

        foreach ($results as $index => $value) {
            if ($value != 0) { // TA-Lib returns 0 for no pattern, +100/-100 for pattern
                $dataIndex = $lookback + $index;
                $combined[] = [
                    'date' => $priceData[$dataIndex]['date'] ?? $dataIndex,
                    'pattern_strength' => $value,
                    'pattern_type' => $value > 0 ? 'bullish' : 'bearish',
                    'candle_data' => [
                        'open' => $priceData[$dataIndex]['open'],
                        'high' => $priceData[$dataIndex]['high'],
                        'low' => $priceData[$dataIndex]['low'],
                        'close' => $priceData[$dataIndex]['close']
                    ]
                ];
            }
        }

        return $combined;
    }

    /**
     * Get recent pattern signals
     */
    private function getRecentSignals(array $results, array $priceData, int $count = 5): array
    {
        $recent = [];
        $lookback = 2; // Most patterns have minimal lookback

        for ($i = count($results) - 1; $i >= 0 && count($recent) < $count; $i--) {
            if ($results[$i] != 0) {
                $dataIndex = $lookback + $i;
                $recent[] = [
                    'date' => $priceData[$dataIndex]['date'] ?? $dataIndex,
                    'strength' => $results[$i],
                    'type' => $results[$i] > 0 ? 'bullish' : 'bearish'
                ];
            }
        }

        return array_reverse($recent);
    }

    /**
     * Calculate pattern strength metrics
     */
    private function calculatePatternStrength(array $results): array
    {
        $nonZero = array_filter($results, function($value) { return $value != 0; });
        
        if (empty($nonZero)) {
            return ['frequency' => 0, 'average_strength' => 0, 'bullish_count' => 0, 'bearish_count' => 0];
        }

        $bullish = array_filter($nonZero, function($value) { return $value > 0; });
        $bearish = array_filter($nonZero, function($value) { return $value < 0; });

        return [
            'frequency' => count($nonZero),
            'frequency_rate' => round((count($nonZero) / count($results)) * 100, 2),
            'average_strength' => round(array_sum($nonZero) / count($nonZero), 2),
            'bullish_count' => count($bullish),
            'bearish_count' => count($bearish),
            'bullish_ratio' => count($nonZero) > 0 ? round(count($bullish) / count($nonZero), 2) : 0
        ];
    }

    /**
     * Generate scan summary
     */
    private function generateScanSummary(array $detectedPatterns): array
    {
        if (empty($detectedPatterns)) {
            return ['status' => 'no_patterns', 'message' => 'No significant patterns detected'];
        }

        $totalPatterns = count($detectedPatterns);
        $highSignificance = 0;
        $bullishPatterns = 0;
        $bearishPatterns = 0;

        foreach ($detectedPatterns as $pattern) {
            if ($pattern['pattern_info']['significance'] >= 4) {
                $highSignificance++;
            }

            $type = $pattern['pattern_info']['type'];
            if (strpos($type, 'bullish') !== false) {
                $bullishPatterns++;
            } elseif (strpos($type, 'bearish') !== false) {
                $bearishPatterns++;
            }
        }

        return [
            'status' => 'patterns_found',
            'total_patterns' => $totalPatterns,
            'high_significance_patterns' => $highSignificance,
            'bullish_patterns' => $bullishPatterns,
            'bearish_patterns' => $bearishPatterns,
            'dominant_bias' => $this->getDominantBias($bullishPatterns, $bearishPatterns)
        ];
    }

    /**
     * Generate composite signal from all detected patterns
     */
    private function generateCompositeSignal(array $detectedPatterns): array
    {
        if (empty($detectedPatterns)) {
            return ['signal' => 'neutral', 'confidence' => 0, 'reasoning' => 'No patterns detected'];
        }

        $bullishScore = 0;
        $bearishScore = 0;
        $totalSignificance = 0;
        $recentPatterns = [];

        foreach ($detectedPatterns as $patternKey => $pattern) {
            $significance = $pattern['pattern_info']['significance'];
            $type = $pattern['pattern_info']['type'];
            $recentSignals = $pattern['recent_signals'];

            // Score recent patterns more heavily
            if (!empty($recentSignals)) {
                $recentPattern = end($recentSignals);
                $recentPatterns[] = [
                    'pattern' => $patternKey,
                    'type' => $type,
                    'significance' => $significance,
                    'strength' => $recentPattern['strength']
                ];

                if (strpos($type, 'bullish') !== false || $recentPattern['type'] === 'bullish') {
                    $bullishScore += $significance * 2; // Recent patterns weighted more
                } elseif (strpos($type, 'bearish') !== false || $recentPattern['type'] === 'bearish') {
                    $bearishScore += $significance * 2;
                } else {
                    // Neutral/reversal patterns - consider recent direction
                    if ($recentPattern['type'] === 'bullish') {
                        $bullishScore += $significance;
                    } else {
                        $bearishScore += $significance;
                    }
                }
            }

            $totalSignificance += $significance;
        }

        $netScore = $bullishScore - $bearishScore;
        $confidence = min(abs($netScore) / max($totalSignificance, 1) * 100, 100);

        return [
            'signal' => $this->determineCompositeSignal($netScore),
            'confidence' => round($confidence, 1),
            'bullish_score' => $bullishScore,
            'bearish_score' => $bearishScore,
            'net_score' => $netScore,
            'recent_patterns' => $recentPatterns,
            'reasoning' => $this->generateReasoning($netScore, $recentPatterns)
        ];
    }

    /**
     * Determine composite signal from net score
     */
    private function determineCompositeSignal(float $netScore): string
    {
        if ($netScore > 8) {
            return 'strong_bullish';
        } elseif ($netScore > 4) {
            return 'bullish';
        } elseif ($netScore > 1) {
            return 'weak_bullish';
        } elseif ($netScore < -8) {
            return 'strong_bearish';
        } elseif ($netScore < -4) {
            return 'bearish';
        } elseif ($netScore < -1) {
            return 'weak_bearish';
        } else {
            return 'neutral';
        }
    }

    /**
     * Generate reasoning for composite signal
     */
    private function generateReasoning(float $netScore, array $recentPatterns): string
    {
        if (empty($recentPatterns)) {
            return 'No recent patterns to analyze';
        }

        $patternNames = array_map(function($p) { return $p['pattern']; }, $recentPatterns);
        $patternList = implode(', ', $patternNames);

        if ($netScore > 4) {
            return "Strong bullish bias from recent patterns: {$patternList}";
        } elseif ($netScore > 1) {
            return "Moderate bullish bias from patterns: {$patternList}";
        } elseif ($netScore < -4) {
            return "Strong bearish bias from recent patterns: {$patternList}";
        } elseif ($netScore < -1) {
            return "Moderate bearish bias from patterns: {$patternList}";
        } else {
            return "Mixed signals from patterns: {$patternList}";
        }
    }

    /**
     * Get dominant bias
     */
    private function getDominantBias(int $bullish, int $bearish): string
    {
        if ($bullish > $bearish) {
            return 'bullish';
        } elseif ($bearish > $bullish) {
            return 'bearish';
        } else {
            return 'neutral';
        }
    }

    /**
     * Get pattern lookback period
     */
    private function getPatternLookback(string $pattern): int
    {
        // Most single-candle patterns have minimal lookback
        $multiCandle = ['morningstar', 'eveningstar', '3blackcrows', '3whitesoldiers', '3inside', '3outside'];
        return in_array($pattern, $multiCandle) ? 2 : 1;
    }

    /**
     * Focus scan on most important patterns only
     */
    public function scanKeyPatterns(array $priceData): array
    {
        $keyPatterns = ['doji', 'hammer', 'shootingstar', 'engulfing', 'morningstar', 'eveningstar', 'piercing', 'darkcloudcover'];
        
        $this->validatePriceData($priceData, ['open', 'high', 'low', 'close']);
        $arrays = $this->extractArrays($priceData, ['open', 'high', 'low', 'close']);
        $detectedPatterns = [];

        foreach ($keyPatterns as $pattern) {
            if (isset(self::PATTERNS[$pattern])) {
                $results = $this->detectPattern($pattern, $arrays);
                if (!empty($results)) {
                    $detectedPatterns[$pattern] = [
                        'pattern_info' => self::PATTERNS[$pattern],
                        'occurrences' => $this->combineWithDates($results, $priceData, $pattern),
                        'recent_signals' => $this->getRecentSignals($results, $priceData, 5)
                    ];
                }
            }
        }

        return [
            'key_patterns_detected' => $detectedPatterns,
            'pattern_count' => count($detectedPatterns),
            'composite_signal' => $this->generateCompositeSignal($detectedPatterns),
            'trading_recommendation' => $this->generateTradingRecommendation($detectedPatterns),
            'calculation_engine' => 'TA-Lib'
        ];
    }

    /**
     * Generate trading recommendation based on patterns
     */
    private function generateTradingRecommendation(array $patterns): array
    {
        if (empty($patterns)) {
            return [
                'action' => 'hold',
                'confidence' => 'low',
                'reasoning' => 'No significant candlestick patterns detected'
            ];
        }

        $recentBullish = 0;
        $recentBearish = 0;
        $highSigPatterns = [];

        foreach ($patterns as $patternKey => $pattern) {
            $info = $pattern['pattern_info'];
            $recent = $pattern['recent_signals'];

            if ($info['significance'] >= 4) {
                $highSigPatterns[] = $patternKey;
            }

            if (!empty($recent)) {
                $latestSignal = end($recent);
                if ($latestSignal['type'] === 'bullish' || strpos($info['type'], 'bullish') !== false) {
                    $recentBullish += $info['significance'];
                } elseif ($latestSignal['type'] === 'bearish' || strpos($info['type'], 'bearish') !== false) {
                    $recentBearish += $info['significance'];
                }
            }
        }

        $bias = $recentBullish - $recentBearish;
        $confidence = min(abs($bias) / 10 * 100, 100);

        if ($bias > 6) {
            $action = 'buy';
            $confidenceLevel = $confidence > 70 ? 'high' : 'medium';
        } elseif ($bias < -6) {
            $action = 'sell';
            $confidenceLevel = $confidence > 70 ? 'high' : 'medium';
        } else {
            $action = 'hold';
            $confidenceLevel = 'low';
        }

        return [
            'action' => $action,
            'confidence' => $confidenceLevel,
            'confidence_score' => round($confidence, 1),
            'high_significance_patterns' => $highSigPatterns,
            'reasoning' => $this->generateRecommendationReasoning($action, $highSigPatterns)
        ];
    }

    /**
     * Generate reasoning for trading recommendation
     */
    private function generateRecommendationReasoning(string $action, array $highSigPatterns): string
    {
        if (empty($highSigPatterns)) {
            return "No high-significance patterns detected. {$action} recommendation based on overall pattern sentiment.";
        }

        $patternList = implode(', ', $highSigPatterns);
        
        switch ($action) {
            case 'buy':
                return "Bullish patterns detected: {$patternList}. Consider buying opportunities.";
            case 'sell':
                return "Bearish patterns detected: {$patternList}. Consider taking profits or reducing positions.";
            default:
                return "Mixed signals from patterns: {$patternList}. Maintain current position.";
        }
    }
}
