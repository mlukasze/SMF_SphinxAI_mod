# Sphinx AI Search for SMF

A powerful AI-enhanced search plugin for Simple Machines Forum (SMF) that combines Sphinx indexing with OpenVINO-optimized language models to provide intelligent search results with summaries and source linking.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
  - [System Requirements](#system-requirements)
  - [Python Dependencies](#python-dependencies)
- [Quick Start](#quick-start)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Documentation](#documentation)
- [Development](#development)
- [Contributing](#contributing)
- [License](#license)
- [Support](#support)

## Features

### 🚀 Core Features
- **AI-Powered Search**: Uses Hugging Face transformers with OpenVINO optimization for semantic search
- **Intelligent Summaries**: Generates contextual summaries of search results using advanced NLP
- **Source Linking**: Links back to original forum posts with confidence scores and relevance ranking
- **Real-time Indexing**: Automatically indexes new posts and updates existing ones
- **Multi-language Support**: Optimized for Polish language with configurable language models

### 🔧 Technical Features
- **Sphinx Integration**: Leverages Sphinx search daemon for efficient full-text indexing
- **Redis Caching**: Implements Redis-based caching for improved performance
- **Rate Limiting**: Built-in rate limiting to prevent API abuse
- **Security**: Comprehensive security measures including CSRF protection, SQL injection prevention
- **Performance Optimization**: Database indexes, query optimization, and model compression

### 🎨 User Interface
- **Modern UI**: Clean, responsive interface with live search suggestions
- **Admin Dashboard**: Comprehensive administration panel for configuration and monitoring
- **Search Analytics**: Track search patterns, popular queries, and performance metrics
- **Mobile Support**: Fully responsive design for mobile and tablet devices

## Requirements

### System Requirements
- **SMF**: 2.1.* or higher
- **PHP**: 7.4+ (8.0+ recommended)
- **Python**: 3.8+ (3.10+ recommended)
- **MySQL**: 5.7+ or MariaDB 10.3+
- **Sphinx Search**: 2.2.11+ (3.x recommended)
- **Redis**: 6.0+ (for caching)
- **Memory**: 4GB+ RAM recommended (8GB+ for large forums)
- **Storage**: 2GB+ free space for models and indexes

### Python Dependencies

#### Core Runtime Dependencies
```
numpy>=2.0.0,<3.0.0              # NumPy 2.x for array operations
redis>=4.5.0                     # Redis client for caching
sentence-transformers>=2.2.0     # Semantic embeddings (includes torch, transformers, etc.)
openvino-genai>=2025.2.0         # OpenVINO inference optimization
nltk>=3.7                        # Natural language processing
spacy>=3.4.0                     # Advanced NLP pipeline
pymysql>=1.0.2                   # MySQL connectivity for Sphinx
requests>=2.28.0                 # HTTP client
python-dateutil>=2.8.2           # Date utilities
pyyaml>=6.0                      # YAML configuration parsing
python-dotenv>=0.20.0            # Environment variables
```

#### Model Conversion Dependencies (Development Only)
```
optimum[openvino]>=1.14.0        # Model optimization for OpenVINO
nncf>=2.9.0                      # Neural network compression
```

#### Development Dependencies (Optional)
```
pytest>=7.1.0                   # Testing framework
pytest-cov>=3.0.0               # Coverage reporting
black>=22.0.0                   # Code formatting
flake8>=5.0.0                   # Linting
```

> **Note**: `sentence-transformers` automatically installs: `torch`, `transformers`, `huggingface-hub`, `scikit-learn`, `scipy`

## Quick Start

### 🚀 Automated Installation (Recommended)

**Windows:**
```cmd
install.bat
```

**Linux/macOS:**
```bash
chmod +x install.sh
./install.sh
```

### 📋 Manual Installation Steps

1. **Install SMF Plugin**
   - Upload plugin files to SMF directory
   - Install via SMF Admin Panel > Packages

2. **Setup Python Environment**
   - Create virtual environment
   - Install dependencies: `pip install -r SphinxAI/requirements-runtime.txt`

3. **Configure Services**
   - Setup Redis server
   - Configure Sphinx Search daemon
   - Download and convert AI models

4. **Plugin Configuration**
   - Access SMF Admin > Configuration > Sphinx AI Search
   - Configure model paths and search settings

## Installation

For detailed installation instructions, see: **[📖 Installation Guide](docs/INSTALLATION.md)**

## Configuration

For configuration options and setup, see: **[⚙️ Configuration Guide](docs/CONFIGURATION.md)**

## Usage

For user guides and API documentation, see: **[📚 Usage Documentation](docs/USAGE.md)**

## Documentation

### 📚 Comprehensive Guides

| Document | Description |
|----------|-------------|
| [📖 Installation Guide](docs/INSTALLATION.md) | Step-by-step installation instructions |
| [⚙️ Configuration Guide](docs/CONFIGURATION.md) | Configuration options and setup |
| [📚 Usage Documentation](docs/USAGE.md) | User guides and API documentation |
| [🤖 Model Management](docs/MODELS.md) | Model download, conversion, and optimization |
| [🔧 Development Guide](docs/DEVELOPMENT.md) | Development setup and contribution guidelines |
| [🛡️ Security Guide](docs/SECURITY.md) | Security considerations and best practices |
| [⚡ Performance Guide](docs/PERFORMANCE.md) | Performance optimization and tuning |
| [🐛 Troubleshooting](docs/TROUBLESHOOTING.md) | Common issues and solutions |

### 🔧 Technical Documentation

- **Architecture**: Modular design with PHP controllers and Python AI services
- **Security**: CSRF protection, SQL injection prevention, input validation
- **Performance**: Redis caching, database optimization, model compression
- **Compatibility**: SMF 2.1+, PHP 7.4+, Python 3.8+

## Development

### 🛠️ Development Setup

```bash
# Clone repository
git clone https://github.com/mlukasze/SMF_SphinxAI_mod.git
cd SMF_SphinxAI_mod

# Install development dependencies
pip install -r SphinxAI/requirements.txt

# Run tests
python -m pytest SphinxAI/tests/
```

### 📦 Project Structure

```
SMF_SphinxAI_mod/
├── php/                    # PHP components
│   ├── controllers/        # MVC controllers
│   ├── services/          # Business logic services
│   ├── handlers/          # Event and data handlers
│   └── utils/             # Utility classes
├── SphinxAI/              # Python AI components
│   ├── core/              # Core functionality
│   ├── handlers/          # AI model handlers
│   ├── utils/             # Utility modules
│   └── models/            # Model storage (created during setup)
├── docs/                  # Documentation
└── install.*              # Installation scripts
```

## Contributing

We welcome contributions! Please see our [Development Guide](docs/DEVELOPMENT.md) for details on:

- Code style and standards
- Testing requirements
- Pull request process
- Issue reporting

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

### 🆘 Getting Help

- **Documentation**: Check our comprehensive [docs](docs/) folder
- **Issues**: Report bugs via [GitHub Issues](https://github.com/mlukasze/SMF_SphinxAI_mod/issues)
- **Discussions**: Join [GitHub Discussions](https://github.com/mlukasze/SMF_SphinxAI_mod/discussions)

### 🐛 Bug Reports

When reporting bugs, please include:
- SMF version and PHP version
- Python version and installed packages
- Error messages and logs
- Steps to reproduce the issue

### 💡 Feature Requests

We're always looking to improve! Submit feature requests via GitHub Issues with the "enhancement" label.

---

**Made with ❤️ for the SMF community**

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
