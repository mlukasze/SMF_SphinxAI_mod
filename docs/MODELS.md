# 🤖 Model Management Guide

This guide covers downloading, converting, optimizing, and managing AI models for the SMF Sphinx AI Search Plugin.

## Table of Contents

- [Overview](#overview)
- [Model Types](#model-types)
- [Model Directory Structure](#model-directory-structure)
- [Downloading Models](#downloading-models)
- [Model Conversion](#model-conversion)
- [Model Optimization](#model-optimization)
- [Custom Models](#custom-models)
- [Model Management](#model-management)
- [Troubleshooting](#troubleshooting)
- [PHP Model Management Interface](#php-model-management-interface)

## Overview

The plugin uses AI models for semantic search and text processing. Models are automatically downloaded and converted to OpenVINO format for optimal performance.

### Supported Model Formats
- **Hugging Face Models**: Original PyTorch/TensorFlow models
- **OpenVINO IR**: Optimized for CPU inference
- **OpenVINO GenAI**: Optimized for LLM inference
- **Compressed Models**: Quantized (INT8/INT4) for reduced memory usage

## Model Types

### Embedding Models
Used for semantic search and text similarity:

| Model | Size | Language | Use Case |
|-------|------|----------|----------|
| `all-MiniLM-L6-v2` | 80MB | Multilingual | General purpose, fast |
| `all-mpnet-base-v2` | 420MB | English | High quality embeddings |
| `paraphrase-multilingual-MiniLM-L12-v2` | 420MB | Multilingual | Paraphrase detection |
| `distiluse-base-multilingual-cased` | 480MB | Multilingual | Distilled model, fast |

### LLM Models (Optional)
Used for text summarization and generation:

| Model | Size | Language | Use Case |
|-------|------|----------|----------|
| `microsoft/DialoGPT-medium` | 350MB | English | Conversational AI |
| `facebook/blenderbot-400M-distill` | 400MB | English | Dialog generation |
| `google/flan-t5-small` | 240MB | Multilingual | Text-to-text generation |

## Model Directory Structure

All models are stored in the consolidated `SphinxAI/models/` directory:

```
SphinxAI/models/
├── original/                    # Original Hugging Face models
│   ├── all-MiniLM-L6-v2/
│   ├── all-mpnet-base-v2/
│   └── ...
├── openvino/                    # OpenVINO IR format (embeddings)
│   ├── all-MiniLM-L6-v2/
│   │   ├── openvino_model.xml
│   │   ├── openvino_model.bin
│   │   └── tokenizer.json
│   └── ...
├── genai/                       # OpenVINO GenAI format (LLMs)
│   ├── DialoGPT-medium/
│   └── ...
└── compressed/                  # Quantized models
    ├── all-MiniLM-L6-v2-int8/
    └── ...
```

## Downloading Models

### Automated Download
The installation script automatically downloads default models:

```bash
# Download all default models
python SphinxAI/unified_model_converter.py --download-all

# Download specific model
python SphinxAI/unified_model_converter.py --download all-MiniLM-L6-v2
```

### Manual Download
You can also download models manually using Hugging Face Hub:

```bash
# Install huggingface-hub
pip install huggingface-hub

# Download model
python -c "
from huggingface_hub import snapshot_download
snapshot_download('sentence-transformers/all-MiniLM-L6-v2', 
                  local_dir='SphinxAI/models/original/all-MiniLM-L6-v2')
"
```

### Hugging Face Authentication

Some models require authentication:

1. **Create Token**
   - Go to https://huggingface.co/settings/tokens
   - Create a new token (read access sufficient)

2. **Configure Authentication**
   
   **Option 1: Environment Variable**
   ```bash
   export HUGGINGFACE_HUB_TOKEN="your_token_here"
   ```
   
   **Option 2: Configuration File**
   Create `SphinxAI/config.ini`:
   ```ini
   [huggingface]
   token = your_token_here
   ```
   
   **Option 3: CLI Login**
   ```bash
   huggingface-cli login
   ```

## Model Conversion

### Embedding Models to OpenVINO IR

Convert embedding models for efficient CPU inference:

```bash
cd SphinxAI

# Convert specific model
python unified_model_converter.py --convert-embedding all-MiniLM-L6-v2

# Convert all embedding models
python unified_model_converter.py --convert-all-embeddings

# Force reconversion
python unified_model_converter.py --convert-embedding all-MiniLM-L6-v2 --force
```

### LLM Models to OpenVINO GenAI

Convert LLM models for text generation:

```bash
# Convert specific LLM
python unified_model_converter.py --convert-llm DialoGPT-medium

# Convert all LLMs
python unified_model_converter.py --convert-all-llms
```

### Conversion Process

The conversion process:
1. **Downloads** the original model from Hugging Face
2. **Converts** to OpenVINO format using `optimum-cli`
3. **Validates** the converted model works correctly
4. **Optimizes** for the target hardware (CPU)

## Model Optimization

### Quantization

Reduce model size and improve inference speed:

```bash
# Enable quantization in conversion
python unified_model_converter.py --convert-embedding all-MiniLM-L6-v2 --quantize int8

# Available quantization levels
# - int8: 2x size reduction, minimal accuracy loss
# - int4: 4x size reduction, some accuracy loss
```

### Compression

Use NNCF (Neural Network Compression Framework) for advanced optimization:

```bash
# Install NNCF
pip install nncf>=2.9.0

# Compress model with NNCF
python unified_model_converter.py --compress all-MiniLM-L6-v2
```

### Performance Tuning

Optimize models for your hardware:

```bash
# Benchmark models
python SphinxAI/main.py benchmark-models

# Test inference speed
python -c "
from SphinxAI.handlers.openvino_handler import OpenVINOHandler
handler = OpenVINOHandler('SphinxAI/models/openvino/all-MiniLM-L6-v2')
result = handler.encode(['test sentence'])
print(f'Inference successful: {len(result[0])} dimensions')
"
```

## Custom Models

### Adding Custom Models

1. **Place Model Files**
   ```bash
   mkdir -p SphinxAI/models/original/my-custom-model
   # Copy your model files to this directory
   ```

2. **Configure Model**
   Edit `SphinxAI/unified_model_converter.py`:
   ```python
   self.embedding_models = {
       # Existing models...
       'my-custom-model': {
           'hf_model': 'path/to/huggingface/model',
           'description': 'My custom embedding model'
       }
   }
   ```

3. **Convert Model**
   ```bash
   python unified_model_converter.py --convert-embedding my-custom-model
   ```

### Model Requirements

Custom models must:
- Be compatible with `sentence-transformers` library
- Output fixed-size embeddings
- Support the same tokenizer format
- Have appropriate licensing for your use case

### Model Validation

Validate custom models:

```bash
# Test model loading
python -c "
from sentence_transformers import SentenceTransformer
model = SentenceTransformer('SphinxAI/models/original/my-custom-model')
embeddings = model.encode(['test sentence'])
print(f'Model loaded successfully, embedding size: {embeddings.shape}')
"
```

## Model Management

### Listing Models

```bash
# List all available models
python SphinxAI/main.py list-models

# Check model status
python unified_model_converter.py --status
```

### Model Updates

```bash
# Update specific model
python unified_model_converter.py --update all-MiniLM-L6-v2

# Update all models
python unified_model_converter.py --update-all
```

### Storage Management

```bash
# Check model disk usage
du -sh SphinxAI/models/*

# Clean up old model versions
python unified_model_converter.py --cleanup

# Remove specific model
rm -rf SphinxAI/models/original/model-name
rm -rf SphinxAI/models/openvino/model-name
```

### Model Configuration

Configure active models in SMF Admin:

1. **Go to Admin Panel**
   - SMF Admin > Configuration > Sphinx AI Search

2. **Model Settings**
   - Set primary embedding model
   - Configure model paths
   - Adjust inference parameters

3. **Test Configuration**
   - Use built-in model testing tools
   - Verify search functionality

## Troubleshooting

### Common Issues

#### Model Download Failures
```bash
# Clear Hugging Face cache
rm -rf ~/.cache/huggingface/

# Retry with verbose output
python unified_model_converter.py --download all-MiniLM-L6-v2 --verbose

# Check authentication
huggingface-cli whoami
```

#### Conversion Errors
```bash
# Install optimum with OpenVINO
pip install optimum[openvino]>=1.14.0

# Check OpenVINO installation
python -c "import openvino; print(openvino.__version__)"

# Retry conversion with debug
python unified_model_converter.py --convert-embedding model-name --debug
```

#### Memory Issues
```bash
# Use smaller models
python unified_model_converter.py --download all-MiniLM-L6-v2  # 80MB

# Enable model compression
python unified_model_converter.py --convert-embedding model-name --quantize int8

# Monitor memory usage
python -c "
import psutil
print(f'Available RAM: {psutil.virtual_memory().available / 1024**3:.1f} GB')
"
```

#### Permission Issues
```bash
# Fix model directory permissions
chmod -R 755 SphinxAI/models/
chown -R www-data:www-data SphinxAI/models/  # Linux
```

### Model Validation

Test model functionality:

```bash
# Test embedding model
python -c "
from SphinxAI.handlers.openvino_handler import OpenVINOHandler
handler = OpenVINOHandler('SphinxAI/models/openvino/all-MiniLM-L6-v2')
result = handler.encode(['Hello world', 'Test sentence'])
print(f'Embeddings shape: {len(result)}x{len(result[0])}')
"

# Test similarity calculation
python -c "
from SphinxAI.utils.text_processing import TextProcessor
processor = TextProcessor()
similarity = processor.calculate_similarity('hello world', 'hello earth')
print(f'Similarity: {similarity:.3f}')
"
```

### Performance Optimization

#### Model Selection Guidelines

| Forum Size | Recommended Model | Reason |
|------------|------------------|---------|
| Small (<10K posts) | `all-MiniLM-L6-v2` | Fast, low memory |
| Medium (10K-100K) | `all-mpnet-base-v2` | Good balance |
| Large (>100K) | `distiluse-base-*` | Optimized for scale |

#### Hardware Considerations

- **RAM**: 2GB minimum, 4GB+ recommended per model
- **Storage**: SSD recommended for model loading speed
- **CPU**: Modern x86_64 with AVX2 support preferred

### Getting Help

For model-related issues:

1. **Check Logs**
   - Model conversion logs in `logs/model_conversion.log`
   - OpenVINO logs in `logs/openvino.log`

2. **Community Resources**
   - Hugging Face Model Hub documentation
   - OpenVINO documentation
   - GitHub Issues for plugin-specific problems

3. **Model-Specific Support**
   - Check individual model cards on Hugging Face
   - Review model licensing and usage requirements

## PHP Model Management Interface

The plugin includes a modern PHP 8.1+ interface for model management:

### Model Configuration Service
```php
class SphinxAIModelConfig
{
    public function __construct(
        private readonly array $models,
        private readonly string $modelsPath,
        private readonly LoggerInterface $logger,
    ) {}

    public function getModelInfo(ModelType $type): ModelInfo|null
    {
        return match($type) {
            ModelType::EMBEDDING => $this->embeddingModels,
            ModelType::LLM => $this->llmModels,
            ModelType::QUANTIZED => $this->quantizedModels,
        };
    }
}
```

### Model Status Enums
```php
enum ModelStatus: string 
{
    case DOWNLOADING = 'downloading';
    case CONVERTING = 'converting';
    case OPTIMIZING = 'optimizing';
    case READY = 'ready';
    case ERROR = 'error';
}

enum ModelType: string 
{
    case EMBEDDING = 'embedding';
    case LLM = 'llm';
    case QUANTIZED = 'quantized';
}
```

### Admin Interface Features
- **Real-time Status**: Live updates of model download and conversion progress
- **Type Safety**: Enum-based model type selection prevents configuration errors
- **Error Handling**: Comprehensive error reporting with detailed logging
- **Memory Management**: Readonly properties ensure efficient memory usage during model operations

---

Next: [Configuration Guide](CONFIGURATION.md) | [Performance Guide](PERFORMANCE.md)
