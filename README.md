# Sphinx AI Search for SMF

A powerful AI-enhanced search plugin for Simple Machines Forum (SMF) that combines Sphinx indexing with OpenVINO-optimized language models to provide intelligent search results with summaries and source linking.

## Features

- **AI-Powered Search**: Uses Hugging Face transformers with OpenVINO optimization
- **Intelligent Summaries**: Generates contextual summaries of search results
- **Source Linking**: Links back to original forum posts with confidence scores
- **Sphinx Integration**: Leverages Sphinx search daemon for efficient indexing
- **Modern UI**: Clean, responsive interface with live search suggestions
- **Admin Dashboard**: Comprehensive administration panel for configuration and monitoring

## Requirements

### System Requirements
- SMF 2.1.* or higher
- PHP 7.4 or higher
- Python 3.8 or higher
- MySQL 5.7 or higher
- Sphinx Search daemon 2.2.11 or higher

### Python Dependencies
- OpenVINO 2022.3.0+
- PyTorch 1.12.0+
- Transformers 4.20.0+
- Sentence Transformers 2.2.0+
- NumPy, SciPy, scikit-learn
- NLTK, spaCy
- See `SphinxAI/requirements.txt` for complete list

## Installation

### Step 1: Install SMF Plugin
1. Download the plugin package
2. Upload to your SMF forum directory
3. Install through SMF Admin Panel > Packages
4. The plugin will create necessary database tables and hooks

### Step 2: Setup Python Environment

#### Automated Installation (Recommended)
**Windows:**
```bash
install.bat
```

**Linux/Unix:**
```bash
chmod +x install.sh
./install.sh
```

The automated installation will:
- Install all Python dependencies
- Download and convert AI models
- Set up NLTK and spaCy data
- Handle Hugging Face authentication (optional)

#### Manual Installation
```bash
cd SphinxAI
python setup.py
```

Or step by step:
```bash
pip install -r requirements.txt
python -c "import nltk; nltk.download('punkt'); nltk.download('stopwords')"
python -m spacy download en_core_web_sm
```

#### Hugging Face Authentication (Optional)
Some AI models may require a Hugging Face token for access. If prompted during installation:

1. Go to https://huggingface.co/settings/tokens
2. Create a new token (read access is sufficient)
3. Enter the token when prompted, or skip and add later

You can also manually create `SphinxAI/config.ini`:
```ini
[huggingface]
token = your_token_here
```

### Step 3: Configure Plugin
1. Go to SMF Admin > Modifications > Sphinx AI Search
2. Configure model path and database settings
3. Set up Sphinx search daemon configuration
4. Run initial indexing

### Step 4: Setup Sphinx Search
1. Install Sphinx Search daemon
2. Configure `/etc/sphinx/sphinx.conf` (use generated config)
3. Start searchd daemon: `searchd --config /etc/sphinx/sphinx.conf`
4. Run initial indexing: `indexer --config /etc/sphinx/sphinx.conf smf_posts`

## Configuration

### Plugin Settings
- **Model Path**: Path to OpenVINO model file (.xml)
- **Max Results**: Maximum search results to return (1-100)
- **Summary Length**: Maximum summary length in characters (50-500)
- **Auto Indexing**: Automatically index new posts

### Database Configuration
Edit `SphinxAI/config.json`:
```json
{
  "database_settings": {
    "host": "localhost",
    "port": 3306,
    "database": "your_smf_database",
    "user": "your_db_user",
    "password": "your_db_password"
  }
}
```

### Sphinx Configuration
The plugin generates a Sphinx configuration file. Key settings:
- Source: MySQL database connection
- Index: Text processing and storage settings
- Searchd: Daemon configuration

## Usage

### For Users
1. Navigate to the AI Search page from the forum menu
2. Enter your search query in natural language
3. View AI-generated summaries and source links
4. Use keyboard shortcuts (Ctrl+K) for quick access

### For Administrators
1. Monitor search statistics and popular queries
2. Manage indexing and reindexing
3. Configure AI model settings
4. View performance metrics

## Advanced Features

### OpenVINO Model Optimization
For better performance, convert Hugging Face models to OpenVINO format:
```bash
# Install OpenVINO development tools
pip install openvino-dev

# Convert model
optimum-cli export openvino --model sentence-transformers/all-MiniLM-L6-v2 --output ./SphinxAI/models/
```

### Custom Models
You can use custom trained models:
1. Train or fine-tune your model
2. Convert to OpenVINO format
3. Update model path in plugin settings

### API Integration
The plugin provides REST API endpoints:
- `POST /index.php?action=sphinxai_api` - Search API
- Supports JSON requests and responses

## Troubleshooting

### Common Issues

**Python dependencies not found**
```bash
cd SphinxAI
pip install -r requirements.txt
```

**Sphinx daemon not running**
```bash
sudo systemctl start sphinxsearch
# or
searchd --config /etc/sphinx/sphinx.conf
```

**No search results**
- Check if indexing completed successfully
- Verify database connection
- Ensure Sphinx daemon is running

**Performance issues**
- Use OpenVINO optimized models
- Adjust batch sizes in configuration
- Monitor system resources

### Error Messages
- **Model not found**: Check model path in settings
- **Python not found**: Ensure Python 3.8+ is installed
- **Dependencies missing**: Run setup.py again
- **Index empty**: Run initial indexing

## Performance Optimization

### Recommended Settings
- **CPU**: 4+ cores, 8GB+ RAM
- **Storage**: SSD recommended for index files
- **Network**: Low latency database connection

### Scaling
- Use dedicated search server for large forums
- Implement caching for frequent queries
- Consider GPU acceleration for large models

## Security

### Permissions
- Configure user permissions for search access
- Limit search rate to prevent abuse
- Sanitize all user inputs

### Data Privacy
- Search queries are logged for analytics
- Configure retention policies
- Ensure compliance with privacy regulations

## Development

### File Structure
```
SphinxAI/
├── search_processor.py     # Main AI processing
├── sphinx_integration.py   # Sphinx daemon integration
├── openvino_handler.py     # OpenVINO model handling
├── requirements.txt        # Python dependencies
├── setup.py               # Installation script
├── config.json            # Configuration file
└── logs/                  # Log files
```

### API Documentation
See inline documentation in PHP and Python files.

### Contributing
1. Fork the repository
2. Create feature branch
3. Add tests for new functionality
4. Submit pull request

## License

This plugin is released under the MIT License. See LICENSE file for details.

## Support

For support, bug reports, and feature requests:
- Create an issue on GitHub
- Visit the SMF community forum
- Check the documentation wiki

## Changelog

### Version 1.0.0
- Initial release
- Basic AI search functionality
- Sphinx integration
- OpenVINO support
- Admin dashboard
- Multi-language support preparation
