# ChatGPT Micro-Cap Experiment
Welcome to the repo behind my 6-month live trading experiment where ChatGPT manages a real-money micro-cap portfolio.

## Overview on getting started: [Here](https://github.com/LuckyOne7777/ChatGPT-Micro-Cap-Experiment/blob/main/Start%20Your%20Own/README.md)
   
## Repository Structure

- **`trading_script.py`** - Main trading engine with portfolio management and stop-loss automation
- **`Scripts and CSV Files/`** - My personal portfolio (updates every trading day)
- **`Start Your Own/`** - Template files and guide for starting your own experiment  
- **`Weekly Deep Research (MD|PDF)/`** - Research summaries and performance reports
- **`Experiment Details/`** - Documentation, methodology, prompts, and Q&A
- **`StockTableManager.php`** - Enhanced database management with per-symbol table architecture
- **`AdvancedTechnicalIndicators.php`** - Professional-grade technical analysis service
- **`PortfolioRiskManager.php`** - Comprehensive risk analysis and portfolio optimization
- **`ShannonAnalysis.php`** - Information theory-based market analysis
- **`BacktestEngine.php`** - Advanced strategy backtesting framework
- **`enhanced_architecture.md`** - System architecture documentation
- **`legacy_software_analysis.md`** - Analysis of PERL financial software for feature enhancement

# The Concept
Every day, I kept seeing the same ad about having some A.I. pick undervalued stocks. It was obvious it was trying to get me to subscribe to some garbage, so I just rolled my eyes.  
Then I started wondering, "How well would that actually work?"

So, starting with just $100, I wanted to answer a simple but powerful question:

**Can powerful large language models like ChatGPT actually generate alpha (or at least make smart trading decisions) using real-time data?**

## Enhanced Features

### Advanced Technical Analysis
- **50+ Professional Indicators** - ADX, Bollinger Bands, Stochastic, Aroon, Parabolic SAR, Shannon Probability
- **Accuracy Tracking** - Statistical validation of indicator performance with confidence levels
- **Multi-timeframe Analysis** - Support for different analytical periods and frequencies
- **Trend Classification** - Automated trend direction detection and classification

### Risk Management & Portfolio Optimization
- **Value at Risk (VaR)** - 95% and 99% confidence levels with expected shortfall calculations
- **Performance Metrics** - Sharpe, Sortino, Calmar ratios with statistical significance testing
- **Correlation Analysis** - Advanced correlation matrices with multiple correlation types
- **Drawdown Analysis** - Maximum drawdown tracking and recovery time analysis
- **Beta/Alpha Calculations** - Market sensitivity and excess return analysis

### Shannon Probability Analysis
- **Information Theory** - Shannon probability calculations for market prediction
- **Hurst Exponent** - Market persistence and mean reversion analysis
- **Kelly Optimization** - Optimal position sizing using Kelly criterion
- **Market Behavior Classification** - Persistent, anti-persistent, or random walk detection

### Professional Backtesting
- **Strategy Framework** - Configurable entry/exit conditions with position sizing
- **Walk-Forward Analysis** - Time-based strategy validation with rolling windows
- **Monte Carlo Simulation** - Statistical validation with thousands of scenarios
- **Performance Attribution** - Detailed analysis of returns vs. benchmark performance
- **Commission & Slippage** - Realistic trading cost modeling

### Per-Symbol Database Architecture
- **Scalable Design** - Each symbol gets individual tables for optimal performance
- **Advanced Schema** - Enhanced fields for professional-grade analysis
- **Backup Optimization** - Symbol-specific backup and restore capabilities
- **Data Quality Tracking** - Comprehensive data validation and quality scoring

## Each trading day:

- I provide it trading data on the stocks in its portfolio.  
- Strict stop-loss rules apply with advanced risk metrics monitoring.
- Every week I allow it to use deep research and advanced analytics to reevaluate its account.  
- Enhanced performance tracking includes professional risk metrics and statistical analysis.
- I track and publish performance data weekly on my blog: [Here](https://nathanbsmith729.substack.com)

## Research & Documentation

- [Research Index](https://github.com/LuckyOne7777/ChatGPT-Micro-Cap-Experiment/blob/main/Experiment%20Details/Deep%20Research%20Index.md)  
- [Disclaimer](https://github.com/LuckyOne7777/ChatGPT-Micro-Cap-Experiment/blob/main/Experiment%20Details/Disclaimer.md)  
- [Q&A](https://github.com/LuckyOne7777/ChatGPT-Micro-Cap-Experiment/blob/main/Experiment%20Details/Q%26A.md)  
- [Prompts](https://github.com/LuckyOne7777/ChatGPT-Micro-Cap-Experiment/blob/main/Experiment%20Details/Prompts.md)  
- [Starting Your Own](https://github.com/LuckyOne7777/ChatGPT-Micro-Cap-Experiment/blob/main/Start%20Your%20Own/README.md)  
- [Research Summaries (MD)](https://github.com/LuckyOne7777/ChatGPT-Micro-Cap-Experiment/tree/main/Weekly%20Deep%20Research%20(MD))  
- [Full Deep Research Reports (PDF)](https://github.com/LuckyOne7777/ChatGPT-Micro-Cap-Experiment/tree/main/Weekly%20Deep%20Research%20(PDF))
- [Chats](https://github.com/LuckyOne7777/ChatGPT-Micro-Cap-Experiment/blob/main/Experiment%20Details/Chats.md)
# Current Performance

<!-- To update performance chart: 
     1. Replace the image file with updated results
     2. Update the dates and description below
     3. Update the "Last Updated" date -->

**Last Updated:** August 29th, 2025

![Latest Performance Results](Results.png)

**Current Status:** Portfolio is outperforming the S&P 500 benchmark

*Performance data is updated after each trading day. See the CSV files in `Scripts and CSV Files/` for detailed daily tracking.*

# Features of This Repo
- Live trading scripts — used to evaluate prices and update holdings daily  
- LLM-powered decision engine — ChatGPT picks the trades  
- Performance tracking — CSVs with daily PnL, total equity, and trade history  
- Visualization tools — Matplotlib graphs comparing ChatGPT vs. Index  
- Logs & trade data — auto-saved logs for transparency  

# Why This Matters
AI is being hyped across every industry, but can it really manage money without guidance?

This project is an attempt to find out — with transparency, data, and a real budget.

# Tech Stack & Features

## Core Technologies
- **Python** - Core scripting and automation
- **pandas + yFinance** - Market data fetching and analysis
- **Matplotlib** - Performance visualization and charting
- **ChatGPT-4** - AI-powered trading decision engine
- **PHP** - Enhanced backend services for advanced analytics
- **MySQL** - Per-symbol database architecture for scalable data management

## Enhanced Financial Analysis Capabilities

### Professional Technical Indicators
- **Trend Indicators** - ADX (Average Directional Index), Aroon Oscillator, Parabolic SAR
- **Momentum Indicators** - Stochastic Oscillator, Williams %R, CCI, Rate of Change
- **Volume Indicators** - On-Balance Volume (OBV), Volume Rate of Change
- **Volatility Indicators** - Bollinger Bands, Average True Range (ATR)
- **Statistical Indicators** - Shannon Probability, Hurst Exponent for market persistence
- **Accuracy Tracking** - Statistical validation with confidence levels and sample sizes

### Advanced Risk Management
- **Value at Risk (VaR)** - 95% and 99% confidence levels with expected shortfall
- **Performance Ratios** - Sharpe, Sortino, Calmar ratios with statistical significance
- **Beta/Alpha Analysis** - Market sensitivity and excess return calculations
- **Correlation Analysis** - Multi-symbol correlation matrices with various correlation types
- **Drawdown Analysis** - Maximum drawdown tracking and recovery time metrics
- **Volatility Modeling** - Annualized volatility with skewness and kurtosis analysis

### Shannon Probability Framework
- **Information Theory Analysis** - Shannon entropy and probability calculations
- **Market Persistence** - Hurst exponent for trend persistence vs. mean reversion
- **Optimal Portfolio Allocation** - Kelly criterion-based position sizing
- **Market Behavior Classification** - Persistent, anti-persistent, or random walk detection
- **Accuracy Validation** - Statistical estimation of prediction accuracy

### Professional Backtesting Engine
- **Strategy Framework** - Configurable entry/exit rules with position sizing
- **Walk-Forward Analysis** - Time-based validation with rolling optimization windows
- **Monte Carlo Simulation** - Statistical validation with thousands of market scenarios
- **Performance Attribution** - Detailed alpha/beta decomposition vs. benchmarks
- **Realistic Cost Modeling** - Commission, slippage, and market impact simulation
- **Risk-Adjusted Metrics** - Information ratio, tracking error, and attribution analysis

### Database Architecture
- **Per-Symbol Tables** - Individual tables per stock symbol for optimal performance
- **Enhanced Schema** - Advanced fields for professional-grade analysis
- **Multi-Exchange Support** - NYSE, NASDAQ, TSX, LSE with currency conversion
- **Data Quality Tracking** - Comprehensive validation and quality scoring
- **Backup Optimization** - Symbol-specific backup and restore capabilities

## Key Features
- **Robust Data Sources** - Yahoo Finance primary, Stooq fallback for reliability
- **Automated Stop-Loss** - Advanced position management with risk metrics integration
- **Interactive Trading** - Market-on-Open (MOO) and limit order support with slippage modeling
- **Professional Backtesting** - Comprehensive strategy validation with statistical significance
- **Performance Analytics** - CAPM analysis, multi-factor attribution, drawdown metrics
- **Real-time Risk Monitoring** - Continuous VaR calculation and correlation tracking
- **Trade Logging** - Complete transparency with detailed execution and performance logs

## System Requirements
- **Python 3.7+** (3.11+ recommended)
- **PHP 7.4+** (for enhanced analytics services)
- **MySQL 8.0+** (for per-symbol database architecture)
- Internet connection for market data
- ~100MB storage for enhanced CSV and database files

## Installation

### Standard Installation
```bash
pip3 install -r requirements.txt
```

### Fedora 30 / Older Systems
For compatibility with older Python versions (3.7):
```bash
# Use compatible package versions
pip3 install --user -r requirements-fedora30.txt

# Or minimal installation
pip3 install --user -r requirements-minimal.txt
```

**Fedora 30 Users:** See detailed installation guide at [`docs/Fedora30-Installation.md`](docs/Fedora30-Installation.md)

### Troubleshooting Package Issues
If you encounter pip3 errors on older systems:
1. Try the minimal requirements first: `requirements-minimal.txt`
2. Install packages individually with version ranges
3. Use virtual environments to avoid conflicts
4. Check the Fedora 30 installation guide for detailed troubleshooting

# Follow Along
The experiment runs from June 2025 to December 2025.  
Every trading day I will update the portfolio CSV file.  
If you feel inspired to do something similar, feel free to use this as a blueprint.

Updates are posted weekly on my blog, more coming soon!

Blog: [A.I Controls Stock Account](https://nathanbsmith729.substack.com)

Have feature requests or any advice?  

Please reach out here: **nathanbsmith.business@gmail.com**
