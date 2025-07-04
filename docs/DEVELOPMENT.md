# 🔧 Development Guide

Guide for developers who want to contribute to or extend the SMF Sphinx AI Search Plugin.

## Table of Contents

- [Development Setup](#development-setup)
- [Project Structure](#project-structure)
- [Code Standards](#code-standards)
- [Testing](#testing)
- [Contributing](#contributing)
- [API Development](#api-development)
- [Extension Development](#extension-development)
- [Modern PHP 8.1+ Features](#modern-php-81-features)

## Development Setup

### Prerequisites
- Git
- PHP 8.1+ with development extensions (uses modern PHP features: enums, union types, constructor property promotion, readonly properties, match expressions)
- Python 3.8+ with pip
- MySQL/MariaDB
- Redis
- Node.js (for frontend development)

### Clone and Setup

```bash
# Clone the repository
git clone https://github.com/mlukasze/SMF_SphinxAI_mod.git
cd SMF_SphinxAI_mod

# Create Python virtual environment
python -m venv dev-env
source dev-env/bin/activate  # Linux/macOS
# or
dev-env\Scripts\activate     # Windows

# Install development dependencies
pip install -r SphinxAI/requirements.txt
pip install -r SphinxAI/requirements-dev.txt

# Install pre-commit hooks
pre-commit install
```

### Development Environment

```bash
# Set environment variables
export SPHINX_AI_DEBUG=true
export SPHINX_AI_LOG_LEVEL=DEBUG
export SPHINX_AI_MODEL_PATH=./SphinxAI/models

# Run tests
python -m pytest SphinxAI/tests/

# Start development server (if applicable)
python SphinxAI/main.py --dev-mode
```

## Project Structure

```
SMF_SphinxAI_mod/
├── php/                          # PHP components
│   ├── controllers/              # MVC controllers
│   │   ├── SphinxAIAdminController.php
│   │   ├── SphinxAIApiController.php
│   │   └── SphinxAISearchController.php
│   ├── core/                     # Core functionality
│   │   ├── SphinxAIHookManager.php
│   │   └── SphinxAISearchService.php
│   ├── handlers/                 # Event and data handlers
│   ├── services/                 # Business logic services
│   └── utils/                    # Utility classes
├── SphinxAI/                     # Python AI components
│   ├── core/                     # Core functionality
│   │   ├── __init__.py
│   │   ├── constants.py
│   │   ├── interfaces.py
│   │   └── search_coordinator.py
│   ├── handlers/                 # AI model handlers
│   │   ├── __init__.py
│   │   ├── genai_handler.py
│   │   ├── genai_handler.py
│   │   └── sphinx_handler.py
│   ├── utils/                    # Utility modules
│   │   ├── __init__.py
│   │   ├── cache.py
│   │   ├── model_utils.py
│   │   └── text_processing.py
│   ├── tests/                    # Test suites
│   ├── main.py                   # Main entry point
│   └── requirements*.txt         # Dependencies
├── docs/                         # Documentation
├── install.*                     # Installation scripts
└── README.md                     # Main documentation
```

## Code Standards

### PHP Standards

Follow PSR-12 coding standards with modern PHP 8.1+ features:

```php
<?php
/**
 * Class documentation
 * 
 * @package SphinxAISearch
 * @subpackage Controllers
 */

declare(strict_types=1);

// Use enums for type safety
enum SearchType: string 
{
    case SEMANTIC = 'semantic';
    case EXACT = 'exact';
    case FUZZY = 'fuzzy';
}

class SphinxAIExampleController
{
    // Constructor property promotion with readonly properties
    public function __construct(
        private readonly SphinxAIConfig $config,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Method documentation with union types
     * 
     * @param string $query The search query
     * @param SearchType $type The search type
     * @return array|null Return value description
     */
    public function search(string $query, SearchType $type): array|null
    {
        // Use match expressions instead of switch
        $strategy = match($type) {
            SearchType::SEMANTIC => new SemanticSearchStrategy(),
            SearchType::EXACT => new ExactSearchStrategy(),
            SearchType::FUZZY => new FuzzySearchStrategy(),
        };

        // Use nullsafe operator
        return $this->searchService?->execute($query, $strategy);
    }
}
```

### Python Standards

Follow PEP 8 with these specifics:

```python
"""Module documentation."""

from typing import Dict, List, Optional
import logging

logger = logging.getLogger(__name__)


class ExampleClass:
    """Class documentation."""
    
    def __init__(self, config: Dict[str, str]) -> None:
        """Initialize the class."""
        self.config = config
        
    def example_method(self, param: str) -> Optional[List[str]]:
        """Method documentation.
        
        Args:
            param: Parameter description
            
        Returns:
            Return value description
            
        Raises:
            ValueError: When param is invalid
        """
        if not param:
            raise ValueError("Parameter cannot be empty")
            
        return [param.lower()]
```

### Code Quality Tools

```bash
# PHP
composer install
./vendor/bin/phpcs --standard=PSR12 php/
./vendor/bin/phpstan analyse php/

# Python
black SphinxAI/
flake8 SphinxAI/
mypy SphinxAI/
pylint SphinxAI/
```

## Testing

The SMF Sphinx AI Search Plugin includes a comprehensive test suite for both PHP and Python components, designed to ensure code quality and reliability without requiring a full production environment.

### Test Infrastructure Overview

The test infrastructure is located in the `tests/` directory with the following structure:

```
tests/
├── php/                    # PHP unit tests
│   ├── Unit/              # Unit test classes
│   │   └── SphinxAICacheTest.php
│   ├── bootstrap.php      # Test bootstrap and autoloading
│   ├── TestCase.php       # Base test class
│   └── MockFactory.php    # Factory for test mocks
├── python/                # Python unit tests
│   ├── test_cache.py      # Cache module tests
│   ├── test_config_manager.py # Config manager tests
│   ├── test_integration.py   # Integration tests
│   ├── test_utils.py      # Utility and import tests
│   └── conftest.py        # pytest fixtures
├── fixtures/              # Test data and sample files
├── composer.json          # PHP test dependencies
├── requirements_test.txt  # Python test dependencies
├── phpunit.xml           # PHPUnit configuration
├── pyproject.toml        # pytest configuration
└── run_tests.py          # Test runner script
```

### Quick Start

#### Python Tests (Recommended)

```bash
# Navigate to test directory
cd tests/

# Run all Python tests with the test runner
python run_tests.py

# Or run pytest directly
python -m pytest python/ -v

# Run specific test class
python -m pytest python/test_cache.py::TestSphinxAICache -v

# Run with coverage
python -m pytest python/ --cov=../SphinxAI --cov-report=html
```

#### PHP Tests

```bash
# Install PHP dependencies (requires Composer)
cd tests/
composer install

# Run PHPUnit tests
./vendor/bin/phpunit

# Run with coverage (requires xdebug)
./vendor/bin/phpunit --coverage-html coverage/
```

### Python Testing

#### Dependencies

The Python test suite uses pytest with comprehensive testing libraries:

```bash
# Install test dependencies
pip install -r tests/requirements_test.txt
```

Core testing libraries included:
- **pytest**: Test framework
- **pytest-cov**: Coverage reporting
- **pytest-mock**: Enhanced mocking
- **fakeredis**: Redis mocking for cache tests
- **responses**: HTTP request mocking
- **freezegun**: Time/date mocking
- **factory-boy**: Test data generation

#### Running Python Tests

```bash
# Run all tests
python -m pytest tests/python/

# Run with verbose output
python -m pytest tests/python/ -v

# Run specific test file
python -m pytest tests/python/test_cache.py

# Run specific test method
python -m pytest tests/python/test_cache.py::TestSphinxAICache::test_cache_search_results_success

# Run tests with markers
python -m pytest tests/python/ -m "not slow"  # Skip slow tests
python -m pytest tests/python/ -m "integration"  # Run only integration tests
python -m pytest tests/python/ -m "redis"  # Run only Redis tests (if Redis available)

# Run with coverage
python -m pytest tests/python/ --cov=SphinxAI --cov-report=term-missing
python -m pytest tests/python/ --cov=SphinxAI --cov-report=html:coverage/

# Stop on first failure
python -m pytest tests/python/ -x

# Run tests in parallel (if pytest-xdist installed)
python -m pytest tests/python/ -n auto
```

#### Test Categories and Markers

Tests are organized with pytest markers for selective execution:

- `@pytest.mark.unit`: Unit tests (default)
- `@pytest.mark.integration`: Integration tests
- `@pytest.mark.slow`: Performance/slow tests
- `@pytest.mark.redis`: Tests requiring Redis connection
- `@pytest.mark.network`: Tests requiring network access

#### Python Test Examples

**Basic Unit Test:**
```python
def test_cache_initialization():
    """Test cache initializes correctly."""
    cache = SphinxAICache()
    assert cache is not None
    assert hasattr(cache, 'config')
```

**Mock-based Test:**
```python
@patch('SphinxAI.utils.cache.redis_available', True)
def test_redis_connection_success(self):
    """Test successful Redis connection."""
    mock_redis = MagicMock()
    with patch('SphinxAI.utils.cache.redis', mock_redis):
        cache = SphinxAICache()
        # Test assertions...
```

**Integration Test:**
```python
@pytest.mark.integration
def test_cache_and_config_integration(self, temp_config_file):
    """Test cache and config working together."""
    cache = SphinxAICache(temp_config_file)
    assert cache.config is not None
```

### PHP Testing

#### Dependencies

The PHP test suite uses PHPUnit with modern testing tools:

```bash
# Install PHP dependencies
cd tests/
composer install
```

Key testing libraries:
- **PHPUnit**: Test framework
- **Mockery**: Advanced mocking library
- **PHPStan**: Static analysis
- **PHPCS**: Code style checking
- **PHPMD**: Mess detection
- **Infection**: Mutation testing

#### Running PHP Tests

```bash
# Navigate to tests directory
cd tests/

# Run all tests
./vendor/bin/phpunit

# Run with verbose output
./vendor/bin/phpunit --verbose

# Run specific test class
./vendor/bin/phpunit php/Unit/SphinxAICacheTest.php

# Run with coverage (requires Xdebug)
./vendor/bin/phpunit --coverage-html coverage/
./vendor/bin/phpunit --coverage-text

# Run static analysis
./vendor/bin/phpstan analyse ../php/

# Check code style
./vendor/bin/phpcs ../php/

# Run mutation testing
./vendor/bin/infection
```

#### PHP Test Examples

**Basic Unit Test:**
```php
<?php

use PHPUnit\Framework\TestCase;
use SMF\SphinxAI\Tests\MockFactory;

class SphinxAICacheTest extends TestCase
{
    public function testCacheInitialization(): void
    {
        $cache = new SphinxAICache();
        $this->assertInstanceOf(SphinxAICache::class, $cache);
        $this->assertTrue($cache->isEnabled());
    }
    
    public function testCacheStoreAndRetrieve(): void
    {
        $cache = new SphinxAICache();
        $key = 'test_key';
        $value = ['test' => 'data'];
        
        $result = $cache->set($key, $value, 3600);
        $this->assertTrue($result);
        
        $retrieved = $cache->get($key);
        $this->assertEquals($value, $retrieved);
    }
}
```

### Mock Testing Strategy

Both PHP and Python tests use extensive mocking to avoid external dependencies:

#### Python Mocking Examples

```python
# Mock Redis connection
@patch('SphinxAI.utils.cache.redis_available', False)
def test_cache_without_redis():
    cache = SphinxAICache()
    assert not cache.is_available()

# Mock configuration
@patch('SphinxAI.utils.cache.ConfigManager')
def test_cache_with_mock_config(mock_config_manager):
    mock_config_manager.return_value.get_cache_config.return_value = {
        'enabled': True,
        'type': 'redis'
    }
    cache = SphinxAICache()
    # Test with mocked config...
```

#### PHP Mocking Examples

```php
public function testCacheWithMockRedis(): void
{
    $mockRedis = Mockery::mock('Redis');
    $mockRedis->shouldReceive('set')->andReturn(true);
    $mockRedis->shouldReceive('get')->andReturn('cached_value');
    
    $cache = new SphinxAICache($mockRedis);
    $result = $cache->set('key', 'value');
    $this->assertTrue($result);
}
```

### Test Configuration

#### pytest Configuration (pyproject.toml)

```toml
[tool.pytest.ini_options]
minversion = "7.0"
addopts = [
    "--strict-markers",
    "--cov=../SphinxAI",
    "--cov-report=html:coverage/html",
    "--cov-report=term-missing",
    "--cov-fail-under=80",
    "-ra",
    "--tb=short"
]
testpaths = ["python"]
markers = [
    "unit: Unit tests",
    "integration: Integration tests", 
    "slow: Slow tests",
    "redis: Redis-dependent tests",
    "network: Network-dependent tests"
]
```

#### PHPUnit Configuration (phpunit.xml)

```xml
<phpunit bootstrap="php/bootstrap.php">
    <testsuites>
        <testsuite name="unit">
            <directory>php/Unit</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">../php</directory>
        </include>
    </coverage>
</phpunit>
```

### Coverage Goals

- **Target Coverage**: 80%+ for both PHP and Python
- **Critical Paths**: 95%+ coverage for cache, config, and core functionality
- **Focus Areas**: Error handling, edge cases, security validation

### Test Data and Fixtures

#### Python Fixtures (conftest.py)

```python
@pytest.fixture
def temp_config_file():
    """Create temporary config file for testing."""
    config_content = """
[database]
host = localhost
user = test_user
password = test_pass

[cache]
enabled = true
type = redis
host = localhost
port = 6379
"""
    with tempfile.NamedTemporaryFile(mode='w', suffix='.ini', delete=False) as f:
        f.write(config_content)
        f.flush()
        yield f.name
        os.unlink(f.name)
```

### Continuous Integration

The test suite is designed for CI/CD integration:

```yaml
# Example GitHub Actions workflow
name: Tests
on: [push, pull_request]
jobs:
  python-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-python@v4
        with:
          python-version: '3.8'
      - run: pip install -r tests/requirements_test.txt
      - run: python -m pytest tests/python/ --cov=SphinxAI
      
  php-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - run: cd tests && composer install
      - run: cd tests && ./vendor/bin/phpunit
```

### Debugging Tests

#### Python Test Debugging

```bash
# Run with debugging output
python -m pytest tests/python/ -v -s

# Use pdb for debugging
python -m pytest tests/python/ --pdb

# Run single test with print statements
python -m pytest tests/python/test_cache.py::test_specific_method -v -s
```

#### PHP Test Debugging

```bash
# Run with verbose output
./vendor/bin/phpunit --verbose

# Debug specific test
./vendor/bin/phpunit --verbose php/Unit/SphinxAICacheTest.php::testSpecificMethod
```

### Best Practices

1. **Write tests first** (TDD) when adding new features
2. **Use descriptive test names** that explain what is being tested
3. **Mock external dependencies** to ensure test isolation
4. **Test both success and failure paths**
5. **Include edge cases and boundary conditions**
6. **Keep tests fast and independent**
7. **Use fixtures for common test data**
8. **Maintain high test coverage** but focus on critical paths

### Performance Testing

```python
# Example performance test
@pytest.mark.slow
def test_cache_performance_with_large_data():
    """Test cache performance with large datasets."""
    cache = SphinxAICache()
    large_results = [{'id': i, 'data': f'content_{i}'} for i in range(10000)]
    
    start_time = time.time()
    cache.cache_search_results("perf_test", {}, large_results)
    duration = time.time() - start_time
    
    assert duration < 5.0  # Should complete within 5 seconds
```

## Contributing

### Workflow

1. **Fork the Repository**
2. **Create Feature Branch**
   ```bash
   git checkout -b feature/new-feature
   ```
3. **Make Changes**
4. **Add Tests**
5. **Run Quality Checks**
   ```bash
   # PHP
   ./vendor/bin/phpcs --standard=PSR12 php/
   ./vendor/bin/phpunit tests/
   
   # Python
   black SphinxAI/
   flake8 SphinxAI/
   python -m pytest SphinxAI/tests/
   ```
6. **Commit Changes**
   ```bash
   git commit -m "feat: add new search feature"
   ```
7. **Push and Create PR**

### Commit Message Format

```
type(scope): description

[optional body]

[optional footer]
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Adding tests
- `chore`: Maintenance tasks

## API Development

### Adding New Endpoints

1. **Create Controller Method**

```php
// php/controllers/SphinxAIApiController.php

public function handleNewEndpoint(): void
{
    $this->validateRequest();
    
    $data = $this->getRequestData();
    $result = $this->processNewFeature($data);
    
    $this->sendJsonResponse($result);
}
```

2. **Register Route**

```php
// SphinxAISearch.php

$actions['sphinx-ai-new-endpoint'] = ['php/controllers/SphinxAIApiController.php', 'SphinxAIApiController::handleNewEndpoint'];
```

3. **Add Python Handler**

```python
# SphinxAI/handlers/new_handler.py

class NewHandler:
    """Handler for new feature."""
    
    def process(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """Process the new feature request."""
        # Implementation
        return {"status": "success", "data": processed_data}
```

### API Documentation

Document APIs using OpenAPI/Swagger format:

```yaml
# docs/api.yaml
openapi: 3.0.0
info:
  title: Sphinx AI Search API
  version: 1.0.0
paths:
  /sphinx-ai/search:
    post:
      summary: Perform AI search
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                query:
                  type: string
                  description: Search query
      responses:
        200:
          description: Search results
          content:
            application/json:
              schema:
                type: object
                properties:
                  results:
                    type: array
```

## Extension Development

### Creating Custom Handlers

```python
# SphinxAI/handlers/custom_handler.py

from .base_handler import BaseHandler
from typing import Dict, Any, List

class CustomHandler(BaseHandler):
    """Custom handler for specific functionality."""
    
    def __init__(self, config: Dict[str, Any]):
        super().__init__(config)
        self.custom_setting = config.get('custom_setting', 'default')
    
    def process(self, input_data: str) -> List[Dict[str, Any]]:
        """Process input and return results."""
        # Custom implementation
        results = []
        
        # Add processing logic here
        
        return results
    
    def validate_input(self, input_data: str) -> bool:
        """Validate input data."""
        return isinstance(input_data, str) and len(input_data) > 0
```

### Adding Custom Models

```python
# SphinxAI/models/custom_model.py

from sentence_transformers import SentenceTransformer
from typing import List, Any

class CustomEmbeddingModel:
    """Custom embedding model wrapper."""
    
    def __init__(self, model_path: str):
        self.model = SentenceTransformer(model_path)
        self.model_name = "custom-model"
    
    def encode(self, texts: List[str]) -> List[List[float]]:
        """Encode texts to embeddings."""
        return self.model.encode(texts).tolist()
    
    def get_embedding_dim(self) -> int:
        """Get embedding dimension."""
        return self.model.get_sentence_embedding_dimension()
```

### Plugin Hooks

Add custom hooks for extensibility:

```php
// In your custom plugin

function custom_sphinx_ai_hook($search_results, $query) {
    // Modify search results
    foreach ($search_results as &$result) {
        $result['custom_score'] = calculateCustomScore($result, $query);
    }
    
    return $search_results;
}

// Register the hook
add_integration_function('integrate_sphinx_ai_search_results', 'custom_sphinx_ai_hook');
```

### Development Tools

#### Debug Mode

```php
// Enable debug mode in SMF settings
$modSettings['sphinx_ai_debug'] = true;

// This enables:
// - Detailed logging
// - Performance profiling  
// - Debug information in responses
// - Development endpoints
```

#### Profiling

```python
# SphinxAI/utils/profiler.py

import cProfile
import pstats
from functools import wraps

def profile_function(func):
    """Decorator to profile function performance."""
    @wraps(func)
    def wrapper(*args, **kwargs):
        pr = cProfile.Profile()
        pr.enable()
        result = func(*args, **kwargs)
        pr.disable()
        
        stats = pstats.Stats(pr)
        stats.sort_stats('cumulative')
        stats.print_stats(10)  # Top 10 functions
        
        return result
    return wrapper
```

#### Logging

```python
# Configure detailed logging for development

import logging

logging.basicConfig(
    level=logging.DEBUG,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('logs/debug.log'),
        logging.StreamHandler()
    ]
)

logger = logging.getLogger(__name__)
```

## Modern PHP 8.1+ Features

This project leverages modern PHP 8.1+ features for improved code quality, type safety, and performance:

### Enums (PHP 8.1+)
Used for type-safe constants and configuration values:

```php
// SphinxAIEnums.php
enum CacheKeyType: string 
{
    case SEARCH_RESULTS = 'search_results';
    case USER_QUERIES = 'user_queries';
    case MODEL_CACHE = 'model_cache';
}

enum SearchType: string 
{
    case SEMANTIC = 'semantic';
    case EXACT = 'exact';
    case FUZZY = 'fuzzy';
}

enum LogLevel: string 
{
    case DEBUG = 'debug';
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
}
```

### Constructor Property Promotion (PHP 8.0+)
Reduces boilerplate code in constructors:

```php
class SphinxAICache
{
    public function __construct(
        private readonly Redis $redis,
        private readonly int $ttl = 3600,
        private readonly string $prefix = 'sphinxai:',
    ) {}
}
```

### Readonly Properties (PHP 8.1+)
Ensures immutability of important configuration:

```php
class SphinxAIConfig
{
    public function __construct(
        private readonly array $config,
        private readonly string $configPath,
    ) {}
}
```

### Union Types (PHP 8.0+)
More flexible type declarations:

```php
public function search(string $query): array|null
{
    return $this->executeSearch($query);
}

public function getCacheData(string $key): string|array|null
{
    return $this->redis->get($key);
}
```

### Match Expressions (PHP 8.0+)
Cleaner control flow than switch statements:

```php
$cacheKey = match($type) {
    CacheKeyType::SEARCH_RESULTS => "search:{$query}",
    CacheKeyType::USER_QUERIES => "user:{$userId}",
    CacheKeyType::MODEL_CACHE => "model:{$modelId}",
};

$logLevel = match($this->config->getLogLevel()) {
    LogLevel::DEBUG => Logger::DEBUG,
    LogLevel::INFO => Logger::INFO,
    LogLevel::WARNING => Logger::WARNING,
    LogLevel::ERROR => Logger::ERROR,
};
```

### Nullsafe Operator (PHP 8.0+)
Safe navigation through potentially null objects:

```php
$result = $this->searchService?->search($query)?->getResults();
$config = $this->configManager?->getConfig()?->getValue('timeout');
```

### Named Arguments (PHP 8.0+)
Improved function calls with clear parameter names:

```php
$searchService = SphinxAISearchService::createWithConfig(
    config: $config,
    logger: $logger,
    cache: $cache,
    rateLimit: true,
    maxResults: 50
);
```

### Attributes (PHP 8.0+)
Metadata for classes and methods:

```php
#[Route('/api/search')]
#[RequiresAuth]
class SphinxAIApiController
{
    #[Cache(ttl: 300)]
    #[RateLimit(requests: 10, window: 60)]
    public function search(string $query): JsonResponse
    {
        // Implementation
    }
}
```

### Best Practices

1. **Use enums** instead of class constants for related values
2. **Leverage constructor property promotion** to reduce boilerplate
3. **Mark properties as readonly** when they shouldn't change after construction
4. **Use union types** for flexible APIs while maintaining type safety
5. **Prefer match expressions** over switch statements for better type inference
6. **Use the nullsafe operator** to avoid verbose null checks
7. **Use named arguments** for complex function calls to improve readability
