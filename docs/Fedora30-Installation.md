# Fedora 30 Installation Guide

## System Requirements
- Fedora 30 (Python 3.7.x)
- pip3 installed

## Installation Steps

### 1. Update System Packages
```bash
sudo dnf update
sudo dnf install python3-pip python3-dev
```

### 2. Install Python Dependencies
For Fedora 30 compatibility, use the specific requirements file:

```bash
# Option 1: Use Fedora 30 specific requirements
pip3 install --user -r requirements-fedora30.txt

# Option 2: Install packages individually with compatible versions
pip3 install --user numpy>=1.16.0,<1.20.0
pip3 install --user pandas>=1.0.0,<1.4.0
pip3 install --user yfinance>=0.1.50,<0.1.90
pip3 install --user matplotlib>=3.1.0,<3.5.0
```

### 3. Verify Installation
```bash
python3 -c "import numpy, pandas, yfinance, matplotlib; print('All packages imported successfully')"
```

## Troubleshooting

### Common Issues on Fedora 30:

#### 1. Package Version Conflicts
If you get version conflicts, try installing with `--force-reinstall`:
```bash
pip3 install --user --force-reinstall -r requirements-fedora30.txt
```

#### 2. Compilation Errors
Install development packages:
```bash
sudo dnf install gcc python3-devel
```

#### 3. Permission Issues
Always use `--user` flag to install in user directory:
```bash
pip3 install --user package_name
```

#### 4. SSL Certificate Errors
Update certificates:
```bash
sudo dnf update ca-certificates
```

### Alternative: Using Virtual Environment
```bash
# Create virtual environment
python3 -m venv trading_env
source trading_env/bin/activate

# Install packages in virtual environment
pip install -r requirements-fedora30.txt

# Deactivate when done
deactivate
```

## Version Compatibility Matrix

| Package | Fedora 30 (Python 3.7) | Recommended Version |
|---------|-------------------------|-------------------|
| numpy | 1.16.0 - 1.19.5 | 1.19.5 |
| pandas | 1.0.0 - 1.3.5 | 1.3.5 |
| yfinance | 0.1.50 - 0.1.87 | 0.1.87 |
| matplotlib | 3.1.0 - 3.4.3 | 3.4.3 |

## Running the Trading Scripts

After successful installation:

```bash
# Run the main trading script
python3 trading_script.py

# Run the automation script
python3 simple_automation.py
```

## Support

If you continue to have issues, please provide:
1. Your exact Python version: `python3 --version`
2. Pip version: `pip3 --version`
3. Full error message from pip3 install
4. Output of: `pip3 list | grep -E "(numpy|pandas|yfinance|matplotlib)"`
