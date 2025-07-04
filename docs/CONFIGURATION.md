# ⚙️ Configuration Guide

Complete configuration guide for the SMF Sphinx AI Search Plugin, covering all settings and optimization options.

## Table of Contents

- [Overview](#overview)
- [SMF Admin Configuration](#smf-admin-configuration)
- [Python Configuration](#python-configuration)
- [Sphinx Search Configuration](#sphinx-search-configuration)
- [Redis Configuration](#redis-configuration)
- [Performance Settings](#performance-settings)
- [Security Settings](#security-settings)
- [Advanced Configuration](#advanced-configuration)
- [Environment Variables](#environment-variables)

## Overview

The plugin configuration is distributed across several components:
- **SMF Admin Panel**: Main plugin settings and user interface options
- **Python Configuration**: AI model settings and processing parameters  
- **Sphinx Search**: Full-text search engine configuration
- **Redis**: Caching and rate limiting configuration

## SMF Admin Configuration

Access the configuration via: **SMF Admin > Configuration > Modifications > Sphinx AI Search**

### Basic Settings

#### General Options
```
✅ Enable Sphinx AI Search
   Enables/disables the entire plugin functionality

🔢 Maximum Search Results: 10
   Number of results to display per search (1-50)

📏 Summary Length: 200
   Maximum characters in result summaries (50-500)

🤖 Auto-indexing: Enabled
   Automatically index new posts and updates
```

#### Search Behavior
```
🎯 Search Type: Semantic + Full-text
   Options: Full-text only, Semantic only, Combined

⚡ Enable Suggestions: Yes
   Show search suggestions as user types

🎨 Enable Highlighting: Yes
   Highlight search terms in results

📊 Show Confidence Scores: No
   Display AI confidence scores to users
```

#### AI Model Settings
```
📂 Model Path: SphinxAI/models/openvino/all-MiniLM-L6-v2
   Path to the active embedding model

🐍 Python Executable: /usr/bin/python3
   Path to Python interpreter with required packages

⏱️ Timeout: 30 seconds
   Maximum time for AI processing per request
```

### Advanced Settings

#### Caching Configuration
```
🗄️ Enable Redis Caching: Yes
   Use Redis for result caching

🏠 Redis Host: localhost
   Redis server hostname or IP

🔌 Redis Port: 6379
   Redis server port

🔐 Redis Password: (optional)
   Redis authentication password

⏰ Cache TTL: 3600 seconds
   How long to cache search results
```

#### Rate Limiting
```
🚦 Enable Rate Limiting: Yes
   Prevent search abuse

👤 User Rate Limit: 60 requests/hour
   Searches per user per hour

🌐 IP Rate Limit: 100 requests/hour
   Searches per IP address per hour

⛔ Blocked IPs: (comma-separated)
   IP addresses to block from search
```

#### Database Settings
```
🗃️ Sphinx Host: localhost
   Sphinx search daemon hostname

🔗 Sphinx Port: 9306
   Sphinx MySQL protocol port

📊 Index Name: smf_posts
   Name of the Sphinx search index

🔄 Auto-rebuild Index: Daily
   How often to rebuild the full index
```

## Python Configuration

Configuration files are located in the `SphinxAI/` directory.

### config.ini

Create `SphinxAI/config.ini` for Python-specific settings:

```ini
[general]
# Logging level: DEBUG, INFO, WARNING, ERROR
log_level = INFO
log_file = logs/sphinx_ai.log

# Model settings
default_model = all-MiniLM-L6-v2
model_cache_size = 3
embedding_batch_size = 32

[huggingface]
# Optional: Hugging Face authentication token
token = your_token_here
cache_dir = ~/.cache/huggingface

[openvino]
# OpenVINO inference settings
device = CPU
num_threads = 4
enable_profiling = false

[processing]
# Text processing settings
max_text_length = 1000
chunk_size = 512
chunk_overlap = 50

[search]
# Search algorithm settings
similarity_threshold = 0.3
max_results = 100
enable_reranking = true

[performance]
# Performance optimization
enable_caching = true
cache_embeddings = true
parallel_processing = true
max_workers = 4
```

### model_config.ini

Model-specific configuration (auto-generated during setup):

```ini
[paths]
# Model storage directories  
models_dir = SphinxAI/models
original_dir = SphinxAI/models/original
openvino_dir = SphinxAI/models/openvino
compressed_dir = SphinxAI/models/compressed

[compression]
# NNCF compression settings
compression_ratio_threshold = 0.1
calibration_samples = 100
quantization_level = int8

[models]
# Active model configurations
embedding_model = all-MiniLM-L6-v2
llm_model = DialoGPT-medium
enable_quantization = true
```

## Sphinx Search Configuration

Edit `/etc/sphinxsearch/sphinx.conf` or your Sphinx configuration file:

### Source Configuration

```conf
source smf_posts
{
    type                = mysql
    sql_host            = localhost
    sql_user            = your_db_user
    sql_pass            = your_db_pass
    sql_db              = your_smf_database
    sql_port            = 3306
    
    # Main query for indexing posts
    sql_query = \
        SELECT p.id_msg as id, \
               t.id_topic, \
               p.id_board, \
               m.real_name as poster_name, \
               p.poster_time, \
               t.num_replies, \
               p.subject, \
               p.body as content, \
               b.name as board_name \
        FROM smf_messages p \
        LEFT JOIN smf_topics t ON t.id_topic = p.id_topic \
        LEFT JOIN smf_boards b ON b.id_board = p.id_board \
        LEFT JOIN smf_members m ON m.id_member = p.id_member \
        WHERE p.approved = 1 AND t.approved = 1
    
    # Attributes for filtering and sorting
    sql_attr_uint       = id_topic
    sql_attr_uint       = id_board  
    sql_attr_uint       = poster_time
    sql_attr_uint       = num_replies
    sql_attr_string     = poster_name
    sql_attr_string     = board_name
}
```

### Index Configuration

```conf
index smf_posts
{
    source              = smf_posts
    path                = /var/lib/sphinxsearch/data/smf_posts
    
    # Morphology and language
    morphology          = stem_en, stem_pl
    charset_table       = 0..9, A..Z->a..z, _, a..z, \
                         U+0104->U+0105, U+0105, U+0106->U+0107, U+0107, \
                         U+0118->U+0119, U+0119, U+0141->U+0142, U+0142, \
                         U+0143->U+0144, U+0144, U+00D3->U+00F3, U+00F3, \
                         U+015A->U+015B, U+015B, U+0179->U+017A, U+017A, \
                         U+017B->U+017C, U+017C
    
    # Text processing
    min_word_len        = 2
    min_prefix_len      = 3
    min_infix_len       = 3
    enable_star         = 1
    expand_keywords     = 1
    
    # Performance settings
    mem_limit           = 256M
    html_strip          = 1
    html_remove_elements = script, style
}
```

### Indexer Configuration

```conf
indexer
{
    mem_limit           = 256M
    max_iops            = 40
    max_iosize          = 1048576
    write_buffer        = 8M
}
```

### Search Daemon Configuration

```conf
searchd
{
    listen              = localhost:9306:mysql41
    listen              = localhost:9312
    log                 = /var/log/sphinxsearch/searchd.log
    query_log           = /var/log/sphinxsearch/query.log
    pid_file            = /var/run/sphinxsearch/searchd.pid
    
    # Performance
    max_children        = 30
    seamless_rotate     = 1
    preopen_indexes     = 1
    unlink_old          = 1
    
    # Security
    max_matches         = 10000
    max_packet_size     = 128M
    mysql_version_string = 5.7.0
}
```

## Redis Configuration

Edit `/etc/redis/redis.conf` for optimal performance:

### Basic Configuration

```conf
# Network
bind 127.0.0.1
port 6379
protected-mode yes

# Memory management
maxmemory 512mb
maxmemory-policy allkeys-lru

# Persistence (optional for cache-only usage)
save ""
appendonly no

# Performance
tcp-keepalive 300
timeout 0
```

### Advanced Redis Settings

```conf
# Logging
loglevel notice
logfile /var/log/redis/redis-server.log

# Client connections
maxclients 10000

# Security (if needed)
requirepass your_redis_password
rename-command FLUSHDB ""
rename-command FLUSHALL ""

# Performance tuning
hash-max-ziplist-entries 512
hash-max-ziplist-value 64
list-max-ziplist-size -2
set-max-intset-entries 512
```

## Performance Settings

### PHP Performance (php.ini)

```ini
# Memory limits
memory_limit = 512M
max_execution_time = 60
max_input_time = 60

# File uploads
upload_max_filesize = 10M
post_max_size = 10M

# Session handling
session.gc_maxlifetime = 7200
session.cache_expire = 180

# OPcache (recommended)
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 2
```

### MySQL/MariaDB Optimization

```sql
-- Add indexes for better search performance
ALTER TABLE smf_messages ADD INDEX idx_sphinx_search (id_board, id_topic, approved, poster_time);
ALTER TABLE smf_topics ADD INDEX idx_sphinx_topics (id_board, approved, num_replies, id_last_msg);
ALTER TABLE smf_boards ADD INDEX idx_sphinx_boards (id_cat, child_level, board_order);

-- Optimize tables
OPTIMIZE TABLE smf_messages, smf_topics, smf_boards;
```

### System Performance

```bash
# Increase file limits (in /etc/security/limits.conf)
www-data soft nofile 65536
www-data hard nofile 65536

# Optimize kernel parameters (in /etc/sysctl.conf)
vm.swappiness = 10
vm.vfs_cache_pressure = 50
net.core.somaxconn = 1024
```

## Security Settings

### Access Control

```php
// In SMF Admin settings
$modSettings['sphinx_ai_enabled_groups'] = '1,2,3'; // Admin, Global Mod, Mod
$modSettings['sphinx_ai_max_queries_guest'] = '10';  // Guest query limit
$modSettings['sphinx_ai_require_login'] = true;     // Require login for AI search
```

### Input Validation

```ini
# In config.ini
[security]
max_query_length = 500
allowed_file_types = txt,pdf,doc,docx
sanitize_input = true
escape_output = true
enable_csrf = true
```

### Rate Limiting Configuration

```ini
[rate_limiting]
# Per-user limits
user_requests_per_minute = 10
user_requests_per_hour = 60
user_requests_per_day = 500

# Per-IP limits
ip_requests_per_minute = 20
ip_requests_per_hour = 100
ip_requests_per_day = 1000

# Penalties
penalty_duration = 300  # seconds
max_penalties = 3
ban_duration = 3600    # seconds
```

## Advanced Configuration

### Multi-language Support

```ini
[languages]
# Supported languages
supported_languages = en,pl,de,fr
default_language = en

# Language-specific models
model_en = all-MiniLM-L6-v2
model_pl = polish-roberta-base
model_de = german-distilbert-base
model_fr = french-camembert-base

# Language detection
auto_detect_language = true
language_threshold = 0.8
```

### Custom Search Algorithms

```python
# In custom_config.py
SEARCH_ALGORITHMS = {
    'semantic': {
        'weight': 0.7,
        'threshold': 0.3,
        'rerank': True
    },
    'keyword': {
        'weight': 0.3,
        'boost_title': 2.0,
        'boost_recent': 1.2
    },
    'hybrid': {
        'combine_method': 'weighted_sum',
        'normalize_scores': True
    }
}
```

### Model Switching

```ini
[model_management]
# Hot-swapping models
enable_model_switching = true
warm_up_models = true
max_loaded_models = 2

# A/B testing
enable_ab_testing = false
test_model = all-mpnet-base-v2
test_percentage = 10
```

## Environment Variables

Set these in your environment or `.env` file:

```bash
# Core settings
SPHINX_AI_DEBUG=false
SPHINX_AI_LOG_LEVEL=INFO
SPHINX_AI_MODEL_PATH=/path/to/models

# Database
SPHINX_AI_DB_HOST=localhost
SPHINX_AI_DB_PORT=9306
SPHINX_AI_DB_INDEX=smf_posts

# Redis
SPHINX_AI_REDIS_HOST=localhost
SPHINX_AI_REDIS_PORT=6379
SPHINX_AI_REDIS_PASSWORD=optional_password

# API keys
HUGGINGFACE_HUB_TOKEN=your_token_here
OPENAI_API_KEY=optional_for_advanced_features

# Performance
SPHINX_AI_MAX_WORKERS=4
SPHINX_AI_BATCH_SIZE=32
SPHINX_AI_TIMEOUT=30

# Security
SPHINX_AI_RATE_LIMIT=true
SPHINX_AI_CSRF_PROTECTION=true
SPHINX_AI_ALLOWED_IPS=192.168.1.0/24,10.0.0.0/8
```

## Configuration Validation

### Test Configuration

```bash
# Test SMF integration
cd SphinxAI
python main.py test-config

# Test model loading
python main.py test-models

# Test database connection
python main.py test-database

# Test Redis connection  
python main.py test-redis

# Run full system check
python main.py system-check
```

### Performance Testing

```bash
# Benchmark search performance
python main.py benchmark --queries 100

# Test memory usage
python main.py memory-test

# Profile model inference
python main.py profile-models
```

### Configuration Backup

```bash
# Backup all configuration files
tar -czf sphinx_ai_config_backup.tar.gz \
    SphinxAI/config.ini \
    SphinxAI/model_config.ini \
    /etc/sphinxsearch/sphinx.conf \
    /etc/redis/redis.conf

# Restore configuration
tar -xzf sphinx_ai_config_backup.tar.gz
```

---

Next: [Usage Documentation](USAGE.md) | [Performance Guide](PERFORMANCE.md)
