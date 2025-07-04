#!/bin/bash
# Sphinx AI Search Plugin Installation Script for Linux/Unix
# This script sets up the Python environment and dependencies

set -e

echo "========================================"
echo "Sphinx AI Search Plugin Setup"
echo "========================================"

# Check if Python is installed
if ! command -v python3 &> /dev/null; then
    echo "ERROR: Python 3 is not installed"
    echo "Please install Python 3.8 or higher"
    exit 1
fi

# Check Python version
PYTHON_VERSION=$(python3 --version 2>&1 | cut -d' ' -f2)
echo "Found Python version: $PYTHON_VERSION"

# Check if pip is available
if ! command -v pip3 &> /dev/null; then
    echo "ERROR: pip3 is not installed"
    echo "Please install pip3"
    exit 1
fi

# Navigate to SphinxAI directory
cd "$(dirname "$0")/SphinxAI"

echo ""
echo "========================================"
echo "Hugging Face Token Setup (Optional)"
echo "========================================"
echo "Some AI models may require a Hugging Face token for access."
echo "If you have a Hugging Face account, you can provide your token now."
echo "You can also skip this and add it later if needed."
echo ""
echo "To get a token:"
echo "1. Go to https://huggingface.co/settings/tokens"
echo "2. Create a new token (read access is sufficient)"
echo "3. Copy the token and paste it below"
echo ""
read -p "Enter your Hugging Face token (or press Enter to skip): " HF_TOKEN

if [ -n "$HF_TOKEN" ]; then
    echo "Setting up Hugging Face token..."
    export HUGGING_FACE_HUB_TOKEN="$HF_TOKEN"
    
    # Save token to config file for persistent use
    echo "[huggingface]" > config.ini
    echo "token = $HF_TOKEN" >> config.ini
    echo "✓ Hugging Face token configured"
else
    echo "Skipping Hugging Face token setup"
    echo "Note: You can add it later by creating config.ini with:"
    echo "[huggingface]"
    echo "token = YOUR_TOKEN_HERE"
fi

echo ""
echo "Installing Python dependencies..."
echo "This may take several minutes..."

# Upgrade pip
python3 -m pip install --upgrade pip

# Install requirements
pip3 install -r requirements.txt
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to install Python dependencies"
    echo "Please check your internet connection and try again"
    exit 1
fi

echo ""
echo "Setting up NLTK data..."
python3 -c "import nltk; nltk.download('punkt', quiet=True); nltk.download('stopwords', quiet=True); nltk.download('wordnet', quiet=True)"

echo ""
echo "Setting up spaCy models..."
python3 -m spacy download en_core_web_sm

echo ""
echo "Creating directories..."
mkdir -p SphinxAI/models cache logs temp

echo ""
echo "Setting permissions..."
chmod 755 *.py
chmod 644 *.txt *.json

echo ""
echo "Testing installation..."
python3 -c "import numpy, torch, transformers, sentence_transformers, sklearn, nltk, spacy; print('All dependencies imported successfully')"
if [ $? -ne 0 ]; then
    echo "ERROR: Installation test failed"
    echo "Some dependencies may not be installed correctly"
    exit 1
fi

echo ""
echo "========================================"
echo "Downloading and converting AI models..."
echo "This process may take 15-45 minutes depending on internet speed"
echo "========================================"

echo ""
echo "Downloading embedding model for multilingual search..."
python3 unified_model_converter.py --embedding-model multilingual_mpnet
if [ $? -ne 0 ]; then
    echo "WARNING: Embedding model conversion failed"
    echo "AI similarity features may not work properly"
    echo "You can retry later with: python3 unified_model_converter.py --embedding-model multilingual_mpnet"
fi

echo ""
echo "Downloading chat model for AI summarization..."
python3 unified_model_converter.py --llm-model chat
if [ $? -ne 0 ]; then
    echo "WARNING: LLM model conversion failed"
    echo "AI summarization features may not work properly"
    echo "You can retry later with: python3 unified_model_converter.py --llm-model chat"
fi

echo ""
echo "Checking model availability..."
python3 -c "
import os
models_dir = 'SphinxAI/models'
embedding_dir = os.path.join(models_dir, 'embeddings')
llm_dir = os.path.join(models_dir, 'llm')

if os.path.exists(embedding_dir) and os.listdir(embedding_dir):
    print('✓ Embedding models available')
else:
    print('⚠ Embedding models not found')

if os.path.exists(llm_dir) and os.listdir(llm_dir):
    print('✓ LLM models available')
else:
    print('⚠ LLM models not found')
"

echo ""
echo "========================================"
echo "Installation completed successfully!"
echo "========================================"
echo ""
echo "Next steps:"
echo "1. Install the SMF plugin through your admin panel"
echo "2. Configure the plugin settings"
echo "3. Set up Sphinx search daemon:"
echo "   - Install Sphinx: apt-get install sphinxsearch (Ubuntu/Debian)"
echo "   - Configure: /etc/sphinxsearch/sphinx.conf"
echo "   - Start daemon: systemctl start sphinxsearch"
echo "4. Run initial indexing"
echo ""
echo "For detailed instructions, see README.md"
echo ""
