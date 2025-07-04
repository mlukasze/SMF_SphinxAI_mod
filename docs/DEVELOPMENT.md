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

## Development Setup

### Prerequisites
- Git
- PHP 7.4+ with development extensions
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
│   │   ├── openvino_handler.py
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

Follow PSR-12 coding standards:

```php
<?php
/**
 * Class documentation
 * 
 * @package SphinxAISearch
 * @subpackage Controllers
 */

declare(strict_types=1);

class SphinxAIExampleController
{
    /**
     * Method documentation
     * 
     * @param string $param Parameter description
     * @return array Return value description
     */
    public function exampleMethod(string $param): array
    {
        // Implementation
        return [];
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

### PHP Testing

```bash
# Install PHPUnit
composer require --dev phpunit/phpunit

# Run tests
./vendor/bin/phpunit tests/
```

Example test:

```php
<?php

use PHPUnit\Framework\TestCase;

class SphinxAISearchServiceTest extends TestCase
{
    public function testSearchValidQuery(): void
    {
        $service = new SphinxAISearchService();
        $results = $service->search('test query');
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('results', $results);
    }
}
```

### Python Testing

```bash
# Run all tests
python -m pytest SphinxAI/tests/

# Run with coverage
python -m pytest --cov=SphinxAI SphinxAI/tests/

# Run specific test
python -m pytest SphinxAI/tests/test_search.py::TestSearch::test_basic_search
```

Example test:

```python
import pytest
from SphinxAI.core.search_coordinator import SearchCoordinator


class TestSearchCoordinator:
    """Test the SearchCoordinator class."""
    
    def test_basic_search(self):
        """Test basic search functionality."""
        coordinator = SearchCoordinator()
        results = coordinator.search("test query")
        
        assert isinstance(results, list)
        assert len(results) >= 0
        
    def test_invalid_query(self):
        """Test handling of invalid queries."""
        coordinator = SearchCoordinator()
        
        with pytest.raises(ValueError):
            coordinator.search("")
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

---

For more information, check the [API Documentation](API.md) and [Contributing Guidelines](CONTRIBUTING.md).
