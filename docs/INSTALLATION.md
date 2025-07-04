# 📖 Installation Guide

This guide provides step-by-step instructions for installing the SMF Sphinx AI Search Plugin.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installation Methods](#installation-methods)
- [Automated Installation](#automated-installation)
- [Manual Installation](#manual-installation)
- [Post-Installation Setup](#post-installation-setup)
- [Verification](#verification)
- [Troubleshooting](#troubleshooting)

## Prerequisites

### System Requirements
- **SMF**: 2.1.* or higher
- **PHP**: 7.4+ (8.0+ recommended)
- **Python**: 3.8+ (3.10+ recommended)
- **MySQL**: 5.7+ or MariaDB 10.3+
- **Redis**: 6.0+ (for caching)
- **Memory**: 4GB+ RAM recommended (8GB+ for large forums)
- **Storage**: 2GB+ free space for models and indexes

### Required Services
- **Sphinx Search Daemon**: 2.2.11+ (3.x recommended)
- **Redis Server**: For caching and rate limiting
- **Web Server**: Apache 2.4+ or Nginx 1.18+

## Installation Methods

### 🚀 Automated Installation (Recommended)

The automated installation scripts handle all dependencies, model downloads, and configuration.

#### Windows
```cmd
install.bat
```

#### Linux/macOS
```bash
chmod +x install.sh
./install.sh
```

The automated installation will:
- ✅ Check system requirements
- ✅ Create Python virtual environment
- ✅ Install all Python dependencies
- ✅ Download and convert AI models
- ✅ Set up NLTK and spaCy data
- ✅ Handle Hugging Face authentication
- ✅ Configure initial settings

### 📋 Manual Installation

If you prefer manual control or automated installation fails:

#### Step 1: Install SMF Plugin

1. **Download the Plugin**
   ```bash
   git clone https://github.com/mlukasze/SMF_SphinxAI_mod.git
   cd SMF_SphinxAI_mod
   ```

2. **Upload to SMF Directory**
   - Copy all plugin files to your SMF root directory
   - Ensure file permissions are correct (644 for files, 755 for directories)

3. **Install via SMF Admin Panel**
   - Go to SMF Admin Panel > Packages
   - Click "Browse Packages" > "Upload Package"
   - Upload the package or install from directory
   - Follow the installation wizard

#### Step 2: Setup Python Environment

1. **Create Virtual Environment**
   ```bash
   python -m venv venv
   
   # Windows
   venv\Scripts\activate
   
   # Linux/macOS
   source venv/bin/activate
   ```

2. **Install Dependencies**
   
   For runtime only:
   ```bash
   pip install -r SphinxAI/requirements-runtime.txt
   ```
   
   For development (includes model conversion tools):
   ```bash
   pip install -r SphinxAI/requirements.txt
   ```

3. **Setup NLTK Data**
   ```bash
   python -c "import nltk; nltk.download('punkt'); nltk.download('stopwords'); nltk.download('wordnet'); nltk.download('averaged_perceptron_tagger')"
   ```

4. **Setup spaCy Models**
   ```bash
   python -m spacy download en_core_web_sm
   ```

#### Step 3: Configure Services

1. **Setup Redis Server**
   
   **Ubuntu/Debian:**
   ```bash
   sudo apt update
   sudo apt install redis-server
   sudo systemctl enable redis-server
   sudo systemctl start redis-server
   ```
   
   **Windows:**
   - Download Redis from https://github.com/microsoftarchive/redis/releases
   - Install and start Redis service
   
   **macOS:**
   ```bash
   brew install redis
   brew services start redis
   ```

2. **Configure Sphinx Search**
   
   Copy the sample configuration:
   ```bash
   cp sphinx.conf.sample /etc/sphinxsearch/sphinx.conf
   ```
   
   Edit the configuration file to match your database settings:
   ```bash
   sudo nano /etc/sphinxsearch/sphinx.conf
   ```

3. **Start Sphinx Daemon**
   ```bash
   sudo systemctl enable sphinxsearch
   sudo systemctl start sphinxsearch
   ```

#### Step 4: Download and Setup AI Models

1. **Hugging Face Authentication (Optional)**
   
   Some models require authentication. Create a token at:
   https://huggingface.co/settings/tokens
   
   Then create `SphinxAI/config.ini`:
   ```ini
   [huggingface]
   token = your_token_here
   ```

2. **Download Models**
   ```bash
   cd SphinxAI
   python unified_model_converter.py --download-all
   ```

3. **Convert Models for OpenVINO**
   ```bash
   python unified_model_converter.py --convert-all
   ```

## Post-Installation Setup

### SMF Plugin Configuration

1. **Access Admin Panel**
   - Go to SMF Admin > Configuration > Modifications
   - Find "Sphinx AI Search" in the list

2. **Basic Configuration**
   - Set Python executable path
   - Configure model directories
   - Set up database connection for Sphinx

3. **Advanced Settings**
   - Configure caching settings (Redis)
   - Set rate limiting parameters
   - Adjust search result limits

### Database Setup

The plugin automatically creates the following tables:
- `{prefix}sphinx_ai_index` - Search index data
- `{prefix}sphinx_ai_searches` - Search analytics
- `{prefix}sphinx_ai_settings` - Plugin configuration

### File Permissions

Ensure proper permissions:
```bash
# Make directories writable
chmod 755 SphinxAI/models cache logs temp

# Make install scripts executable
chmod +x install.sh

# Ensure PHP files are readable
chmod 644 *.php php/**/*.php
```

## Verification

### Test Python Environment

```bash
cd SphinxAI
python -c "
import numpy as np
import sentence_transformers
import openvino_genai
import redis
print('All dependencies loaded successfully!')
"
```

### Test SMF Integration

1. **Check Plugin Status**
   - Go to SMF Admin > Packages
   - Verify "Sphinx AI Search" shows as installed

2. **Test Search Interface**
   - Go to your forum's search page
   - Look for AI search options
   - Try a sample search

3. **Check Logs**
   - Review SMF error logs for any issues
   - Check `logs/` directory for Python logs

### Test Model Loading

```bash
cd SphinxAI
python main.py test-models
```

## Troubleshooting

### Common Issues

#### Python Import Errors
```bash
# Reinstall dependencies
pip install --force-reinstall -r requirements.txt

# Check Python version
python --version  # Should be 3.8+
```

#### Model Download Failures
```bash
# Clear cache and retry
rm -rf ~/.cache/huggingface/
python unified_model_converter.py --download-all --force
```

#### Permission Issues
```bash
# Fix file permissions
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
```

#### Redis Connection Issues
```bash
# Test Redis connection
redis-cli ping  # Should return "PONG"

# Check Redis configuration
redis-cli config get "*"
```

#### Sphinx Search Issues
```bash
# Check Sphinx status
sudo systemctl status sphinxsearch

# Test Sphinx connection
mysql -h127.0.0.1 -P9306
```

### Getting Help

If you encounter issues:

1. **Check Logs**
   - SMF error logs
   - Apache/Nginx error logs
   - Python logs in `logs/` directory

2. **Verify Requirements**
   - Run system requirements check
   - Ensure all services are running

3. **Community Support**
   - GitHub Issues: Report bugs and get help
   - SMF Community: Ask in the modifications section

### Next Steps

After successful installation:
- 📖 [Configuration Guide](CONFIGURATION.md)
- 🤖 [Model Management](MODELS.md)
- 📚 [Usage Documentation](USAGE.md)

---

Need help? Check our [Troubleshooting Guide](TROUBLESHOOTING.md) or open an issue on GitHub.
