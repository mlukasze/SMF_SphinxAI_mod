# Sphinx AI Search Plugin - Installation & Usage Guide

## Table of Contents
1. [Overview](#overview)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [Usage](#usage)
6. [Administration](#administration)
7. [Troubleshooting](#troubleshooting)
8. [Performance Optimization](#performance-optimization)
9. [Advanced Features](#advanced-features)

## Overview

The Sphinx AI Search Plugin enhances your SMF forum with intelligent search capabilities using:
- **AI-powered search**: Semantic understanding of user queries
- **Smart summaries**: Auto-generated summaries of search results
- **Source linking**: Direct links to relevant forum posts
- **Sphinx integration**: High-performance full-text search
- **OpenVINO optimization**: Accelerated AI inference

## Requirements

### System Requirements
- **Operating System**: Linux (Ubuntu 20.04+, CentOS 7+), Windows 10+, macOS 10.15+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 7.4+ (8.0+ recommended)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Python**: 3.8+ (3.9+ recommended)
- **Memory**: 4GB RAM minimum, 8GB+ recommended
- **Storage**: 2GB free space for models and indices

### SMF Requirements
- SMF 2.1.* or higher
- Admin access to install modifications
- Database modification permissions

### Python Dependencies
Core dependencies (automatically installed):
- OpenVINO 2022.3.0+
- PyTorch 1.12.0+
- Transformers 4.20.0+
- Sentence Transformers 2.2.0+
- NumPy, SciPy, scikit-learn
- NLTK, spaCy

## Installation

### Step 1: Download and Extract
1. Download the plugin package
2. Extract to a temporary directory
3. Upload files to your SMF root directory

### Step 2: Install SMF Plugin
1. Login to SMF Admin Panel
2. Go to **Admin → Packages**
3. Click **Browse** and select `package-info.xml`
4. Click **Install**
5. Confirm installation

### Step 3: Python Environment Setup

#### Windows
```batch
# Run as administrator
cd path\to\your\smf\directory
install.bat
```

#### Linux/macOS
```bash
cd /path/to/your/smf/directory
chmod +x install.sh
./install.sh
```

#### Manual Installation
```bash
cd SphinxAI
pip install -r requirements.txt
python -c "import nltk; nltk.download('punkt'); nltk.download('stopwords')"
python -m spacy download en_core_web_sm
```

### Step 4: Configure Plugin
1. Go to **Admin → Modifications → Sphinx AI Search**
2. Configure basic settings:
   - **Model Path**: Leave empty for default models
   - **Max Results**: 10-20 recommended
   - **Summary Length**: 150-300 characters
   - **Auto Indexing**: Enable for automatic updates

### Step 5: Database Setup
The plugin automatically creates required tables:
- `smf_sphinx_ai_index`: Main search index
- `smf_sphinx_ai_searches`: Search history and analytics

### Step 6: Sphinx Search Daemon

#### Ubuntu/Debian
```bash
sudo apt-get update
sudo apt-get install sphinxsearch
sudo systemctl enable sphinxsearch
```

#### CentOS/RHEL
```bash
sudo yum install epel-release
sudo yum install sphinx
sudo systemctl enable searchd
```

#### Windows
1. Download Sphinx for Windows
2. Extract to `C:\sphinx`
3. Configure as Windows service

#### Configuration
```bash
# Copy sample configuration
sudo cp sphinx.conf.sample /etc/sphinxsearch/sphinx.conf

# Edit configuration
sudo nano /etc/sphinxsearch/sphinx.conf

# Start daemon
sudo systemctl start sphinxsearch
```

### Step 7: Initial Indexing
1. Go to **Admin → Modifications → Sphinx AI Search → Indexing**
2. Click **Start Indexing**
3. Wait for completion (may take several minutes)

## Configuration

### Plugin Settings

#### Basic Settings
- **Model Path**: Path to OpenVINO model (optional)
- **Max Results**: Maximum search results (1-100)
- **Summary Length**: Characters in AI summaries (50-500)
- **Auto Indexing**: Automatically index new posts

#### Advanced Settings
Edit `SphinxAI/config.json`:

```json
{
  "model_settings": {
    "device": "CPU",
    "confidence_threshold": 0.1,
    "embedding_model": "all-MiniLM-L6-v2"
  },
  "sphinx_settings": {
    "host": "localhost",
    "port": 9312,
    "index_name": "smf_posts"
  },
  "database_settings": {
    "host": "localhost",
    "database": "your_smf_db",
    "user": "your_db_user",
    "password": "your_db_password"
  }
}
```

### Sphinx Configuration

Edit `/etc/sphinxsearch/sphinx.conf`:

```conf
# Database connection
sql_host = localhost
sql_user = your_db_user
sql_pass = your_password
sql_db = your_smf_database

# Index paths
path = /var/lib/sphinxsearch/data/smf_posts

# Daemon settings
listen = 127.0.0.1:9312
log = /var/log/sphinxsearch/searchd.log
pid_file = /var/run/sphinxsearch/searchd.pid
```

### User Permissions
Configure user permissions in SMF:
1. Go to **Admin → Members → Permissions**
2. Find **Sphinx AI Search** permissions
3. Set appropriate access levels

## Usage

### For Users

#### Basic Search
1. Navigate to **AI Search** from the main menu
2. Enter your query in natural language
3. View results with AI-generated summaries
4. Click source links to view original posts

#### Advanced Features
- **Keyboard Shortcuts**: `Ctrl+K` to focus search
- **Live Search**: Results appear as you type
- **Search History**: Previous searches are saved
- **Mobile Responsive**: Works on all devices

#### Search Tips
- Use natural language queries
- Ask specific questions
- Include relevant keywords
- Use quotes for exact phrases

### For Administrators

#### Monitoring
- **Search Statistics**: View usage analytics
- **Popular Queries**: See most searched terms
- **Performance Metrics**: Monitor response times
- **Error Logs**: Check for issues

#### Maintenance
- **Reindexing**: Refresh search index
- **Cache Management**: Clear cached results
- **Model Updates**: Update AI models
- **Database Cleanup**: Remove old data

## Administration

### Admin Dashboard
Access via **Admin → Modifications → Sphinx AI Search**

#### Settings Tab
- Configure AI model parameters
- Set search result limits
- Enable/disable features
- Update database connection

#### Indexing Tab
- View indexing statistics
- Start manual reindexing
- Monitor indexing progress
- Schedule automatic updates

#### Statistics Tab
- Search volume analytics
- Popular search terms
- User engagement metrics
- Performance statistics

### Maintenance Tasks

#### Regular Tasks
- **Daily**: Check error logs
- **Weekly**: Review search statistics
- **Monthly**: Update AI models
- **Quarterly**: Full reindexing

#### Backup Procedures
```bash
# Backup search index
sudo cp -r /var/lib/sphinxsearch/data /backup/sphinx/

# Backup database
mysqldump -u root -p smf_database > smf_backup.sql

# Backup plugin files
tar -czf smf_ai_plugin.tar.gz SphinxAI/
```

## Troubleshooting

### Common Issues

#### Plugin Not Working
1. **Check PHP Version**: Ensure PHP 7.4+
2. **Verify Permissions**: Check file permissions
3. **Database Connection**: Test database connectivity
4. **Error Logs**: Check SMF and PHP error logs

#### Python Dependencies
```bash
# Reinstall dependencies
pip install -r requirements.txt --force-reinstall

# Check imports
python -c "import torch, transformers, sentence_transformers"
```

#### Sphinx Issues
```bash
# Check daemon status
sudo systemctl status sphinxsearch

# Test connection
mysql -h127.0.0.1 -P9306

# Check logs
sudo tail -f /var/log/sphinxsearch/searchd.log
```

#### Performance Problems
1. **Increase Memory**: Add more RAM
2. **Optimize Models**: Use OpenVINO optimization
3. **Cache Configuration**: Enable result caching
4. **Database Tuning**: Optimize MySQL settings

### Error Messages

#### "Python not found"
- Install Python 3.8+
- Add Python to system PATH
- Verify installation: `python --version`

#### "Model not found"
- Check model path in settings
- Verify file permissions
- Download required models

#### "Sphinx daemon not running"
- Start daemon: `sudo systemctl start sphinxsearch`
- Check configuration: `/etc/sphinxsearch/sphinx.conf`
- Verify port availability: `netstat -tlnp | grep 9312`

#### "Database connection failed"
- Check database credentials
- Verify database server is running
- Test connection manually

### Debug Mode
Enable debug mode for troubleshooting:
```json
{
  "logging": {
    "level": "DEBUG",
    "file": "logs/debug.log"
  }
}
```

## Performance Optimization

### Hardware Recommendations
- **CPU**: 4+ cores for concurrent searches
- **Memory**: 8GB+ for large indices
- **Storage**: SSD for index files
- **Network**: Low-latency database connection

### Software Optimization

#### OpenVINO Models
Convert models for better performance:
```bash
# Install OpenVINO dev tools
pip install openvino-dev

# Convert model
optimum-cli export openvino --model sentence-transformers/all-MiniLM-L6-v2 --output SphinxAI/models/
```

#### Caching Strategy
```json
{
  "cache": {
    "enabled": true,
    "ttl": 3600,
    "max_size": 10000
  }
}
```

#### Database Optimization
```sql
-- Add indexes for better performance
CREATE INDEX idx_sphinx_ai_topic ON smf_sphinx_ai_index(topic_id);
CREATE INDEX idx_sphinx_ai_board ON smf_sphinx_ai_index(board_id);
CREATE INDEX idx_sphinx_ai_date ON smf_sphinx_ai_index(indexed_date);
```

### Monitoring
- **Response Times**: Track search performance
- **Memory Usage**: Monitor memory consumption
- **CPU Usage**: Check processor utilization
- **Error Rates**: Monitor failure rates

## Advanced Features

### Custom Models
1. **Train Custom Models**: Use your forum data
2. **Fine-tune Existing Models**: Improve accuracy
3. **Convert to OpenVINO**: Optimize for production
4. **Deploy Models**: Update plugin configuration

### API Integration
The plugin provides REST API endpoints:
```javascript
// Search API
fetch('/index.php?action=sphinxai_api', {
  method: 'POST',
  body: JSON.stringify({
    api_action: 'search',
    query: 'search query'
  })
});
```

### Multi-language Support
Configure for multiple languages:
```json
{
  "model_settings": {
    "embedding_model": "sentence-transformers/distiluse-base-multilingual-cased"
  }
}
```

### Clustering and Scaling
For large forums:
- **Distributed Search**: Multiple search servers
- **Load Balancing**: Balance search requests
- **Caching Layers**: Redis/Memcached integration
- **Database Sharding**: Distribute index data

## Support and Updates

### Getting Help
- **Documentation**: Check README.md and guides
- **Community**: SMF community forums
- **Issues**: GitHub issue tracker
- **Professional Support**: Available for enterprise

### Updates
- **Plugin Updates**: Through SMF package manager
- **Model Updates**: Download new models
- **Security Updates**: Apply promptly
- **Feature Updates**: Review changelog

### Contributing
1. Fork the repository
2. Create feature branches
3. Add comprehensive tests
4. Submit pull requests
5. Follow coding standards

---

For more information, visit the project repository or contact support.
