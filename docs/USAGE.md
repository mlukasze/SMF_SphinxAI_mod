# 📚 Usage Documentation

Complete guide for using the SMF Sphinx AI Search Plugin from both user and administrator perspectives.

## Table of Contents

- [User Guide](#user-guide)
- [Administrator Guide](#administrator-guide)
- [API Documentation](#api-documentation)
- [Search Features](#search-features)
- [Advanced Usage](#advanced-usage)
- [Troubleshooting](#troubleshooting)

## User Guide

### Basic Search

1. **Access Search**
   - Navigate to the forum search page
   - Look for "AI Search" option alongside traditional search

2. **Enter Search Query**
   - Type your search terms naturally
   - Use full sentences or questions for best AI results
   - Example: "How to configure database settings?"

3. **View Results**
   - Results show relevance scores and AI-generated summaries
   - Click result titles to view full posts
   - Use filters to narrow results by board, date, or author

### Advanced Search Features

#### Semantic Search
- **Natural Language**: Ask questions in plain language
- **Context Understanding**: AI understands intent, not just keywords
- **Similarity**: Finds related content even with different wording

Example queries:
```
"Problems with email notifications"  → Finds posts about mail issues
"Database connection errors"         → Locates DB troubleshooting
"How to customize theme colors?"     → Discovers theming discussions
```

#### Search Suggestions
- Auto-complete suggestions appear as you type
- Based on popular queries and forum content
- Click suggestions to search immediately

#### Filters and Sorting
- **Date Range**: Last day, week, month, year, or custom
- **Boards**: Limit search to specific forum sections  
- **Authors**: Find posts by specific users
- **Sort By**: Relevance, date, author, or board

## Administrator Guide

### Plugin Management

#### Accessing Admin Panel
1. Go to **SMF Admin Panel**
2. Navigate to **Configuration > Modifications**
3. Find **Sphinx AI Search** in the list

#### Basic Configuration
```
✅ Enable/Disable Plugin
🔢 Set Maximum Results (1-50)
📏 Configure Summary Length (50-500 chars)
🤖 Toggle Auto-indexing
🎯 Choose Search Types (Full-text, Semantic, Combined)
```

#### Model Management
```
📂 Model Path: Select active AI model
🐍 Python Path: Set Python executable location
⏱️ Timeout: Configure processing timeout
🔄 Model Updates: Download and convert new models
```

### Search Analytics

#### Usage Statistics
- Total searches performed
- Most popular queries
- User engagement metrics
- Performance statistics

#### Search Patterns
- Peak usage times
- Common search terms
- Failed queries for improvement
- User behavior analysis

### Content Management

#### Indexing Control
```bash
# Manual index rebuild
php install_check.php rebuild-index

# Check indexing status  
php install_check.php index-status

# Index specific content
php install_check.php index-posts --from-date="2024-01-01"
```

#### Content Filtering
- Configure which boards to index
- Set minimum post quality thresholds
- Exclude specific content types
- Handle multi-language content

## API Documentation

### Search API Endpoints

#### POST /sphinx-ai/search
Perform AI-enhanced search

**Request:**
```json
{
    "query": "database connection problems",
    "max_results": 10,
    "search_type": "semantic",
    "filters": {
        "boards": [1, 2, 3],
        "date_from": "2024-01-01",
        "date_to": "2024-12-31",
        "author": "username"
    }
}
```

**Response:**
```json
{
    "status": "success",
    "query": "database connection problems",
    "total_results": 25,
    "processing_time": 1.23,
    "results": [
        {
            "id": 12345,
            "title": "MySQL Connection Error Fix",
            "summary": "Solution for database connection timeouts...",
            "content_preview": "If you're experiencing database...",
            "url": "/index.php?topic=12345.0",
            "board": "Technical Support",
            "author": "admin",
            "date": "2024-03-15T10:30:00Z",
            "relevance_score": 0.95,
            "confidence": 0.87
        }
    ],
    "suggestions": [
        "mysql timeout settings",
        "database configuration",
        "connection pool settings"
    ]
}
```

#### GET /sphinx-ai/suggestions
Get search suggestions

**Parameters:**
- `q`: Partial query string
- `limit`: Maximum suggestions (default: 5)

**Response:**
```json
{
    "status": "success",
    "suggestions": [
        "database configuration",
        "database backup",
        "database optimization"
    ]
}
```

### Admin API

#### GET /sphinx-ai/admin/stats
Get search statistics (admin only)

**Response:**
```json
{
    "total_searches": 15420,
    "searches_today": 234,
    "average_response_time": 1.45,
    "cache_hit_rate": 0.78,
    "top_queries": [
        {"query": "login problems", "count": 45},
        {"query": "theme customization", "count": 32}
    ],
    "model_info": {
        "current_model": "all-MiniLM-L6-v2",
        "model_size": "80MB",
        "last_updated": "2024-03-01T12:00:00Z"
    }
}
```

#### POST /sphinx-ai/admin/reindex
Trigger manual reindexing (admin only)

**Request:**
```json
{
    "full_rebuild": false,
    "boards": [1, 2, 3],
    "since_date": "2024-03-01"
}
```

## Search Features

### Semantic Understanding

#### Context Awareness
- Understands synonyms and related terms
- Recognizes different phrasings of same concept
- Considers context from surrounding text

#### Multi-language Support
- Automatic language detection
- Language-specific models for better accuracy
- Cross-language search capabilities

#### Content Types
- **Forum Posts**: Main content search
- **Private Messages**: With appropriate permissions
- **User Profiles**: Public information only
- **Attachments**: Text content extraction

### Result Enhancement

#### AI Summaries
- Contextual summaries for each result
- Highlights relevant portions
- Configurable summary length

#### Relevance Scoring
- AI confidence scores
- Traditional keyword matching scores
- Combined relevance ranking

#### Smart Highlighting
- Highlights search terms in context
- Shows semantic matches, not just exact words
- Preserves formatting and structure

## Advanced Usage

### Power User Features

#### Query Operators
```
"exact phrase"              # Exact phrase matching
+required -excluded         # Required and excluded terms
title:search terms          # Search in titles only
author:username             # Posts by specific author
board:"Technical Support"   # Posts in specific board
date:2024-03-01..2024-03-31 # Date range search
```

#### Search Modifiers
```
similar:12345              # Find posts similar to post #12345
related:topic              # Find related discussions
summary:query              # Get AI summary of search topic
translate:query to:english # Translate and search
```

### Integration Features

#### RSS Feeds
```
/sphinx-ai/rss?q=search+terms        # RSS feed of search results
/sphinx-ai/rss?q=author:username     # User's posts RSS
/sphinx-ai/rss?q=board:support       # Board-specific RSS
```

#### Email Alerts
- Subscribe to search queries
- Get notified of new matching posts
- Configurable frequency and format

#### Mobile API
- Optimized endpoints for mobile apps
- Compressed responses
- Offline caching support

### Custom Integrations

#### Webhooks
Configure webhooks for search events:

```json
{
    "event": "search_performed",
    "webhook_url": "https://your-site.com/webhook",
    "filters": {
        "min_results": 5,
        "users": ["admin", "moderator"]
    }
}
```

#### Plugin Extensions
- Custom search result formatters
- Additional content sources
- Third-party service integrations

## Troubleshooting

### Common Issues

#### Search Returns No Results
1. **Check Query**: Ensure query is meaningful and not too specific
2. **Verify Indexing**: Check if content is properly indexed
3. **Model Status**: Ensure AI models are loaded correctly
4. **Permissions**: Verify user has access to searched content

#### Slow Search Performance
1. **Enable Caching**: Configure Redis caching
2. **Optimize Models**: Use compressed/quantized models
3. **Database Tuning**: Add recommended indexes
4. **Resource Allocation**: Increase memory/CPU limits

#### AI Features Not Working
1. **Python Environment**: Verify Python setup and dependencies
2. **Model Files**: Check model files exist and are accessible
3. **Configuration**: Review AI-specific settings
4. **Logs**: Check error logs for detailed information

### Error Messages

#### "AI service unavailable"
- **Cause**: Python service not running or unreachable
- **Solution**: Check Python process, restart if needed

#### "Model not found"
- **Cause**: AI model files missing or path incorrect
- **Solution**: Re-download models, verify paths

#### "Search index out of date"
- **Cause**: Sphinx index needs rebuilding
- **Solution**: Run manual reindex

#### "Rate limit exceeded"
- **Cause**: Too many searches from user/IP
- **Solution**: Wait for cooldown period or adjust limits

### Performance Optimization

#### For Users
- Use specific search terms for better results
- Utilize filters to narrow down results
- Take advantage of search suggestions

#### For Administrators
- Monitor search analytics regularly
- Optimize based on usage patterns
- Keep models and indexes updated
- Configure appropriate caching and rate limits

## Technical Overview

### Modern PHP Architecture (8.1+)

The plugin is built with modern PHP 8.1+ features for improved performance and maintainability:

- **Type Safety**: Extensive use of enums and union types for configuration and search parameters
- **Performance**: Constructor property promotion and readonly properties reduce memory overhead
- **Reliability**: Match expressions and nullsafe operators improve error handling
- **Maintainability**: Clear separation of concerns with dependency injection and service classes

### Search Flow

1. **Query Processing** (PHP with enums for search types)
2. **Cache Check** (Redis integration with type-safe cache keys)
3. **AI Analysis** (Python models via secure subprocess execution)
4. **Result Aggregation** (PHP services with union types for flexible data handling)
5. **Response Formatting** (Modern PHP templating with readonly configuration)

---

Next: [Performance Guide](PERFORMANCE.md) | [Troubleshooting](TROUBLESHOOTING.md)
