# API Key Setup Instructions

This project requires API keys for several external services. You must acquire these keys and enter them in the web admin screen or in the YAML config file.

## Required/Optional Keys
- **OpenAI** (optional, for LLM features)
- **Alpha Vantage** (required for stock data)
- **Finnhub** (required for stock data)
- **FMP (Financial Modeling Prep)** (optional, for extra data)

## How to Acquire Keys

### OpenAI
1. Go to https://platform.openai.com/account/api-keys
2. Sign up or log in.
3. Click "Create new secret key" and copy the key.

### Alpha Vantage
1. Go to https://www.alphavantage.co/support/#api-key
2. Sign up for a free API key.
3. Copy the key from your email or dashboard.

### Finnhub
1. Go to https://finnhub.io/register
2. Register for a free account.
3. Copy your API key from the dashboard.

### FMP (Financial Modeling Prep)
1. Go to https://financialmodelingprep.com/developer/docs/pricing/
2. Sign up for a free or paid account.
3. Copy your API key from the dashboard.

## Entering Keys
- Use the web admin at `web_ui/admin_api_keys.php` to enter and save your keys (recommended).
- Or, edit `db_config_refactored.yml` directly under the `api_keys` section.

## Storage
- Keys are stored in both the YAML config and the database for redundancy.

---
For more help, see the admin screen or contact the project maintainer.
