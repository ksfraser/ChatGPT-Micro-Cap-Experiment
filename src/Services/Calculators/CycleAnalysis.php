<?php

namespace App\Services\Calculators;

use LupeCode\phpTraderNative\Trader;

/**
 * Hilbert Transform Calculator for Market Cycle Analysis
 * Implements advanced cycle detection and trend/cycle mode analysis
 */
class HilbertTransformCalculator extends TALibCalculatorBase
{
    /**
     * Calculate comprehensive Hilbert Transform analysis
     */
    public function calculateAll(array $priceData): array
    {
        $this->validatePriceData($priceData, ['close']);
        
        if (count($priceData) < 100) {
            return $this->formatError('insufficient_data', "Need at least 100 data points for Hilbert Transform analysis");
        }

        $closes = $this->extractArrays($priceData, ['close'])['close'];
        
        try {
            // Calculate all Hilbert Transform indicators
            $dcPeriod = Trader::ht_dcperiod($closes);
            $dcPhase = Trader::ht_dcphase($closes);
            $trendMode = Trader::ht_trendmode($closes);
            $phasorInPhase = Trader::ht_phasor($closes)[0]; // In-phase component
            $phasorQuadrature = Trader::ht_phasor($closes)[1]; // Quadrature component
            $sine = Trader::ht_sine($closes)[0]; // Sine wave
            $leadSine = Trader::ht_sine($closes)[1]; // Lead sine wave
            
            // Combine with dates
            $dataKeys = array_keys($priceData);
            $resultData = [];
            $cycleSignals = [];
            $trendSignals = [];
            
            foreach ($dcPeriod as $index => $period) {
                if ($period !== null && isset($dataKeys[$index])) {
                    $date = $dataKeys[$index];
                    $price = $priceData[$date]['close'];
                    
                    $phase = $dcPhase[$index];
                    $trend = $trendMode[$index];
                    $inPhase = $phasorInPhase[$index];
                    $quadrature = $phasorQuadrature[$index];
                    $sineVal = $sine[$index];
                    $leadSineVal = $leadSine[$index];
                    
                    $marketMode = $this->determineMarketMode($trend);
                    $cyclePosition = $this->determineCyclePosition($phase);
                    $signal = $this->generateHTSignal($sineVal, $leadSineVal, $trend);
                    
                    $resultData[$date] = [
                        'price' => $price,
                        'dominant_cycle_period' => round($period, 2),
                        'dominant_cycle_phase' => round($phase, 4),
                        'trend_mode' => round($trend, 4),
                        'market_mode' => $marketMode,
                        'cycle_position' => $cyclePosition,
                        'in_phase' => round($inPhase, 4),
                        'quadrature' => round($quadrature, 4),
                        'sine' => round($sineVal, 4),
                        'lead_sine' => round($leadSineVal, 4),
                        'signal' => $signal
                    ];
                    
                    // Track significant signals
                    if ($signal !== 'HOLD') {
                        if ($marketMode === 'CYCLE') {
                            $cycleSignals[] = [
                                'date' => $date,
                                'signal' => $signal,
                                'cycle_period' => round($period, 2),
                                'cycle_position' => $cyclePosition,
                                'strength' => $this->calculateCycleSignalStrength($sineVal, $leadSineVal)
                            ];
                        } else {
                            $trendSignals[] = [
                                'date' => $date,
                                'signal' => $signal,
                                'trend_strength' => round($trend, 4),
                                'market_mode' => $marketMode
                            ];
                        }
                    }
                }
            }

            return [
                'indicator' => 'HilbertTransform',
                'data' => $resultData,
                'cycle_signals' => $cycleSignals,
                'trend_signals' => $trendSignals,
                'summary' => $this->generateHTSummary($resultData, $cycleSignals, $trendSignals),
                'calculation_engine' => 'TA-Lib'
            ];
            
        } catch (\Exception $e) {
            return $this->formatError('calculation_error', $e->getMessage());
        }
    }

    /**
     * Determine market mode (trend vs cycle)
     */
    private function determineMarketMode(float $trendMode): string
    {
        if ($trendMode > 0.7) {
            return 'STRONG_TREND';
        } elseif ($trendMode > 0.3) {
            return 'TREND';
        } elseif ($trendMode > -0.3) {
            return 'NEUTRAL';
        } elseif ($trendMode > -0.7) {
            return 'CYCLE';
        } else {
            return 'STRONG_CYCLE';
        }
    }

    /**
     * Determine position within cycle
     */
    private function determineCyclePosition(float $phase): string
    {
        // Phase is in radians, convert to degrees for easier interpretation
        $phaseDegrees = ($phase * 180 / M_PI) % 360;
        
        if ($phaseDegrees < 45 || $phaseDegrees >= 315) {
            return 'CYCLE_BOTTOM';
        } elseif ($phaseDegrees < 135) {
            return 'CYCLE_RISING';
        } elseif ($phaseDegrees < 225) {
            return 'CYCLE_TOP';
        } else {
            return 'CYCLE_FALLING';
        }
    }

    /**
     * Generate Hilbert Transform signal
     */
    private function generateHTSignal(float $sine, float $leadSine, float $trendMode): string
    {
        // For trend mode, use simple sine wave crossover
        if ($trendMode > 0) {
            if ($sine > $leadSine && $sine > 0.1) {
                return 'BUY';
            } elseif ($sine < $leadSine && $sine < -0.1) {
                return 'SELL';
            }
        } else {
            // For cycle mode, use more sophisticated cycle timing
            if ($sine > $leadSine && $sine > 0.3) {
                return 'CYCLE_BUY';
            } elseif ($sine < $leadSine && $sine < -0.3) {
                return 'CYCLE_SELL';
            }
        }
        
        return 'HOLD';
    }

    /**
     * Calculate cycle signal strength
     */
    private function calculateCycleSignalStrength(float $sine, float $leadSine): int
    {
        $separation = abs($sine - $leadSine);
        $amplitude = max(abs($sine), abs($leadSine));
        
        if ($separation > 0.5 && $amplitude > 0.5) return 90;
        if ($separation > 0.3 && $amplitude > 0.3) return 70;
        if ($separation > 0.1 && $amplitude > 0.1) return 50;
        return 30;
    }

    /**
     * Generate Hilbert Transform summary
     */
    private function generateHTSummary(array $resultData, array $cycleSignals, array $trendSignals): array
    {
        if (empty($resultData)) {
            return ['status' => 'no_data'];
        }

        $latest = end($resultData);
        $recent = array_slice($resultData, -20, 20, true);
        
        // Analyze recent market mode distribution
        $modeDistribution = [];
        $avgCyclePeriod = 0;
        $cyclePeriodCount = 0;
        
        foreach ($recent as $data) {
            $mode = $data['market_mode'];
            $modeDistribution[$mode] = ($modeDistribution[$mode] ?? 0) + 1;
            
            if (in_array($mode, ['CYCLE', 'STRONG_CYCLE'])) {
                $avgCyclePeriod += $data['dominant_cycle_period'];
                $cyclePeriodCount++;
            }
        }
        
        $avgCyclePeriod = $cyclePeriodCount > 0 ? $avgCyclePeriod / $cyclePeriodCount : 0;
        $dominantMode = array_keys($modeDistribution, max($modeDistribution))[0];
        
        return [
            'current_cycle_period' => $latest['dominant_cycle_period'],
            'current_market_mode' => $latest['market_mode'],
            'current_cycle_position' => $latest['cycle_position'],
            'current_signal' => $latest['signal'],
            'dominant_mode_recent' => $dominantMode,
            'avg_cycle_period' => round($avgCyclePeriod, 1),
            'mode_distribution' => $modeDistribution,
            'total_cycle_signals' => count($cycleSignals),
            'total_trend_signals' => count($trendSignals),
            'recent_cycle_signals' => array_slice($cycleSignals, -5),
            'recent_trend_signals' => array_slice($trendSignals, -5),
            'analysis' => $this->generateHTAnalysis($latest, $dominantMode, $avgCyclePeriod)
        ];
    }

    /**
     * Generate market cycle analysis
     */
    private function generateHTAnalysis(array $latest, string $dominantMode, float $avgCyclePeriod): string
    {
        $analysis = [];
        
        // Market mode analysis
        switch ($dominantMode) {
            case 'STRONG_TREND':
                $analysis[] = "Market in strong trending mode - trend-following strategies favored";
                break;
            case 'TREND':
                $analysis[] = "Market showing trending behavior - breakout strategies effective";
                break;
            case 'CYCLE':
                $analysis[] = "Market in cyclical mode - mean reversion strategies favored";
                break;
            case 'STRONG_CYCLE':
                $analysis[] = "Market in strong cyclical mode - contrarian strategies highly effective";
                break;
            default:
                $analysis[] = "Market in neutral mode - mixed strategy approach recommended";
        }
        
        // Cycle period analysis
        if ($avgCyclePeriod > 0) {
            if ($avgCyclePeriod < 10) {
                $analysis[] = "Short cycle period detected - expect frequent reversals";
            } elseif ($avgCyclePeriod < 30) {
                $analysis[] = "Medium cycle period - standard swing trading timeframe";
            } else {
                $analysis[] = "Long cycle period - position trading timeframe";
            }
        }
        
        // Current position analysis
        switch ($latest['cycle_position']) {
            case 'CYCLE_BOTTOM':
                $analysis[] = "Currently at cycle bottom - potential buying opportunity";
                break;
            case 'CYCLE_TOP':
                $analysis[] = "Currently at cycle top - potential selling opportunity";
                break;
            case 'CYCLE_RISING':
                $analysis[] = "In rising phase of cycle - upward momentum";
                break;
            case 'CYCLE_FALLING':
                $analysis[] = "In falling phase of cycle - downward momentum";
                break;
        }
        
        return implode('. ', $analysis);
    }
}

/**
 * Statistical Indicators Calculator
 * Implements Beta, Correlation, Linear Regression, and other statistical measures
 */
class StatisticalIndicatorsCalculator extends TALibCalculatorBase
{
    /**
     * Calculate Beta relative to market (benchmark)
     */
    public function calculateBeta(array $stockData, array $marketData, int $period = 252): array
    {
        $this->validatePriceData($stockData, ['close']);
        $this->validatePriceData($marketData, ['close']);
        
        if (count($stockData) < $period || count($marketData) < $period) {
            return $this->formatError('insufficient_data', "Need at least {$period} data points for Beta calculation");
        }

        // Align dates
        $commonDates = array_intersect(array_keys($stockData), array_keys($marketData));
        sort($commonDates);
        
        if (count($commonDates) < $period) {
            return $this->formatError('insufficient_data', "Not enough common dates between stock and market data");
        }

        $stockPrices = [];
        $marketPrices = [];
        
        foreach ($commonDates as $date) {
            $stockPrices[] = $stockData[$date]['close'];
            $marketPrices[] = $marketData[$date]['close'];
        }
        
        try {
            $beta = Trader::beta($stockPrices, $marketPrices, $period);
            $correlation = Trader::correl($stockPrices, $marketPrices, $period);
            
            // Combine with dates
            $resultData = [];
            $riskAssessments = [];
            
            foreach ($beta as $index => $betaValue) {
                if ($betaValue !== null && isset($commonDates[$index])) {
                    $date = $commonDates[$index];
                    $corrValue = $correlation[$index];
                    
                    $riskProfile = $this->determineRiskProfile($betaValue, $corrValue);
                    
                    $resultData[$date] = [
                        'beta' => round($betaValue, 4),
                        'correlation' => round($corrValue, 4),
                        'risk_profile' => $riskProfile,
                        'stock_price' => $stockData[$date]['close'],
                        'market_price' => $marketData[$date]['close']
                    ];
                    
                    $riskAssessments[] = [
                        'date' => $date,
                        'beta' => round($betaValue, 4),
                        'correlation' => round($corrValue, 4),
                        'risk_profile' => $riskProfile
                    ];
                }
            }

            return [
                'indicator' => 'Beta',
                'period' => $period,
                'data' => $resultData,
                'risk_assessments' => $riskAssessments,
                'summary' => $this->generateBetaSummary($resultData),
                'calculation_engine' => 'TA-Lib'
            ];
            
        } catch (\Exception $e) {
            return $this->formatError('calculation_error', $e->getMessage());
        }
    }

    /**
     * Calculate Linear Regression indicators
     */
    public function calculateLinearRegression(array $priceData, int $period = 20): array
    {
        $this->validatePriceData($priceData, ['close']);
        
        if (count($priceData) < $period) {
            return $this->formatError('insufficient_data', "Need at least {$period} data points for Linear Regression");
        }

        $closes = $this->extractArrays($priceData, ['close'])['close'];
        
        try {
            $linearReg = Trader::linearreg($closes, $period);
            $linearRegAngle = Trader::linearreg_angle($closes, $period);
            $linearRegSlope = Trader::linearreg_slope($closes, $period);
            $tsf = Trader::tsf($closes, $period); // Time Series Forecast
            
            // Combine with dates
            $dataKeys = array_keys($priceData);
            $resultData = [];
            $trendSignals = [];
            
            foreach ($linearReg as $index => $regValue) {
                if ($regValue !== null && isset($dataKeys[$index])) {
                    $date = $dataKeys[$index];
                    $price = $priceData[$date]['close'];
                    $angle = $linearRegAngle[$index];
                    $slope = $linearRegSlope[$index];
                    $forecast = $tsf[$index];
                    
                    $trendStrength = $this->determineTrendStrength($angle);
                    $trendDirection = $this->determineTrendDirection($slope);
                    $signal = $this->generateTrendSignal($price, $regValue, $slope);
                    
                    $resultData[$date] = [
                        'price' => $price,
                        'linear_reg' => round($regValue, 4),
                        'angle' => round($angle, 4),
                        'slope' => round($slope, 6),
                        'forecast' => round($forecast, 4),
                        'trend_strength' => $trendStrength,
                        'trend_direction' => $trendDirection,
                        'signal' => $signal,
                        'deviation_pct' => round((($price - $regValue) / $regValue) * 100, 2)
                    ];
                    
                    if ($signal !== 'HOLD') {
                        $trendSignals[] = [
                            'date' => $date,
                            'signal' => $signal,
                            'trend_strength' => $trendStrength,
                            'trend_direction' => $trendDirection,
                            'deviation_pct' => round((($price - $regValue) / $regValue) * 100, 2)
                        ];
                    }
                }
            }

            return [
                'indicator' => 'LinearRegression',
                'period' => $period,
                'data' => $resultData,
                'trend_signals' => $trendSignals,
                'summary' => $this->generateLinRegSummary($resultData, $trendSignals),
                'calculation_engine' => 'TA-Lib'
            ];
            
        } catch (\Exception $e) {
            return $this->formatError('calculation_error', $e->getMessage());
        }
    }

    /**
     * Determine risk profile based on beta and correlation
     */
    private function determineRiskProfile(float $beta, float $correlation): array
    {
        $profile = [];
        
        // Beta interpretation
        if ($beta > 1.5) {
            $profile['volatility'] = 'HIGH';
            $profile['beta_desc'] = 'Very volatile, amplifies market moves significantly';
        } elseif ($beta > 1.2) {
            $profile['volatility'] = 'MODERATE_HIGH';
            $profile['beta_desc'] = 'More volatile than market';
        } elseif ($beta > 0.8) {
            $profile['volatility'] = 'MODERATE';
            $profile['beta_desc'] = 'Similar volatility to market';
        } else {
            $profile['volatility'] = 'LOW';
            $profile['beta_desc'] = 'Less volatile than market';
        }
        
        // Correlation interpretation
        if (abs($correlation) > 0.8) {
            $profile['correlation_strength'] = 'STRONG';
        } elseif (abs($correlation) > 0.5) {
            $profile['correlation_strength'] = 'MODERATE';
        } else {
            $profile['correlation_strength'] = 'WEAK';
        }
        
        $profile['correlation_direction'] = $correlation > 0 ? 'POSITIVE' : 'NEGATIVE';
        
        return $profile;
    }

    /**
     * Determine trend strength from regression angle
     */
    private function determineTrendStrength(float $angle): string
    {
        $absAngle = abs($angle);
        
        if ($absAngle > 45) return 'VERY_STRONG';
        if ($absAngle > 30) return 'STRONG';
        if ($absAngle > 15) return 'MODERATE';
        if ($absAngle > 5) return 'WEAK';
        return 'SIDEWAYS';
    }

    /**
     * Determine trend direction from slope
     */
    private function determineTrendDirection(float $slope): string
    {
        if ($slope > 0.01) return 'STRONG_UP';
        if ($slope > 0.001) return 'UP';
        if ($slope < -0.01) return 'STRONG_DOWN';
        if ($slope < -0.001) return 'DOWN';
        return 'FLAT';
    }

    /**
     * Generate trend signal based on price vs regression line
     */
    private function generateTrendSignal(float $price, float $regValue, float $slope): string
    {
        $deviation = (($price - $regValue) / $regValue) * 100;
        
        if ($slope > 0.001) { // Uptrend
            if ($deviation < -5) return 'BUY'; // Price below uptrend line
            if ($deviation > 5) return 'SELL'; // Price far above uptrend line
        } else if ($slope < -0.001) { // Downtrend
            if ($deviation > 5) return 'SELL'; // Price above downtrend line
            if ($deviation < -5) return 'BUY'; // Price far below downtrend line
        }
        
        return 'HOLD';
    }

    /**
     * Generate Beta summary
     */
    private function generateBetaSummary(array $resultData): array
    {
        if (empty($resultData)) {
            return ['status' => 'no_data'];
        }

        $latest = end($resultData);
        $recentData = array_slice($resultData, -60, 60, true); // Last 60 periods
        
        $avgBeta = 0;
        $avgCorrelation = 0;
        $count = 0;
        
        foreach ($recentData as $data) {
            $avgBeta += $data['beta'];
            $avgCorrelation += $data['correlation'];
            $count++;
        }
        
        $avgBeta /= $count;
        $avgCorrelation /= $count;
        
        return [
            'current_beta' => $latest['beta'],
            'current_correlation' => $latest['correlation'],
            'current_risk_profile' => $latest['risk_profile'],
            'avg_beta_60d' => round($avgBeta, 4),
            'avg_correlation_60d' => round($avgCorrelation, 4),
            'risk_assessment' => $this->assessOverallRisk($avgBeta, $avgCorrelation)
        ];
    }

    /**
     * Generate Linear Regression summary
     */
    private function generateLinRegSummary(array $resultData, array $trendSignals): array
    {
        if (empty($resultData)) {
            return ['status' => 'no_data'];
        }

        $latest = end($resultData);
        
        return [
            'current_trend_strength' => $latest['trend_strength'],
            'current_trend_direction' => $latest['trend_direction'],
            'current_signal' => $latest['signal'],
            'current_deviation_pct' => $latest['deviation_pct'],
            'forecast_price' => $latest['forecast'],
            'total_trend_signals' => count($trendSignals),
            'recent_signals' => array_slice($trendSignals, -5)
        ];
    }

    /**
     * Assess overall risk
     */
    private function assessOverallRisk(float $avgBeta, float $avgCorrelation): string
    {
        if ($avgBeta > 1.5 && abs($avgCorrelation) > 0.7) {
            return 'HIGH_RISK_HIGH_CORRELATION';
        } elseif ($avgBeta > 1.2) {
            return 'MODERATE_HIGH_RISK';
        } elseif ($avgBeta < 0.8 && abs($avgCorrelation) < 0.5) {
            return 'LOW_RISK_LOW_CORRELATION';
        } else {
            return 'MODERATE_RISK';
        }
    }
}
