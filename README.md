# Sphinx AI Search for SMF

[![Tests](https://github.com/mlukasze/smf-sphinx-ai-search/actions/workflows/tests.yml/badge.svg)](https://github.com/mlukasze/smf-sphinx-ai-search/actions/workflows/tests.yml)
[![Coverage](https://codecov.io/gh/mlukasze/smf-sphinx-ai-search/branch/main/graph/badge.svg)](https://codecov.io/gh/mlukasze/smf-sphinx-ai-search)
[![Python](https://img.shields.io/badge/python-3.7%2B-blue.svg)](https://www.python.org/downloads/)
[![PHP](https://img.shields.io/badge/php-8.1%2B-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![SMF](https://img.shields.io/badge/SMF-2.1%2B-orange.svg)](https://www.simplemachines.org/)

[![Redis](https://img.shields.io/badge/redis-6.0%2B-red.svg)](https://redis.io/)
[![Sphinx](https://img.shields.io/badge/sphinx-3.0%2B-purple.svg)](https://sphinxsearch.com/)
[![OpenVINO](https://img.shields.io/badge/openvino-2023.0%2B-blue.svg)](https://openvino.ai/)
[![Transformers](https://img.shields.io/badge/transformers-4.21%2B-yellow.svg)](https://huggingface.co/transformers/)

> **⚠️ ALPHA SOFTWARE WARNING ⚠️**
> 
> **This project is currently in ALPHA stage and is NOT ready for production use.**
> 
> Please be aware that:
> - This software is **experimental** and **under active development**
> - It **has not been thoroughly tested** in production environments
> - **Data loss, system instability, or security vulnerabilities** may occur
> - **Breaking changes** may be introduced without notice
> - **No production support** is currently available
> 
> **DO NOT USE THIS SOFTWARE ON PRODUCTION SYSTEMS**
> 
> This software is intended for:
> - ✅ **Development and testing environments only**
> - ✅ **Experimental installations**
> - ✅ **Community feedback and contribution**
> 
> By using this software, you acknowledge and accept full responsibility for any consequences that may arise. The developers assume no liability for any damages or issues that may occur.
> 
> **Please wait for a stable release before considering production deployment.**

A powerful AI-enhanced search plugin for Simple Machines Forum (SMF) that combines Sphinx indexing with OpenVINO-optimized language models to provide intelligent search results with summaries and source linking.

## Table of Contents

- [🚀 Modern PHP Architecture](#-modern-php-architecture)
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
- **PHP**: 8.1+ (8.2+ recommended) - Leverages modern PHP features including enums, union types, constructor property promotion, and readonly properties
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

- **Architecture**: Modular design with modern PHP 8.1+ controllers and Python AI services
- **Modern PHP Features**: Enums, union types, constructor property promotion, readonly properties, match expressions
- **Security**: CSRF protection, SQL injection prevention, input validation
- **Performance**: Redis caching, database optimization, model compression
- **Compatibility**: SMF 2.1+, PHP 8.1+, Python 3.8+

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

## 🚀 Modern PHP Architecture

This plugin leverages cutting-edge **PHP 8.1+ features** for enterprise-grade performance and maintainability:

### Type Safety & Performance
- **🔒 Enums**: Type-safe constants for search types, cache keys, and configuration values
- **⚡ Constructor Property Promotion**: Reduced boilerplate and memory footprint
- **🛡️ Readonly Properties**: Immutable configuration and dependency injection
- **🔄 Union Types**: Flexible APIs with compile-time type checking
- **🎯 Match Expressions**: Cleaner control flow with better type inference

### Code Quality Features  
- **🔍 Nullsafe Operator**: Safe navigation through optional dependencies
- **📝 Named Arguments**: Self-documenting function calls in service factories
- **🏗️ Attributes**: Metadata-driven configuration for routes and caching
- **🔧 Strict Types**: Full strict typing throughout the codebase

### Example: Modern Search Service
```php
enum SearchType: string {
    case SEMANTIC = 'semantic';
    case EXACT = 'exact';
    case FUZZY = 'fuzzy';
}

class SphinxAISearchService {
    public function __construct(
        private readonly SphinxAIConfig $config,
        private readonly LoggerInterface $logger,
        private readonly SphinxAICache $cache,
    ) {}

    public function search(string $query, SearchType $type): SearchResult|null {
        $strategy = match($type) {
            SearchType::SEMANTIC => new SemanticStrategy(),
            SearchType::EXACT => new ExactStrategy(), 
            SearchType::FUZZY => new FuzzyStrategy(),
        };

        return $this->cache?->get($query) ?? $strategy->execute($query);
    }
}
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

## Configuration

### Plugin Settings
- **Model Path**: Path to OpenVINO model file (.xml)
- **Max Results**: Maximum search results to return (1-100)
- **Summary Length**: Maximum summary length in characters (50-500)
- **Auto Indexing**: Automatically index new posts

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
# or check your installation guide for specific commands
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
- **Dependencies missing**: Re-run the installation script or check the Installation Guide
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
├── core/                   # Core functionality
│   ├── constants.py        # Core constants and configuration
│   ├── interfaces.py       # Abstract base classes and interfaces
│   └── search_coordinator.py # Main search coordination
├── handlers/               # AI model handlers
│   ├── genai_handler.py    # OpenVINO GenAI model handling
│   └── sphinx_handler.py   # Sphinx daemon integration
├── utils/                  # Utility modules
│   ├── cache.py           # Redis caching implementation
│   ├── config_manager.py  # Configuration management
│   ├── model_utils.py     # Model utilities
│   └── text_processing.py # Text processing and normalization
├── main.py                # Main entry point
├── requirements.txt       # Python dependencies
├── setup.py              # Installation script
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
