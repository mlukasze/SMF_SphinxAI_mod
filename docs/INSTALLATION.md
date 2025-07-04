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
- [Configuration](#configuration)
- [Usage Guide](#usage-guide)
- [Administration](#administration)

## Prerequisites

### System Requirements
- **SMF**: 2.1.* or higher
- **PHP**: 8.1+ (8.2+ recommended) - Uses modern PHP features including enums, union types, constructor property promotion, and readonly properties
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

### Common Installation Issues

#### Plugin Installation Fails
**Symptoms**: Error during SMF package installation
**Solutions**:
1. **Check PHP Version**: Ensure PHP 8.1+ is installed and active
2. **Verify Permissions**: Check file/directory permissions (644 for files, 755 for directories)
3. **Database Connection**: Test database connectivity and permissions
4. **SMF Version**: Confirm SMF 2.1+ is installed
5. **Error Logs**: Check SMF error logs in Admin → Maintenance → Forum Errors

#### Python Environment Issues
**Symptoms**: "Python not found" or import errors
**Solutions**:
```bash
# Check Python version
python --version  # Should be 3.8+

# Reinstall dependencies
pip install -r SphinxAI/requirements-runtime.txt --force-reinstall

# Test critical imports
python -c "import torch, transformers, sentence_transformers, openvino"

# Check virtual environment
which python  # Should point to your venv if activated
```

#### Sphinx Daemon Problems
**Symptoms**: Search results empty or daemon connection fails
**Solutions**:
```bash
# Check daemon status
sudo systemctl status sphinxsearch

# Test connection to Sphinx
mysql -h127.0.0.1 -P9306

# Check logs
sudo tail -f /var/log/sphinxsearch/searchd.log

# Restart daemon
sudo systemctl restart sphinxsearch
```

#### Model Download Issues
**Symptoms**: "Model not found" or download failures
**Solutions**:
1. **Check Internet Connection**: Ensure access to huggingface.co
2. **Manual Download**: Use `git lfs` to download models manually
3. **Disk Space**: Verify sufficient storage (2GB+ free)
4. **Permissions**: Check write permissions in SphinxAI/models/ directory

#### Performance Problems
**Symptoms**: Slow search responses or high memory usage
**Solutions**:
1. **Increase Memory**: Ensure 4GB+ RAM available
2. **Optimize Models**: Use OpenVINO conversion for better performance
3. **Enable Caching**: Configure Redis for result caching
4. **Database Tuning**: Optimize MySQL configuration

### Specific Error Messages

#### "Call to undefined method" errors
**Cause**: Using old PHP syntax or version
**Solution**: 
- Upgrade to PHP 8.1+
- Clear opcode cache: `sudo systemctl restart php8.1-fpm`
- Verify modern PHP features are available

#### "Parse error: syntax error" messages
**Cause**: PHP 8.1+ syntax in older PHP versions
**Solution**: 
- Ensure PHP 8.1+ is installed and active
- Check `php -v` and web server PHP version match
- Restart web server after PHP upgrade

#### "Class 'Enum' not found" errors
**Cause**: Missing PHP 8.1+ enum support
**Solution**: 
- Verify PHP version: `php -r "echo PHP_VERSION;"`
- Test enum support: `php -r "enum Test: string { case VALUE = 'test'; } echo 'OK';"`
- Restart web server and clear caches

#### "Model not found" or "OpenVINO runtime error"
**Cause**: Missing or corrupted model files
**Solution**:
```bash
# Check model directory
ls -la SphinxAI/models/

# Re-download models
cd SphinxAI
python -c "
from sentence_transformers import SentenceTransformer
model = SentenceTransformer('all-MiniLM-L6-v2')
"

# Convert to OpenVINO format
optimum-cli export openvino --model sentence-transformers/all-MiniLM-L6-v2 --output models/
```

#### "Database connection failed"
**Cause**: Incorrect database credentials or server issues
**Solution**:
1. Check database credentials in SMF Settings.php
2. Test connection manually: `mysql -u username -p database_name`
3. Verify database server is running
4. Check firewall settings and port accessibility

#### "Redis connection failed" 
**Cause**: Redis server not running or misconfigured
**Solution**:
```bash
# Check Redis status
sudo systemctl status redis

# Test connection
redis-cli ping  # Should return "PONG"

# Check configuration
grep -E "^(bind|port)" /etc/redis/redis.conf

# Restart Redis
sudo systemctl restart redis
```

### Debug Mode

For detailed troubleshooting, enable debug mode:

1. **Edit SphinxAI/config.json**:
```json
{
  "logging": {
    "level": "DEBUG",
    "file": "logs/debug.log",
    "max_size": "10MB"
  },
  "debug": {
    "enabled": true,
    "verbose_errors": true
  }
}
```

2. **Check Debug Logs**:
```bash
tail -f SphinxAI/logs/debug.log
```

3. **SMF Debug Mode**:
   - Go to SMF Admin → Configuration → Server Settings
   - Enable "Database Error Logging"
   - Enable "Show Database Queries"

### Getting Help

If problems persist:

1. **Check Documentation**: Review all guides in the `docs/` folder
2. **Search Issues**: Check GitHub issues for similar problems
3. **Community Support**: Post in SMF community forums
4. **Professional Support**: Available for enterprise installations

**When Requesting Help, Include**:
- PHP version (`php -v`)
- SMF version
- Operating system details
- Complete error messages
- Steps taken before the error
- Debug log excerpts (without sensitive information)

### Performance Optimization

#### Hardware Recommendations
- **CPU**: 4+ cores for concurrent searches
- **Memory**: 8GB+ for large forums with many models
- **Storage**: SSD for index files and model storage
- **Network**: Low-latency database connection

#### Software Optimization

**OpenVINO Model Conversion**:
```bash
# Install OpenVINO development tools
pip install openvino-dev

# Convert sentence transformer to OpenVINO
optimum-cli export openvino \
  --model sentence-transformers/all-MiniLM-L6-v2 \
  --output SphinxAI/models/openvino/
```

**Caching Configuration**:
```json
{
  "cache": {
    "enabled": true,
    "backend": "redis",
    "ttl": 3600,
    "max_size": 10000
  }
}
```

**Database Optimization**:
```sql
-- Add indexes for better search performance
CREATE INDEX idx_sphinx_ai_topic ON smf_sphinx_ai_index(topic_id);
CREATE INDEX idx_sphinx_ai_board ON smf_sphinx_ai_index(board_id); 
CREATE INDEX idx_sphinx_ai_date ON smf_sphinx_ai_index(indexed_date);
CREATE INDEX idx_sphinx_ai_score ON smf_sphinx_ai_index(relevance_score);
```

## Security Considerations

### File Permissions
```bash
# Set correct permissions
find SphinxAI/ -type f -exec chmod 644 {} \;
find SphinxAI/ -type d -exec chmod 755 {} \;
find php/ -type f -exec chmod 644 {} \;
find php/ -type d -exec chmod 755 {} \;

# Protect sensitive files
chmod 600 SphinxAI/config.json
chmod 600 SphinxAI/.env
```

### Network Security
- **Firewall**: Restrict Sphinx daemon access (port 9312) to localhost only
- **Database**: Use dedicated database user with minimal required permissions
- **API Access**: Implement rate limiting and authentication for API endpoints

### Data Privacy
- **Search Logs**: Configure log retention policies
- **User Data**: Ensure GDPR compliance for search history
- **Model Security**: Protect model files from unauthorized access

---

For additional help, consult the [Development Guide](DEVELOPMENT.md) or [Troubleshooting Guide](TROUBLESHOOTING.md).
